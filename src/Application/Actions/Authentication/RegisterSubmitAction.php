<?php

namespace App\Application\Actions\Authentication;

use App\Application\Responder\Responder;
use App\Domain\Authentication\Service\UserRegisterer;
use App\Domain\Exceptions\ValidationException;
use App\Domain\Factory\LoggerFactory;
use App\Domain\Security\Exception\SecurityException;
use Odan\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

final class RegisterSubmitAction
{
    protected LoggerInterface $logger;

    public function __construct(
        LoggerFactory $logger,
        protected Responder $responder,
        protected UserRegisterer $userRegisterer,
        private SessionInterface $session
    ) {
        $this->logger = $logger->addFileHandler('error.log')->createInstance('auth-register');
    }

    public function __invoke(ServerRequest $request, Response $response): Response
    {
        $flash = $this->session->getFlash();
        $userData = $request->getParsedBody();

        if (null !== $userData && [] !== $userData) {
            // ? If a html form name changes, these changes have to be done in the data class constructor
            // ? (and the array keys in the "if" condition below) too since these names will be the keys of the ArrayReader
            // Check that request body syntax is formatted right (one more when captcha)
            $requiredAreSet = isset($userData['first_name'], $userData['surname'], $userData['email'],
                $userData['password'], $userData['password2']);
            if (
                ($requiredAreSet && count($userData) === 5) ||
                ($requiredAreSet && (count($userData) === 6 && isset($userData['g-recaptcha-response'])))
            ) {
                // Populate $captcha var if reCAPTCHA response is given
                $captcha = $userData['g-recaptcha-response'] ?? null;

                try {
                    // Throws exception if there is error and returns false if user already exists
                    $insertId = $this->userRegisterer->registerUser($userData, $captcha, $request->getQueryParams());
                    // Say email has been sent even when user exists as it should be kept secret
                    $flash->add('success', 'Email has been sent.');
                    $flash->add(
                        'warning',
                        'Your account is not active yet. <br>
Please click on the link in the email to finnish the registration.'
                    );
                } catch (ValidationException $ve) {
                    $flash->add('error', $ve->getMessage());
                    return $this->responder->renderOnValidationError(
                        $response,
                        'Authentication/register.html.php',
                        $ve->getValidationResult(),
                        $request->getQueryParams()
                    );
                } catch (TransportExceptionInterface $e) {
                    $flash->add('error', 'Email error. Please try again. ' . "<br> Message: " . $e->getMessage());
                    $this->logger->error('Mailer exception: ' . $e->getMessage());
                    $response = $response->withStatus(500);
                    $this->responder->addAttribute('formError', true);
                    return $this->responder->render(
                        $response,
                        'Authentication/register.html.php',
                        // Provide same query params passed to register page to be added again to the submit request
                        ['queryParams' => $request->getQueryParams()]
                    );
                } catch (SecurityException $se) {
                    if (PHP_SAPI === 'cli') {
                        // If script is called from commandline (e.g. testing) throw error instead of rendering page
                        throw $se;
                    }
                    $flash->add('error', $se->getPublicMessage());
                    return $this->responder->respondWithThrottle(
                        $response,
                        $se->getRemainingDelay(),
                        'Authentication/register.html.php',
                        ['firstName' => $userData['first_name'], 'surname' => $userData['surname'], 'email' => $userData['email']],
                        $request->getQueryParams()
                    );
                }

                if ($insertId !== false) {
                    $this->logger->info('User "' . $userData['email'] . '" created');
                } else {
                    $this->logger->info('Account creation tried with existing email: "' . $userData['email'] . '"');
                }
                // Redirect for new user and if email already exists is the same
//                return $response;
                return $this->responder->redirectToRouteName($response, 'register-check-email-page');
            }
            $flash->add('error', 'Malformed request body syntax');
            // Prevent to log passwords; if keys not set unset() will not trigger notice or warning
            unset($userData['password'], $userData['password2']);
            $this->logger->error('POST request body malformed: ' . json_encode($userData));
            // Caught in error handler which displays error page because if POST request body is empty frontend has error
            // Error message same as in tests/Provider/UserProvider->malformedRequestBodyProvider()
            throw new HttpBadRequestException($request, 'Request body malformed.');
        }
        $flash->add('error', 'Request body empty');
        $this->logger->error('POST request body empty');
        // Caught in error handler which displays error page because if POST request body is empty frontend has error
        // Error message same as in tests/Provider/UserProvider->malformedRequestBodyProvider()
        throw new HttpBadRequestException($request, 'Request body is empty.');
    }
}