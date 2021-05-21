<?php


namespace App\Domain\Authentication\Service;


use App\Domain\User\DTO\User;
use App\Domain\Utility\EmailService;
use App\Infrastructure\Authentication\VerificationToken\VerificationTokenCreatorRepository;
use App\Infrastructure\Authentication\VerificationToken\VerificationTokenDeleterRepository;

class VerificationTokenCreator
{

    public function __construct(
        private VerificationTokenDeleterRepository $verificationTokenDeleterRepository,
        private EmailService $emailService,
        private VerificationTokenCreatorRepository $verificationTokenCreatorRepository
    )
    {
    }

    /**
     * Create and insert verification token
     *
     * @param User $user WITH id
     * @param array $queryParams query params that should be added to email verification link (e.g. redirect)
     *
     * @return int
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function createAndSendUserVerification(User $user, array $queryParams = []): int
    {
        // Create token
        $token = random_bytes(50);

        // Set token expiration because link automatically logs in
        $expires = new \DateTime('now');
        $expires->add(new \DateInterval('PT02H')); // 2 hours

        // Soft delete any existing tokens for this user
        $this->verificationTokenDeleterRepository->deleteVerificationToken($user->id);

        // Insert verification token into database
        $tokenId = $this->verificationTokenCreatorRepository->insertUserVerification(
            [
                'user_id' => $user->id,
                'token' => password_hash($token, PASSWORD_DEFAULT),
                // expires format 'U' is the same as time() so it can be used later to compare easily
                'expires' => $expires->format('U')
            ]
        );

        // Add relevant query params to $queryParams array
        $queryParams['token'] = $token;
        $queryParams['id'] = $tokenId;

        // Send verification mail
        $this->emailService->setSubject('One more step to register'); // Subject asserted in testRegisterUser
        $this->emailService->setContentFromTemplate(
            'auth/register.email.php',
            ['user' => $user, 'queryParams' => $queryParams]
        );
        $this->emailService->setFrom('slim-example-project@samuel-gfeller.ch', 'Slim Example Project');
        $this->emailService->sendTo($user->email, $user->name);
        // PHPMailer errors caught in action

        return $tokenId;
    }
}