<?php

namespace App\Test\Integration\User;

use App\Domain\User\Enum\UserRole;
use App\Infrastructure\Authentication\UserRoleFinderRepository;
use App\Test\Fixture\UserFixture;
use App\Test\Traits\AppTestTrait;
use App\Test\Traits\DatabaseExtensionTestTrait;
use App\Test\Traits\FixtureTrait;
use App\Test\Traits\RouteTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Odan\Session\SessionInterface;
use PHPUnit\Framework\TestCase;
use Selective\TestTrait\Traits\DatabaseTestTrait;
use Selective\TestTrait\Traits\HttpJsonTestTrait;
use Selective\TestTrait\Traits\HttpTestTrait;
use Slim\Exception\HttpBadRequestException;

/**
 * Integration testing user update Process
 */
class UserCreateActionTest extends TestCase
{

    use AppTestTrait;
    use HttpTestTrait;
    use HttpJsonTestTrait;
    use RouteTestTrait;
    use DatabaseTestTrait;
    use DatabaseExtensionTestTrait;
    use FixtureTrait;

    /**
     * User create authorization test with different user roles
     *
     * @dataProvider \App\Test\Provider\User\UserCreateCaseProvider::userCreateAuthorizationCases()
     *
     * @param array $authenticatedUserAttr authenticated user attributes containing the user_role_id
     * @param UserRole $newUserRole user role id of new user
     * @param array $expectedResult HTTP status code, bool if db_entry_created and json_response
     * @return void
     */
    public function testUserSubmitCreate_authorization(
        array $authenticatedUserAttr,
        UserRole $newUserRole,
        array $expectedResult
    ): void {
        $userRoleFinder = $this->container->get(UserRoleFinderRepository::class);
        // If user role is provided and is instance of UserRole, replace array key with the actual id
        if ($authenticatedUserAttr['user_role_id'] ?? '' instanceof UserRole){
            $authenticatedUserAttr['user_role_id'] = $userRoleFinder->findUserRoleIdByName(
                $authenticatedUserAttr['user_role_id']->value
            );
        }
        // Insert authenticated user and user linked to resource with given attributes containing the user role
        $authenticatedUserRow = $this->insertFixturesWithAttributes($authenticatedUserAttr, UserFixture::class);

        $requestData = [
            'first_name' => 'Danny',
            'surname' => 'Ric',
            'email' => 'daniel.riccardo@notmclaren.com',
            'password' => '12345678',
            'password2' => '12345678',
            'status' => 'unverified',
            'user_role_id' => $userRoleFinder->findUserRoleIdByName($newUserRole->value)
        ];

        $request = $this->createJsonRequest(
            'POST',
            $this->urlFor('user-create-submit'),
            $requestData
        );
        // Simulate logged-in user with logged-in user id
        $this->container->get(SessionInterface::class)->set('user_id', $authenticatedUserRow['id']);
        $response = $this->app->handle($request);
        // Assert status code
        self::assertSame($expectedResult[StatusCodeInterface::class], $response->getStatusCode());
        // Assert database
        if ($expectedResult['db_changed'] === true) {
            $userDbRow = $this->findLastInsertedTableRow('user');
            // Request data can be taken to assert database as keys correspond to database columns after removing passwords
            unset($requestData['password'], $requestData['password2']);
            $this->assertTableRowEquals($requestData, 'user', $userDbRow['id']);
        } else {
            // Only 1 rows (authenticated user) expected in user table
            $this->assertTableRowCount(1, 'user');
        }
        $this->assertJsonData($expectedResult['json_response'], $response);
    }

    /**
     * Test that user is redirected to login page
     * if trying to do unauthenticated request
     *
     * @return void
     */
    public function testUserSubmitCreate_unauthenticated(): void
    {
        // Request body doesn't have to be passed as missing session is caught in a middleware before the action
        $request = $this->createJsonRequest('PUT', $this->urlFor('user-update-submit', ['user_id' => 1]));
        // Create url where user should be redirected to after login
        $redirectToUrlAfterLogin = $this->urlFor('user-read-page', ['user_id' => 1]);
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
     * Test user submit invalid update data
     *
     * @dataProvider \App\Test\Provider\User\UserUpdateCaseProvider::invalidUserUpdateCases()
     *
     * @param array $requestBody
     * @param array $jsonResponse
     */
    public function testUserSubmitCreate_invalid(array $requestBody, array $jsonResponse): void
    {
        // Insert user that is allowed to change content (owner)
        $userRow = $this->insertFixturesWithAttributes(['user_role_id' => 3], UserFixture::class);

        $request = $this->createJsonRequest(
            'PUT',
            $this->urlFor('user-update-submit', ['user_id' => $userRow['id']]),
            $requestBody
        );
        // Simulate logged-in user with logged-in user id
        $this->container->get(SessionInterface::class)->set('user_id', $userRow['id']);
        $response = $this->app->handle($request);
        // Assert 200 OK
        self::assertSame(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        // database must be unchanged
        $this->assertTableRowEquals($userRow, 'user', $userRow['id']);
        $this->assertJsonData($jsonResponse, $response);
    }

    /**
     * Empty or malformed request body is when parameters
     * are not set or have the wrong name ("key").
     * Example: Server needs the argument "email" to process
     * the request but "email" is not present in the body or
     * misspelled.
     * Good: "email: valid_or_invalid@data.com"
     * Bad: "emal: valid_or_invalid@data.com"
     *
     * If the request contains a different body than expected, HttpBadRequestException
     * is thrown and an error page is displayed to the user because that means that
     * there is an error with the client sending the request that has to be fixed.
     */
    public function testUserSubmitCreate_malformedBody(): void
    {
        // Action class should directly return error so only logged-in user has to be inserted
        $userRow = $this->insertFixturesWithAttributes([], UserFixture::class);
        // Simulate logged-in user with same user as linked to client
        $this->container->get(SessionInterface::class)->set('user_id', $userRow['id']);
        $request = $this->createJsonRequest(
            'PUT',
            $this->urlFor('user-update-submit', ['user_id' => $userRow['id']]),
            // The update request can format the request body pretty freely as long as it doesn't contain a non-allowed key
            // so to test only one invalid key is enough
            ['non_existing_key' => 'value']
        );
        // Bad Request (400) means that the client sent the request wrongly; it's a client error
        $this->expectException(HttpBadRequestException::class);
        $this->expectExceptionMessage('Request body malformed.');
        // Handle request after defining expected exceptions
        $this->app->handle($request);
    }
}
