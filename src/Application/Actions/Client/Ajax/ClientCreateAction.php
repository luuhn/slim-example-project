<?php

namespace App\Application\Actions\Client\Ajax;

use App\Application\Responder\Responder;
use App\Domain\Client\Service\ClientCreator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action.
 */
final class ClientCreateAction
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ClientCreator $clientCreator
     */
    public function __construct(
        private readonly Responder $responder,
        private readonly ClientCreator $clientCreator,
    ) {
    }

    /**
     * Action.
     *
     * @param ServerRequestInterface $request The request
     * @param ResponseInterface $response The response
     * @param array $args
     *
     * @return ResponseInterface The response
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $clientValues = $request->getParsedBody();

        // Validation and Forbidden exception caught in respective middlewares
        $insertId = $this->clientCreator->createClient($clientValues);

        if (0 !== $insertId) {
            return $this->responder->respondWithJson($response, ['status' => 'success', 'data' => null], 201);
        }
        $response = $this->responder->respondWithJson($response, [
            'status' => 'warning',
            'message' => 'Client not created',
        ]);

        return $response->withAddedHeader('Warning', 'The client could not be created');
    }
}
