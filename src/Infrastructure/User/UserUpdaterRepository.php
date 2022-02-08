<?php


namespace App\Infrastructure\User;


use App\Infrastructure\Factory\QueryFactory;

class UserUpdaterRepository
{
    public function __construct(
        private QueryFactory $queryFactory
    ) { }

    /**
     * Update values from user
     * Example of $data: ['firstName' => 'NewFirstName']
     *
     * @param int $userId
     * @param array $userValues has to be only allowed changes for this function
     * @return bool
     */
    public function updateUser(int $userId, array $userValues): bool
    {
        $query = $this->queryFactory->newQuery()->update('user')->set($userValues)->where(['id' => $userId]);
        return $query->execute()->rowCount() > 0;
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
        $query = $this->queryFactory->newQuery()->update('user')->set(['status' => $status])->where(
            ['id' => $userId]
        );
        return $query->execute()->rowCount() > 0;
    }
}