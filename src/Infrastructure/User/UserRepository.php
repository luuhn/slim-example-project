<?php

declare(strict_types=1);

namespace App\Infrastructure\User;

use App\Common\Hydrator;
use App\Domain\User\User;
use App\Infrastructure\DataManager;
use App\Infrastructure\Exceptions\PersistenceRecordNotFoundException;

class UserRepository
{
    // Fields without password
    private array $fields = ['id', 'name', 'email', 'updated_at', 'created_at'];

    public function __construct(private DataManager $dataManager, private Hydrator $hydrator)
    {
    }

    /**
     * Return all users
     *
     * @return User[]
     */
    public function findAllUsers(): array
    {
        $usersRows = $this->dataManager->findAll('user', $this->fields);
        // Convert to list of objects
        return $this->hydrator->hydrate($usersRows, User::class);
    }

    /**
     * Return user with given id if it exists
     * otherwise null
     *
     * @param string $id
     * @return User
     */
    public function findUserById(string $id): User
    {
        $userRows = $this->dataManager->findById('user', $id, $this->fields);
        // Empty user object if not found
        return new User($userRows);
    }

    /**
     * Return user with given id if it exists
     * If there is no user, an empty object is returned because:
     * > It is considered a best practice to NEVER return null when returning a collection or enumerable
     * Source: https://stackoverflow.com/a/1970001/9013718
     *
     * @param string|null $email
     * @return User
     */
    public function findUserByEmail(?string $email): User
    {
        $userRows = $this->dataManager->findOneBy(
            'user',
            'email',
            $email,
            $this->fields
        );
        // Empty user object if not found
        return new User($userRows);
    }

    /**
     * Retrieve user from database
     * If not found error is thrown
     *
     * @param int $id
     * @return array
     * @throws PersistenceRecordNotFoundException
     */
    public function getUserById(int $id): array
    {
        return $this->dataManager->getById('user', $id, $this->fields);
    }

    /**
     * Insert user in database
     *
     * @param array $data
     * @return int lastInsertId
     */
    public function insertUser(array $data): int
    {
        return (int)$this->dataManager->newInsert($data)->into('user')->execute()->lastInsertId();
    }

    /**
     * Delete user from database
     *
     * @param int $id
     * @return bool
     */
    public function deleteUser(int $id): bool
    {
        $query = $this->dataManager->newDelete('user')->where(['id' => $id]);
        return $query->execute()->rowCount() > 0;
    }

    /**
     * Update values from user
     * Example of $data: ['name' => 'New name']
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateUser(array $data, int $id): bool
    {
        $query = $this->dataManager->newQuery()->update('user')->set($data)->where(['id' => $id]);
        return $query->execute()->rowCount() > 0;
    }

    /**
     * Retrieve user role
     *
     * @param int $id
     * @return string
     * @throws PersistenceRecordNotFoundException
     */
    public function getUserRole(int $id): string
    {
        // todo put role in separate tables
        return $this->dataManager->getById('user', $id, ['role'])['role'];
    }

    /**
     * Retrieve user role
     *
     * @param int $id
     * @return bool
     */
    public function userExists(int $id): bool
    {
        return $this->dataManager->exists('user', 'id', $id);
    }

    /**
     * Change user status
     *
     * @param string $status
     * @param string $userId
     * @return bool
     */
    public function changeUserStatus(string $status, string $userId): bool
    {
        $query = $this->dataManager->newQuery()->update('user')->set(['status' => $status])->where(
            ['id' => $userId]
        );
        return $query->execute()->rowCount() > 0;
    }

}
