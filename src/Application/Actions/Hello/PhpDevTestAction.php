<?php


namespace App\Application\Actions\Hello;

use App\Application\Responder\Responder;
use App\Domain\Hello\PhpDevTester;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * This action serves when I want to test php concepts, syntax or else while developing
 */
class PhpDevTestAction
{
    public function __construct(
        private Responder $responder,
        private PhpDevTester $phpDevTester
    ) { }

    /**
     * Action.
     *
     * @param ServerRequestInterface $request The request
     * @param ResponseInterface $response The response
     *
     * @param array $args
     * @return ResponseInterface The response
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {

        $this->phpDevTester->testInheritanceInjection();

        return $this->responder->respondWithJson($response, ['success' => 'true']);
    }
}