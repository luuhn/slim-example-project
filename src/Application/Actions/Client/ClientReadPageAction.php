<?php

namespace App\Application\Actions\Client;

use App\Application\Responder\Responder;
use App\Domain\Client\Service\ClientFinder;
use App\Domain\Client\Service\ClientUtilFinder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action.
 */
final class ClientReadPageAction
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param ClientFinder $clientFinder
     */
    public function __construct(
        private readonly Responder $responder,
        private readonly ClientFinder $clientFinder,
        protected readonly ClientUtilFinder $clientUtilFinder,
    ) {
    }

    /**
     * Action.
     *
     * @param ServerRequestInterface $request The request
     * @param ResponseInterface $response The response
     *
     * @param array $args
     * @return ResponseInterface The response
     * @throws \JsonException
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $clientAggregate = $this->clientFinder->findClientReadAggregate((int)$args['client_id'], false);
        $dropdownValues = $this->clientUtilFinder->findClientDropdownValues();

        return $this->responder->render(
            $response,
            'client/client-read.html.php',
            ['clientAggregate' => $clientAggregate, 'dropdownValues' => $dropdownValues]
        );
    }
}
