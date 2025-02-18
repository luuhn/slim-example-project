<?php

namespace App\Infrastructure\SecurityLogging;

use App\Infrastructure\Factory\QueryFactory;

class EmailLoggerRepository
{
    public function __construct(
        private readonly QueryFactory $queryFactory
    ) {
    }

    /**
     * Insert new request where an email was sent.
     *
     * @param string $fromEmail
     * @param string $toEmail
     * @param string $subject
     * @param int|string|null $userId
     *
     * @return string
     */
    public function logEmailRequest(
        string $fromEmail,
        string $toEmail,
        string $subject,
        int|string|null $userId
    ): string {
        $query = $this->queryFactory->insertQuery();

        return $query->insert(['id', 'user_id', 'from_email', 'to_email', 'other_recipient', 'subject', 'created_at'])
            ->into('email_log')->values([
                'user_id' => $userId,
                'from_email' => $fromEmail,
                'to_email' => $toEmail,
                'other_recipient' => null,
                'subject' => $subject,
                // Set time in PHP to be sure that time matches php timezone
                'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            ])->execute()->lastInsertId();
    }
}
