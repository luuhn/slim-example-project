<?php


namespace App\Test\Unit\Authentication;

use App\Domain\Authentication\Service\UserRoleFinder;
use App\Infrastructure\Authentication\UserRoleFinderRepository;
use App\Test\Traits\AppTestTrait;
use PHPUnit\Framework\TestCase;

class UserRoleFinderTest extends TestCase
{
    use AppTestTrait;

    /**
     * Test getUserRoleById() with different roles
     *
     * Test with multiple users to have different roles
     * @dataProvider \App\Test\Provider\User\UserDataProvider::validUserProvider()
     * @param array $user
     */
    public function testGetUserRoleById(array $user): void
    {
        $this->mock(UserRoleFinderRepository::class)->method('getUserRoleById')->willReturn($user['role']);

        $userRoleFinder = $this->container->get(UserRoleFinder::class);

        self::assertEquals($user['role'], $userRoleFinder->getUserRoleById($user['id']));
    }
}