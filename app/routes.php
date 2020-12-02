<?php

use App\Application\Actions\PreflightAction;
use App\Application\Middleware\UserAuthMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Exception\HttpNotFoundException;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {

    $app->get('/login', \App\Application\Actions\Auth\LoginAction::class)->setName('login-page');
    $app->post('/login', \App\Application\Actions\Auth\LoginSubmitAction::class)->setName('login-submit-form');

    $app->get('/logout', \App\Application\Actions\Auth\LogoutAction::class)->setName('logout');

    $app->post('/register', \App\Application\Actions\Auth\RegistrationAction::class)->setName('auth-register');

    $app->group('/users', function (RouteCollectorProxy $group) {
        $group->options('', PreflightAction::class); // Allow preflight requests
        $group->get('', \App\Application\Actions\Users\UserListAction::class);

        $group->options('/{id:[0-9]+}', PreflightAction::class); // Allow preflight requests
        $group->get('/{id:[0-9]+}', \App\Application\Actions\Users\UserViewAction::class);
        $group->put('/{id:[0-9]+}', \App\Application\Actions\Users\UserUpdateAction::class);
        $group->delete('/{id:[0-9]+}', \App\Application\Actions\Users\UserDeleteAction::class);
    })->add(UserAuthMiddleware::class);

    // Post requests where user needs to be authenticated
    $app->group('/posts', function (RouteCollectorProxy $group) {
        $group->get('', \App\Application\Actions\Posts\PostListAction::class)->setName('post-list-all');
        $group->post('', \App\Application\Actions\Posts\PostCreateAction::class);

        $group->get('/{id:[0-9]+}', \App\Application\Actions\Posts\PostViewAction::class);
        $group->put('/{id:[0-9]+}', \App\Application\Actions\Posts\PostUpdateAction::class);
        $group->delete('/{id:[0-9]+}', \App\Application\Actions\Posts\PostDeleteAction::class);
    })->add(UserAuthMiddleware::class);

    $app->get('/own-posts', \App\Application\Actions\Posts\PostViewOwnAction::class)->setName('post-list-own');

    $app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
        $name = $args['name'];
        $response->getBody()->write("Hello, $name");
        $response->getBody()->write(json_encode($response->getStatusCode()));
        throw new HttpInternalServerErrorException('Nooooooooooooooo!');
        return $response;
    });

/*    $app->options('/{routes:.+}', function ($request, $response, $args) {
        return $response;
    });*/


    /**
     * Catch-all route to serve a 404 Not Found page if none of the routes match
     * NOTE: make sure this route is defined last
     */
    $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
        throw new HttpNotFoundException($request);
    });
};
