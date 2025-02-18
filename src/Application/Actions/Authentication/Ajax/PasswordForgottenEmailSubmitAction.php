<?php

namespace App\Application\Actions\Authentication\Ajax;

use App\Application\Responder\Responder;
use App\Application\Validation\MalformedRequestBodyChecker;
use App\Domain\Authentication\Service\PasswordRecoveryEmailSender;
use App\Domain\Exception\DomainRecordNotFoundException;
use App\Domain\Factory\LoggerFactory;
use App\Domain\Security\Exception\SecurityException;
use App\Domain\Validation\ValidationExceptionOld;
use Odan\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

final class PasswordForgottenEmailSubmitAction
{
    protected LoggerInterface $logger;

    /**
     * The constructor.
     *
     * @param Responder $responder
     * @param SessionInterface $session
     * @param PasswordRecoveryEmailSender $passwordRecoveryEmailSender
     * @param LoggerFactory $logger
     * @param MalformedRequestBodyChecker $malformedRequestBodyChecker
     */
    public function __construct(
        private readonly Responder $responder,
        private readonly SessionInterface $session,
        private readonly PasswordRecoveryEmailSender $passwordRecoveryEmailSender,
        private readonly MalformedRequestBodyChecker $malformedRequestBodyChecker,
        LoggerFactory $logger,
    ) {
        $this->logger = $logger->addFileHandler('error.log')->createLogger('auth-login');
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @throws \Throwable
     *
     * @return Response
     */
    public function __invoke(ServerRequest $request, Response $response): Response
    {
        $flash = $this->session->getFlash();
        $userValues = $request->getParsedBody();

        // Check that request body syntax is formatted right
        if ($this->malformedRequestBodyChecker->requestBodyHasValidKeys($userValues, ['email'])) {
            try {
                $this->passwordRecoveryEmailSender->sendPasswordRecoveryEmail($userValues);
            } catch (DomainRecordNotFoundException $domainRecordNotFoundException) {
                // User was not found. Do nothing special as it would be a security flaw to say that user doesn't exist
                $this->logger->info(
                    'User with email ' . $userValues['email'] . ' tried to request password
                reset link but account doesn\'t exist'
                );
            } catch (ValidationExceptionOld $validationException) {
                // Form error messages set in function below
                return $this->responder->renderOnValidationError(
                    $response,
                    'authentication/login.html.php',
                    $validationException,
                    $request->getQueryParams(),
                );
            } catch (SecurityException $securityException) {
                return $this->responder->respondWithFormThrottle(
                    $response,
                    'authentication/login.html.php',
                    $securityException,
                    $request->getQueryParams(),
                    ['email' => $userValues['email']]
                );
            } catch (TransportExceptionInterface $transportException) {
                $flash->add('error', __('There was an error when sending the email.'));

                return $this->responder->render(
                    $response,
                    'authentication/login.html.php',
                    $request->getQueryParams(),
                );
            }
            $flash->add(
                'success',
                __(
                    "Password recovery email is sent to you.<br>
Please also check the spam folder if you don't see it in the inbox."
                )
            );

            return $this->responder->redirectToRouteName($response, 'login-page');
        }

        $flash->add('error', __('Malformed request body syntax. Please contact an administrator.'));
        $this->logger->error('POST request body malformed: ' . json_encode($userValues));
        // Caught in error handler which displays error page because if POST request body is empty frontend has error
        throw new HttpBadRequestException($request, 'Request body malformed.');
    }
}
