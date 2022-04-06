<?php

namespace App\Test\Integration\Authentication;

use App\Domain\Authentication\Data\UserVerificationData;
use App\Domain\User\Data\UserData;
use App\Test\Fixture\UserFixture;
use App\Test\Traits\AppTestTrait;
use App\Test\Traits\RouteTestTrait;
use Fig\Http\Message\StatusCodeInterface;
use Odan\Session\SessionInterface;
use PHPUnit\Framework\TestCase;
use Selective\TestTrait\Traits\DatabaseTestTrait;
use Selective\TestTrait\Traits\HttpJsonTestTrait;
use Selective\TestTrait\Traits\HttpTestTrait;
use Slim\Exception\HttpBadRequestException;

/**
 * Integration testing password change from authenticated user
 *  - request to set new password with valid token
 *  - request to set new password with invalid, expired and used token -> redirect to login page
 *  - request to set new password with valid token but invalid data -> 400 Bad request
 *  - request to set new password with malformed request body -> HttpBadRequestException
 */
class ResetPasswordSubmitActionTest extends TestCase
{

    use AppTestTrait;
    use HttpTestTrait;
    use HttpJsonTestTrait;
    use RouteTestTrait;
    use DatabaseTestTrait;

    /**
     * Request to reset password with token
     *
     * @dataProvider \App\Test\Provider\Authentication\UserVerificationDataProvider::userVerificationProvider()
     *
     * @param UserVerificationData $verification
     * @param string $clearTextToken
     */
    public function testResetPasswordSubmit(UserVerificationData $verification, string $clearTextToken): void
    {
        $newPassword = 'new password';
        // Insert user id 2 role: user
        $userRow = (new UserFixture())->records[1];
        $this->insertFixture('user', $userRow);

        $this->insertFixture('user_verification', $verification->toArrayForDatabase());

        $request = $this->createFormRequest(
            'POST', // Request to change password
            $this->urlFor('password-reset-submit'),
            [
                'password' => $newPassword,
                'password2' => $newPassword,
                'token' => $clearTextToken,
                'id' => $verification->id,
            ]
        );

        $response = $this->app->handle($request);

        // Assert: 302 Redirect to desired page
        self::assertSame(StatusCodeInterface::STATUS_FOUND, $response->getStatusCode());

        self::assertSame($this->urlFor('profile-page'), $response->getHeaderLine('Location'));

        // Assert that session user_id is set meaning user is logged-in
        self::assertNotNull($this->container->get(SessionInterface::class)->get('user_id'));

        // Assert that password was changed correctly
        $dbPasswordHash = $this->getTableRowById('user', $userRow['id'])['password_hash'];

        // Verify that hash matches the given password
        self::assertTrue(password_verify($newPassword, $dbPasswordHash));
    }

    /**
     * Test password submit reset with invalid, used or expired token
     *
     * @dataProvider \App\Test\Provider\Authentication\UserVerificationDataProvider::userVerificationInvalidExpiredProvider()
     * @param UserVerificationData $verification
     * @param string $clearTextToken
     */
    public function testResetPasswordSubmit_invalidUsedExpiredToken(
        UserVerificationData $verification,
        string $clearTextToken
    ): void {
        // User needed to insert verification
        $userRow = (new UserFixture())->records[1];
        $userRow['status'] = UserData::STATUS_UNVERIFIED;
        $this->insertFixture('user', $userRow);

        $this->insertFixture('user_verification', $verification->toArrayForDatabase());
        $newPassword = 'new password';
        $request = $this->createFormRequest(
            'POST', // Request to change password
            $this->urlFor('password-reset-submit'),
            [
                'password' => $newPassword,
                'password2' => $newPassword,
                'token' => $clearTextToken,
                'id' => $verification->id,
            ]
        );

        $response = $this->app->handle($request);

        // Assert 302 Found redirect to login page
        self::assertSame(StatusCodeInterface::STATUS_FOUND, $response->getStatusCode());

        // Assert that client is redirected to login page
        self::assertSame($this->urlFor('login-page'), $response->getHeaderLine('Location'));

        // Assert that token had NOT been used (except if already used)
        self::assertSame(
            $verification->usedAt,
            $this->getTableRowById('user_verification', $verification->id, ['used_at'])['used_at']
        );

        // Assert that password was not changed to the new one
        $this->assertTableRowValue(UserData::STATUS_UNVERIFIED, 'user', $userRow['id'], 'status');

        // Assert that user is not logged in
        self::assertNull($this->container->get(SessionInterface::class)->get('user_id'));

        // Assert that password was NOT changed
        $dbPasswordHash = $this->getTableRowById('user', $userRow['id'])['password_hash'];
        self::assertFalse(password_verify($newPassword, $dbPasswordHash));
    }


    /**
     * Test that backend validation fails when new passwords are invalid
     *
     * @dataProvider \App\Test\Provider\Authentication\UserVerificationDataProvider::userVerificationProvider()
     *
     * @param UserVerificationData $verification
     * @param string $clearTextToken
     */
    public function testResetPasswordSubmit_invalidData(UserVerificationData $verification, string $clearTextToken): void
    {
        // Invalid new password
        $newPassword = '1';
        // Insert user id 2 role: user
        $userRow = (new UserFixture())->records[1];
        $this->insertFixture('user', $userRow);

        $this->insertFixture('user_verification', $verification->toArrayForDatabase());

        $request = $this->createFormRequest(
            'POST', // Request to change password
            $this->urlFor('password-reset-submit'),
            [
                'password' => $newPassword,
                'password2' => $newPassword,
                'token' => $clearTextToken,
                'id' => $verification->id,
            ]
        );

        $response = $this->app->handle($request);

        // Assert that response has error status 422
        self::assertSame(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY, $response->getStatusCode());

        // As form is directly rendered with validation errors it's not possible to test them as response is a stream
    }


    /**
     * Empty or malformed request body is when parameters are not set or have
     * the wrong name ("key").
     *
     * If the request contains a different body than expected, HttpBadRequestException
     * is thrown and an error page is displayed to the user because that means that
     * there is an error with the client sending the request that has to be fixed.
     *
     * @dataProvider \App\Test\Provider\User\UserDataProvider::malformedPasswordResetRequestBodyProvider()
     *
     * @param array|null $malformedBody null for the case that request body is null
     * @param string $message
     */
    public function testChangePassword_malformedBody(null|array $malformedBody, string $message): void
    {
        // Insert user id 2 role: user
        $userRow = (new UserFixture())->records[1];
        $this->insertFixture('user', $userRow);

        $malformedRequest = $this->createFormRequest(
            'POST',
            $this->urlFor('password-reset-submit'),
            $malformedBody
        );

        // Bad Request (400) means that the client sent the request wrongly; it's a client error
        $this->expectException(HttpBadRequestException::class);
        $this->expectExceptionMessage($message);

        // Handle request after defining expected exceptions
        $this->app->handle($malformedRequest);
    }
}
