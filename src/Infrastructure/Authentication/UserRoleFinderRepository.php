<?php


namespace App\Infrastructure\Authentication;


use App\Common\Hydrator;
use App\Domain\Authorization\UserRoleData;
use App\Infrastructure\Exceptions\PersistenceRecordNotFoundException;
use App\Infrastructure\Factory\QueryFactory;

class UserRoleFinderRepository
{
    public function __construct(
        private QueryFactory $queryFactory
    ) { }

    /**
     * Retrieve user role
     *
     * @param int $userId
     * @return int
     * @throws PersistenceRecordNotFoundException if entry not found
     */
    public function getRoleIdFromUser(int $userId): int
    {
        $query = $this->queryFactory->newQuery()->select(['user_role_id'])->from('user')->where(
            ['deleted_at IS' => null, 'id' => $userId]);
        $roleId = $query->execute()->fetch('assoc')['user_role_id'];
        if (!$roleId){
            throw new PersistenceRecordNotFoundException('user');
        }
        return $roleId;
    }

    public function getUserRoleDataFromUser(int $userId): UserRoleData
    {
        $query = $this->queryFactory->newQuery()
            ->select(['user_role.id', 'user_role.name', 'user_role.hierarchy'])
            ->from('user')
            ->leftJoin('user_role', ['user.user_role_id = user_role.id'])
            ->where(['user.deleted_at IS' => null, 'user.id' => $userId]);
        $roleResultRow = $query->execute()->fetch('assoc');
        $userRoleData = new UserRoleData();
        $userRoleData->id = $roleResultRow['id'];
        $userRoleData->name = $roleResultRow['name'];
        $userRoleData->hierarchy = $roleResultRow['hierarchy'];
        return $userRoleData;
    }


    /**
     * Get user role hierarchies mapped by name
     *
     * @return array{role_name: int}
     */
    public function getUserRolesHierarchies(): array
    {
        $query = $this->queryFactory->newQuery()->select(['id', 'name', 'hierarchy'])->from('user_role');
        $resultRows = $query->execute()->fetchAll('assoc');

        $userRoles = [];
        foreach ($resultRows as $resultRow) {
            $userRoles[$resultRow['name']] = $resultRow['hierarchy'];
        }
        return $userRoles;
    }

}