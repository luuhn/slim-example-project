<?php

namespace App\Application\Actions\Authentication;

use App\Application\Responder\Responder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action.
 */
final class LoginAction
{
    /**
     * @var Responder
     */
    private Responder $responder;

    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     */
    public function __construct(Responder $responder)
    {
        $this->responder = $responder;
    }

    /**
     * Action.
     *
     * @param ServerRequestInterface $request The request
     * @param ResponseInterface $response The response
     *
     * @return ResponseInterface The response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->responder->render(
            $response,
            'Authentication/login.html.php',
            // Provide same query params passed to login page to be added to the login submit request
            ['queryParams' => $request->getQueryParams()]
        );
    }
}
