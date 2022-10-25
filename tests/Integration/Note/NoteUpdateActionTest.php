<?php

namespace App\Test\Integration\Note;

use App\Test\Fixture\ClientFixture;
use App\Test\Fixture\ClientStatusFixture;
use App\Test\Fixture\FixtureTrait;
use App\Test\Fixture\NoteFixture;
use App\Test\Fixture\UserFixture;
use App\Test\Traits\AppTestTrait;
use App\Test\Traits\DatabaseExtensionTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Odan\Session\SessionInterface;
use PHPUnit\Framework\TestCase;
use Selective\TestTrait\Traits\DatabaseTestTrait;
use Selective\TestTrait\Traits\HttpJsonTestTrait;
use Selective\TestTrait\Traits\HttpTestTrait;
use Selective\TestTrait\Traits\RouteTestTrait;
use Slim\Exception\HttpBadRequestException;


/**
 * Test cases for client read note modification
 *  - Authenticated with different user roles
 *  - Unauthenticated
 *  - Invalid data (validation test)
 *  - Malformed request body
 */
class NoteUpdateActionTest extends TestCase
{

    use AppTestTrait;
    use HttpTestTrait;
    use HttpJsonTestTrait;
    use RouteTestTrait;
    use DatabaseTestTrait;
    use DatabaseExtensionTestTrait;
    use FixtureTrait;

    /**
     * Test note modification on client-read page while being authenticated
     *
     * @dataProvider \App\Test\Provider\Note\NoteCaseProvider::provideUsersAndExpectedResultForNoteCrud()
     * @return void
     */
    public function testNoteUpdateAction(
        array $userLinkedToNoteData,
        array $authenticatedUserData,
        array $expectedResult
    ): void {
        $authenticatedUserData['id'] = $this->insertFixture('user', $authenticatedUserData);
        // If authenticated user and user that should be linked to client is different, insert both
        if ($userLinkedToNoteData['user_role_id'] !== $authenticatedUserData['user_role_id']) {
            $userLinkedToNoteData['id'] = $this->insertFixture('user', $userLinkedToNoteData);
        } else {
            $userLinkedToNoteData['id'] = $authenticatedUserData['id'];
        }

        // Insert one client linked to this user
        $clientRow = $this->getFixtureRecordsWithAttributes(['user_id' => $userLinkedToNoteData['id']],
            ClientFixture::class);
        // In array first to assert user data later
        $this->insertFixtureWhere(['id' => $clientRow['client_status_id']], ClientStatusFixture::class);
        $clientRow['id'] = $this->insertFixture('client', $clientRow);

        // Insert main note attached to client and given "owner" user
        $mainNoteData = $this->getFixtureRecordsWithAttributes(
            ['is_main' => 1, 'user_id' => $userLinkedToNoteData['id'], 'client_id' => $clientRow['id']],
            NoteFixture::class
        );
        $mainNoteData['id'] = $this->insertFixture('note', $mainNoteData);

        // Insert normal note attached to client and given "owner" user
        $normalNoteData = $this->getFixtureRecordsWithAttributes(
            ['is_main' => 0, 'user_id' => $userLinkedToNoteData['id'], 'client_id' => $clientRow['id']],
            NoteFixture::class
        );
        $normalNoteData['id'] = $this->insertFixture('note', $normalNoteData);

        // Simulate logged-in user
        $this->container->get(SessionInterface::class)->set('user_id', $authenticatedUserData['id']);

        $newNoteMessage = 'New note message';
        // --- *MAIN note request ---
        // Create request to edit main note
        $mainNoteRequest = $this->createJsonRequest(
            'PUT', $this->urlFor('note-submit-modification', ['note_id' => $mainNoteData['id']]),
            ['message' => $newNoteMessage,]
        );
        // Make request
        $mainNoteResponse = $this->app->handle($mainNoteRequest);

        // Assert 200 OK note updated successfully
        self::assertSame(
            $expectedResult['modification']['main_note'][StatusCodeInterface::class],
            $mainNoteResponse->getStatusCode()
        );

        if ($expectedResult['modification']['main_note']['db_changed'] === true) {
            $this->assertTableRow(['message' => $newNoteMessage], 'note', $mainNoteData['id']);
        } else {
            // If db is not expected to change message should remain the same as when it was inserted first
            $this->assertTableRow(['message' => $mainNoteData['message']], 'note', $mainNoteData['id']);
        }

        // Assert response
        $this->assertJsonData($expectedResult['modification']['main_note']['json_response'], $mainNoteResponse);

        // --- *NORMAL NOTE REQUEST ---
        $normalNoteRequest = $this->createJsonRequest(
            'PUT', $this->urlFor('note-submit-modification', ['note_id' => $normalNoteData['id']]),
            ['message' => $newNoteMessage,]
        );
        // Make request
        $normalNoteResponse = $this->app->handle($normalNoteRequest);
        self::assertSame(
            $expectedResult['modification']['normal_note'][StatusCodeInterface::class],
            $normalNoteResponse->getStatusCode()
        );

        // If db is expected to change assert the new message
        if ($expectedResult['modification']['normal_note']['db_changed'] === true) {
            $this->assertTableRow(['message' => $newNoteMessage], 'note', $normalNoteData['id']);
        } else {
            // If db is not expected to change message should remain the same as when it was inserted first
            $this->assertTableRow(['message' => $normalNoteData['message']], 'note', $normalNoteData['id']);
        }

        $this->assertJsonData($expectedResult['modification']['normal_note']['json_response'], $normalNoteResponse);
    }

