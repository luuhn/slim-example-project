<?php

namespace App\Application\Actions\Note\Ajax;

use App\Application\Responder\Responder;
use App\Application\Validation\MalformedRequestBodyChecker;
use App\Domain\Authentication\Exception\ForbiddenException;
use App\Domain\Note\Service\NoteCreator;
use App\Domain\Note\Service\NoteFinder;
use App\Domain\User\Service\UserFinder;
use App\Domain\Validation\ValidationExceptionOld;
use Fig\Http\Message\StatusCodeInterface;
use IntlDateFormatter;
use Odan\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;

/**
 * Action.
 */
final class NoteCreateAction
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param NoteCreator $noteCreator
     * @param SessionInterface $session
     * @param UserFinder $userFinder
     * @param NoteFinder $noteFinder
     * @param MalformedRequestBodyChecker $malformedRequestBodyChecker
     */
    public function __construct(
        private readonly Responder $responder,
        private readonly NoteCreator $noteCreator,
        private readonly SessionInterface $session,
        private readonly UserFinder $userFinder,
        private readonly NoteFinder $noteFinder,
        private readonly MalformedRequestBodyChecker $malformedRequestBodyChecker,
    ) {
    }

    /**
     * Action.
     *
     * @param ServerRequestInterface $request The request
     * @param ResponseInterface $response The response
     * @param array $args
     *
     * @throws \JsonException
     *
     * @return ResponseInterface The response
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        if (($loggedInUserId = $this->session->get('user_id')) !== null) {
            $noteValues = $request->getParsedBody();

            // Check that request body syntax is formatted right
            if ($this->malformedRequestBodyChecker->requestBodyHasValidKeys(
                $noteValues,
                ['message', 'client_id', 'is_main']
            )) {
                try {
                    $insertId = $this->noteCreator->createNote($noteValues);
                    $noteDataFromDb = $this->noteFinder->findNote($insertId);
                } catch (ValidationExceptionOld $exception) {
                    return $this->responder->respondWithJsonOnValidationError(
                        $exception->getValidationResult(),
                        $response
                    );
                } catch (ForbiddenException $forbiddenException) {
                    return $this->responder->respondWithJson(
                        $response,
                        [
                            'status' => 'error',
                            'message' => __(sprintf('Not allowed to create %s', __('note'))),
                        ],
                        StatusCodeInterface::STATUS_FORBIDDEN
                    );
                }

                if (0 !== $insertId) {
                    $user = $this->userFinder->findUserById($loggedInUserId);
                    $dateFormatter = new IntlDateFormatter(
                        setlocale(LC_ALL, 0),
                        IntlDateFormatter::LONG,
                        IntlDateFormatter::SHORT
                    );

                    // camelCase according to Google recommendation
                    return $this->responder->respondWithJson($response, [
                        'status' => 'success',
                        'data' => [
                            'userFullName' => $user->firstName . ' ' . $user->surname,
                            'noteId' => $insertId,
                            'createdDateFormatted' => $dateFormatter->format($noteDataFromDb->createdAt),
                        ],
                    ], 201);
                }
                $response = $this->responder->respondWithJson($response, [
                    'status' => 'warning',
                    'message' => 'Note not created',
                ]);

                return $response->withAddedHeader('Warning', 'The note could not be created');
            }
            throw new HttpBadRequestException($request, 'Request body malformed.');
        }

        // Handled by AuthenticationMiddleware
        return $response;
    }
}
