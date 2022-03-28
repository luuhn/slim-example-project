<?php


namespace App\Domain\Authentication\Service;


use App\Domain\Authentication\Exception\InvalidTokenException;
use App\Infrastructure\Authentication\VerificationToken\VerificationTokenFinderRepository;
use App\Infrastructure\Authentication\VerificationToken\VerificationTokenUpdaterRepository;

final class VerificationTokenChecker
{
    public function __construct(
        private VerificationTokenFinderRepository $verificationTokenFinderRepository,
        private VerificationTokenUpdaterRepository $verificationTokenUpdaterRepository,
    ) {
    }

    /**
     * Most simple form of verifying token and return user id
     *
     * @param int $verificationId
     * @param string $token
     * @return int
     *
     * @throws InvalidTokenException
     */
    public function getUserIdIfTokenIsValid(int $verificationId, string $token): int
    {

        $verification = $this->verificationTokenFinderRepository->findUserVerification($verificationId);

        // Verify given token with token in database
        if (
            ($verification->token !== null) &&
            $verification->usedAt === null &&
            $verification->expiresAt > time() &&
            true === password_verify($token, $verification->token)
        ) {
            // Mark token as being used
            $this->verificationTokenUpdaterRepository->setVerificationEntryToUsed($verificationId);
            return $this->verificationTokenFinderRepository->getUserIdFromVerification($verificationId);
        }

        throw new InvalidTokenException('Not existing, invalid, used or expired token.');
    }
}