    /**
     * Test note modification on client-read page while being unauthenticated.
     *
     * @return void
     */
    public function testNoteUpdateAction_unauthenticated(): void
    {
        $request = $this->createJsonRequest(
            'PUT', $this->urlFor('note-submit-modification', ['note_id' => 1]),
            ['message' => 'New test message',]
        );
        // Create url where client should be redirected to after login
        $redirectToUrlAfterLogin = $this->urlFor('client-read-page', ['client_id' => 1]);
        $request = $request->withAddedHeader('Redirect-to-url-if-unauthorized', $redirectToUrlAfterLogin);
        // Make request
        $response = $this->app->handle($request);
        // Assert response HTTP status code: 401 Unauthorized
        self::assertSame(StatusCodeInterface::STATUS_UNAUTHORIZED, $response->getStatusCode());
        // Build expected login url as UserAuthenticationMiddleware.php does
        $expectedLoginUrl = $this->urlFor('login-page', [], ['redirect' => $redirectToUrlAfterLogin]);
        // Assert that response contains correct login url
        $this->assertJsonData(['loginUrl' => $expectedLoginUrl], $response);
    }

    /**
     * Test note modification on client-read page with invalid data.
     * Fixture dependencies:
     *   - 1 client
     *   - 1 user linked to client
     *   - 1 note that is linked to the client and the user
     *
     * @dataProvider \App\Test\Provider\Note\NoteCaseProvider::provideInvalidNoteAndExpectedResponseDataForModification()
     * @return void
     */
    public function testNoteUpdateAction_invalid(string $invalidMessage, array $expectedResponseData): void
    {
        // Add the minimal needed data
        $clientData = (new ClientFixture())->records[0];
        // Insert user linked to client and user that is logged in
        $userData = $this->findRecordsFromFixtureWhere(['id' => $clientData['user_id']], UserFixture::class)[0];
        $this->insertFixture('user', $userData);
        // Insert linked status
        $this->insertFixtureWhere(['id' => $clientData['client_status_id']], ClientStatusFixture::class);
        // Insert client
        $this->insertFixture('client', $clientData);
        // Insert note linked to client and user
        $noteData = $this->findRecordsFromFixtureWhere(['client_id' => $clientData['id'], 'user_id' => $userData['id']],
            NoteFixture::class)[0];
        $this->insertFixture('note', $noteData);

        // Simulate logged-in user with same user as linked to client
        $this->container->get(SessionInterface::class)->set('user_id', $userData['id']);

        $request = $this->createJsonRequest(
            'PUT', $this->urlFor('note-submit-modification', ['note_id' => $noteData['id']]),
            ['message' => $invalidMessage]
        );
        $response = $this->app->handle($request);

        // Assert 422 Unprocessable entity
        self::assertSame(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY, $response->getStatusCode());

        // Assert json response data
        $this->assertJsonData($expectedResponseData, $response);
    }

    /**
     * Test client read note modification with malformed request body
     *
     * @dataProvider \App\Test\Provider\Note\NoteCaseProvider::provideMalformedNoteRequestBody()
     * @return void
     */
    public function testNoteUpdateAction_malformedRequest(array $malformedRequestBody): void
    {
        // Action class should directly return error so only logged-in user has to be inserted
        $userData = (new UserFixture())->records[0];
        $this->insertFixture('user', $userData);

        // Simulate logged-in user with same user as linked to client
        $this->container->get(SessionInterface::class)->set('user_id', $userData['id']);

        $request = $this->createJsonRequest(
            'PUT',
            $this->urlFor('note-submit-modification', ['note_id' => 1]),
            $malformedRequestBody
        );
        // Bad Request (400) means that the client sent the request wrongly; it's a client error
        $this->expectException(HttpBadRequestException::class);
        $this->expectExceptionMessage('Request body malformed.');

        // Handle request after defining expected exceptions
        $this->app->handle($request);
    }
}