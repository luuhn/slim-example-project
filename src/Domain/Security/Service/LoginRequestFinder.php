<?php

namespace App\Domain\Security\Service;

use App\Application\Data\UserNetworkSessionData;
use App\Domain\Settings;
use App\Infrastructure\SecurityLogging\LoginLogFinderRepository;

class LoginRequestFinder
{
    private array $securitySettings;

    public function __construct(
        private readonly LoginLogFinderRepository $loginRequestFinderRepository,
        private readonly UserNetworkSessionData $ipAddressData,
        Settings $settings
    ) {
        $this->securitySettings = $settings->get('security');
    }

    /**
     * Retrieve login requests from given email and client ip.
     *
     * @param string $email
     *
     * @return array{
     *     logins_by_email: array{successes: int, logins: int},
     *     logins_by_ip: array{successes: int, logins: int},
     * }
     */
    public function findLoginLogEntriesInTimeLimit(string $email): array
    {
        // Stats concerning given email in last timespan
        return $this->loginRequestFinderRepository->getLoginSummaryFromEmailAndIp(
            $email,
            $this->ipAddressData->ipAddress,
            $this->securitySettings['timespan']
        );
    }

    /**
     * Returns the very last LOGIN request from actual ip or given email.
     *
     * @param string $email
     *
     * @return int
     */
    public function findLatestLoginRequestTimestamp(string $email): int
    {
        $createdAt = $this->loginRequestFinderRepository->findLatestLoginTimestampFromUserOrIp(
            $email,
            $this->ipAddressData->ipAddress,
        );
        if ($createdAt) {
            return (new \DateTime($createdAt))->format('U');
        }

        return 0;
    }
}
