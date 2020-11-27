<?php


namespace App\Application\Middleware;

use App\Application\Exceptions\CorsMiddlewareException;
use Exception;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;
use Throwable;

/**
 * CORS middleware.
 */
final class CorsMiddleware implements MiddlewareInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    private ResponseFactoryInterface $responseFactory;

    /**
     * @param ResponseFactoryInterface $responseFactory The response factory
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory
    ) {
        $this->responseFactory = $responseFactory;
    }

    /**
     * Invoke Cors middleware
     *
     * Source: http://www.slimframework.com/docs/v4/cookbook/enable-cors.html
     * https://odan.github.io/2019/11/24/slim4-cors.html
     *
     * @param ServerRequestInterface $request The request
     * @param RequestHandlerInterface $handler The handler
     *
     * @return ResponseInterface The response
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeContext = RouteContext::fromRequest($request);
        $routingResults = $routeContext->getRoutingResults();
        $methods = $routingResults->getAllowedMethods();
        $requestHeaders = $request->getHeaderLine('Access-Control-Request-Headers');

        try {
            $response = $handler->handle($request);
        } catch (Throwable $throwable) {
        }

        if (!isset($response)) {
            $response = $this->responseFactory->createResponse(500);
        }

        $response = $response->withHeader('Access-Control-Allow-Origin', 'http://dev.frontend-example');
        $response = $response->withHeader('Access-Control-Allow-Methods', implode(', ', $methods));
        $response = $response->withHeader('Access-Control-Allow-Headers', $requestHeaders ?: '*');

        // Allow Ajax CORS requests with Authorization header
        $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');

        if (isset($throwable)) {
            $response = $response->withHeader('Content-Type', 'application/json');

            // Add custom response body here...
            $response->getBody()->write(
                json_encode(
                    [
                        'error' => [
                            'message' => $throwable->getMessage(),
                        ]
                    ],
                )
            );

            // Throw exception to pass the response with the CORS headers
            throw new CorsMiddlewareException($response, $throwable->getMessage(), 500, $throwable);
        }

        return $response;
    }
}