<?php

namespace App\Domain\User\Service;

use App\Application\Data\UserNetworkSessionData;
use App\Domain\User\Data\UserActivityData;
use App\Domain\User\Enum\UserActivity;
use App\Infrastructure\User\UserActivityRepository;
use Odan\Session\SessionInterface;

class UserActivityManager
{
    public function __construct(
        private readonly UserActivityRepository $userActivityRepository,
        private readonly SessionInterface $session,
        private readonly UserNetworkSessionData $userNetworkSessionData,
    ) {
    }

    /**
     * Insert new user activity.
     *
     * @param UserActivity $userActivityAction
     * @param string $table
     * @param int $rowId
     * @param array|null $data
     * @param int|null $userId in case there is no session like on login
     *
     * @return int
     */
    public function addUserActivity(
        UserActivity $userActivityAction,
        string $table,
        int $rowId,
        ?array $data = null,
        ?int $userId = null,
    ): int {
        $userActivity = new UserActivityData();
        $userActivity->ipAddress = $this->userNetworkSessionData->ipAddress;
        $userActivity->userAgent = $this->userNetworkSessionData->userAgent;
        $userActivity->userId = $this->userNetworkSessionData->userId ?? $userId;
        $userActivity->action = $userActivityAction;
        $userActivity->table = $table;
        $userActivity->rowId = $rowId;
        $userActivity->data = $data;

        return $this->userActivityRepository->insertUserActivity($userActivity->toArray());
    }

    /**
     * Delete entry.
     *
     * @param int $activityId
     *
     * @return bool if deleted
     */
    public function deleteUserActivity(int $activityId): bool
    {
        return $this->userActivityRepository->hardDeleteUserActivity($activityId);
    }
}
