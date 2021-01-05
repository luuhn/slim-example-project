<?php

namespace App\Application\Actions\Auth;

use App\Application\Responder\Responder;
use App\Domain\Auth\AuthService;
use App\Domain\Exceptions\InvalidCredentialsException;
use App\Domain\Exceptions\ValidationException;
use App\Domain\User\User;
use App\Domain\User\UserService;
use App\Domain\Utility\ArrayReader;
use Odan\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Log\LoggerInterface;

final class RegistrationAction
{
    protected UserService $userService;
    protected LoggerInterface $logger;
    protected Responder $responder;
    protected AuthService $authService;
    private SessionInterface $session;


    public function __construct(
        LoggerInterface $logger,
        UserService $userService,
        Responder $responder,
        AuthService $authService,
        SessionInterface $session
    ) {
        $this->userService = $userService;
        $this->logger = $logger;
        $this->responder = $responder;
        $this->authService = $authService;
        $this->session = $session;
    }

    public function __invoke(ServerRequest $request, Response $response): Response
    {
        // If a html form name changes, these changes have to be done in the Entities constructor
        // too since these names will be the keys from the ArrayReader
        $userData = $request->getParsedBody();

        // Use Entity instead of DTO for simplicity https://github.com/samuelgfeller/slim-api-example/issues/2#issuecomment-597245455
        $user = new User(new ArrayReader($userData));

        try {
            $insertId = $this->userService->createUser($user);
        } catch (ValidationException $exception) {
            return $this->responder->redirectOnValidationError(
                $response,
                $exception->getValidationResult(),
                'register-page'
            );
        }

        // Clear sessions
        $flash = $this->session->getFlash();
        $flash->clear();

        if (null !== $insertId) {
            $this->logger->info('User "' . $userData['email'] . '" created');

            // Log user in (go through official method as double check before starting session), (user with plain pass)
            $this->authService->getUserIdIfAllowedToLogin($user); // Throws InvalidCredentialsException if not allowed

            try {
                $userId = $this->authService->GetUserIdIfAllowedToLogin($user);

                // Clears all session data and regenerates session ID
                $this->session->regenerateId();

                $this->session->set('user', $userId);
                $flash->add('success', 'Login successful');

                $this->logger->info('Successful register + login from user "' . $user->getEmail() . '"');

                return $this->responder->redirect($response, 'post-list-all');
            } catch (InvalidCredentialsException $e) {
                $flash->add('error', 'Automatic login after registration failed!');

                // Log error
                $this->logger->notice(
                    'InvalidCredentialsException thrown with message: "' . $e->getMessage(
                    ) . '" user "' . $e->getUserEmail() . '"'
                );
                return $this->responder->redirect($response, 'login-page');
            }
        }
        $flash->add('error', 'Registration failed');
        return $this->responder->redirect($response, 'register-page');
    }
}