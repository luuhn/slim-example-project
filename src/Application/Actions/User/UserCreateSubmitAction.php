<?php

namespace App\Application\Actions\User;

use App\Application\Responder\Responder;
use App\Application\Validation\MalformedRequestBodyChecker;
use App\Domain\Exceptions\ForbiddenException;
use App\Domain\Exceptions\ValidationException;
use App\Domain\Factory\LoggerFactory;
use App\Domain\Security\Exception\SecurityException;
use App\Domain\User\Service\UserCreator;
use Fig\Http\Message\StatusCodeInterface;
use Odan\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

final class UserCreateSubmitAction
{
    protected LoggerInterface $logger;

    public function __construct(
        LoggerFactory $logger,
        protected Responder $responder,
        protected UserCreator $userRegisterer,
        private readonly SessionInterface $session,
        private readonly MalformedRequestBodyChecker $malformedRequestBodyChecker,
    ) {
        $this->logger = $logger->addFileHandler('error.log')->createInstance('user-create-action');
    }

    public function __invoke(ServerRequest $request, Response $response): Response
    {
        $flash = $this->session->getFlash();
        $userValues = $request->getParsedBody();

        if ($this->malformedRequestBodyChecker->requestBodyHasValidKeys($userValues, [
            'first_name',
            'surname',
            'email',
            'status',
            'user_role_id',
            'password',
            'password2',
        ], ['g-recaptcha-response'])) {
            // Populate $captcha var if reCAPTCHA response is given
            $captcha = $userValues['g-recaptcha-response'] ?? null;

            try {
                // Throws exception if there is error and returns false if user already exists
                $insertId = $this->userRegisterer->createUser($userValues, $captcha, $request->getQueryParams());
                // Say email has been sent even when user exists as it should be kept secret
                $flash->add('success', 'Email has been sent.');
                $flash->add(
                    'warning',
                    'Your account is not active yet. <br>
Please click on the link in the email to finnish the registration.'
                );

                if ($insertId !== false) {
                    $this->logger->info('User "' . $userValues['email'] . '" created');
                } else {
                    $this->logger->info('Account creation tried with existing email: "' . $userValues['email'] . '"');
                    $response = $response->withAddedHeader('Warning', 'The post could not be created');
                }
                return $this->responder->respondWithJson($response, ['status' => 'success', 'data' => null], 201);
            } catch (ValidationException $validationException) {
                return $this->responder->respondWithJsonOnValidationError(
                    $validationException->getValidationResult(),
                    $response
                );
            } catch (TransportExceptionInterface $e) {
                $flash->add('error', 'Email error. Please try again. ' . "<br> Message: " . $e->getMessage());
                $this->logger->error('Mailer exception: ' . $e->getMessage());
                $response = $response->withStatus(500);
                $this->responder->addPhpViewAttribute('formError', true);
                return $this->responder->render(
                    $response,
                    'authentication/register.html.php',
                    // Provide same query params passed to register page to be added again to the submit request
                    ['queryParams' => $request->getQueryParams()]
                );
            } catch (SecurityException $se) {
                if (PHP_SAPI === 'cli') {
                    // If script is called from commandline (e.g. testing) throw error instead of rendering page
                    throw $se;
                }

                return $this->responder->respondWithFormThrottle(
                    $response,
                    'authentication/register.html.php',
                    $se,
                    $request->getQueryParams(),
                    [
                        'firstName' => $userValues['first_name'],
                        'surname' => $userValues['surname'],
                        'email' => $userValues['email']
                    ],
                );
            } catch (ForbiddenException $forbiddenException) {
                return $this->responder->respondWithJson(
                    $response,
                    [
                        'status' => 'error',
                        'message' => 'Not allowed to create user.'
                    ],
                    StatusCodeInterface::STATUS_FORBIDDEN
                );
            }
        }
        // Prevent to log passwords (if keys not set unset() will not trigger notice or warning)
        unset($userValues['password'], $userValues['password2']);
        $this->logger->error('POST request body malformed: ' . json_encode($userValues, JSON_THROW_ON_ERROR));
        // Caught in error handler which displays error page. If request body is malformed, frontend has error.
        throw new HttpBadRequestException($request, 'Request body malformed.');
    }
}