<?phpnamespace App\Domain\Auth;use App\Domain\Auth\Exception\InvalidTokenException;use App\Domain\Auth\Exception\UserAlreadyVerifiedException;use App\Domain\Exceptions\InvalidCredentialsException;use App\Domain\Security\SecurityService;use App\Domain\User\DTO\User;use App\Domain\User\UserService;use App\Domain\User\Service\UserValidator;use App\Domain\Utility\EmailService;use App\Infrastructure\Security\RequestTrackRepository;use App\Infrastructure\User\UserRepository;use App\Infrastructure\User\UserVerificationRepository;/** * Authentication logic * Class AuthService * @package App\Domain\Auth */class AuthService{    public function __construct(        private UserValidator $userValidator,        private UserService $userService,        private EmailService $emailService,        private UserRepository $userRepository,        private UserVerificationRepository $userVerificationRepository,        private SecurityService $securityService    ) {    }    /**     * Checks if user is allowed to login.     * If yes, the user object is returned with id     * If no, an InvalidCredentialsException is thrown     *     * @param array $userData     * @param string|null $captcha user captcha response if filled out     * @return string id     *     */    public function getUserIdIfAllowedToLogin(array $userData, string|null $captcha = null): string    {        $user = new User($userData, true);        // Validate entries coming from client        $this->userValidator->validateUserLogin($user);        // Perform login security check        $this->securityService->performLoginSecurityCheck($user->email, $captcha);        $dbUser = $this->userRepository->findUserByEmail($user->email);        // Check if user already exists        if ($dbUser->email !== null) {            if ($dbUser->status === User::STATUS_UNVERIFIED) {                // todo inform user when he tries to login that account is unverified and he should click on the link in his inbox                // maybe send verification email again and newEmailRequest (not login as its same as register)            } elseif ($dbUser->status === User::STATUS_SUSPENDED) {                // Todo inform user (only via mail) that he is suspended and isn't allowed to create a new account            } elseif ($dbUser->status === User::STATUS_LOCKED) {                // Todo login fail and inform user (only via mail) that he is locked            } elseif ($dbUser->status === User::STATUS_ACTIVE) {                // Check failed login attempts                if (password_verify($user->password, $dbUser->passwordHash)) {                    $this->securityService->newLoginRequest($dbUser->email, $_SERVER['REMOTE_ADDR'], true);                    return $dbUser->id;                }            } else {                // todo invalid role in db. Send email to admin to inform that there is something wrong with the user                throw new \RuntimeException('Invalid status');            }        }        $this->securityService->newLoginRequest($user->email, $_SERVER['REMOTE_ADDR'], false);        // Throw InvalidCred exception if user doesn't exist or wrong password        // Vague exception on purpose for security        throw new InvalidCredentialsException($user->email);    }    /**     * Insert user in database     *     * @param array $userData     * @param string|null $captcha user captcha response if filled out     * @param array $queryParams query params that should be added to email verification link (e.g. redirect)     *     * @return string|bool insert id, false if user already exists     * @throws \PHPMailer\PHPMailer\Exception     */    public function registerUser(array $userData, string|null $captcha = null, array $queryParams = []): bool|string    {        $user = new User($userData, true);        // Validate entries coming from client        $this->userValidator->validateUserRegistration($user);        $this->securityService->performEmailAbuseCheck($user->email, $captcha);        $existingUser = $this->userRepository->findUserByEmail($user->email);        // Check if user already exists        if ($existingUser->email !== null) {            // If unverified and registered again, old user should be deleted and replaced with new input and verification            // Reason: User could have lost the email or someone else tried to register under someone elses name            if ($existingUser->status === User::STATUS_UNVERIFIED) {                // Soft delete user so that new one can be inserted properly                $this->userRepository->deleteUserById($existingUser->id);                $this->userVerificationRepository->deleteVerificationToken($existingUser->id);            } elseif ($existingUser->status === User::STATUS_SUSPENDED) {                // Todo inform user (only via mail) that he is suspended and isn't allowed to create a new account                return false;            } elseif ($existingUser->status === User::STATUS_LOCKED) {                // Todo inform user (only via mail) that he is locked and can't create a new account                return false;            } elseif ($existingUser->status === User::STATUS_ACTIVE) {                try {                    // Send info mail to email address holder                    // Subject asserted in testRegisterUser_alreadyExistingActiveUser                    $this->emailService->setSubject('Someone tried to create an account with your address');                    $this->emailService->setContentFromTemplate(                        'auth/register-on-existing.email.php',                        ['user' => $existingUser]                    );                    $this->emailService->setFrom('slim-example-project@samuel-gfeller.ch', 'Slim Example Project');                    $this->emailService->sendTo($existingUser->email, $existingUser->name);                    $this->securityService->newEmailRequest($existingUser->email, $_SERVER['REMOTE_ADDR']);                } catch (\PHPMailer\PHPMailer\Exception $e) {                    // We try to hide if an email already exists or not so if email fails, nothing is done                } catch (\Throwable $e) { // For phpRenderer ->fetch()                }                return false;            } else {                // todo invalid role in db. Send email to admin to inform that there is something wrong with the user                throw new \RuntimeException('Invalid role');            }        }        $user->passwordHash = password_hash($user->password, PASSWORD_DEFAULT);        // Set default status and role        $user->status = User::STATUS_UNVERIFIED;        $user->role = 'user';        // Insert new user into database        $user->id = $this->userRepository->insertUser($user);        // Create, insert and send token to user        $this->createAndSendUserVerification($user, $queryParams);        $this->securityService->newEmailRequest($user->email, $_SERVER['REMOTE_ADDR']);        return $user->id;    }    /**     * Create and insert verification token     *     * @param User $user WITH id     * @param array $queryParams query params that should be added to email verification link (e.g. redirect)     *     * @return int     * @throws \PHPMailer\PHPMailer\Exception     */    private function createAndSendUserVerification(User $user, array $queryParams = []): int    {        // Create token        $token = random_bytes(50);        // Set token expiration because link automatically logs in        $expires = new \DateTime('now');        $expires->add(new \DateInterval('PT02H')); // 2 hours        // Soft delete any existing tokens for this user        $this->userVerificationRepository->deleteVerificationToken($user->id);        // Insert verification token into database        $tokenId = $this->userVerificationRepository->insertUserVerification(            [                'user_id' => $user->id,                'token' => password_hash($token, PASSWORD_DEFAULT),                // expires format 'U' is the same as time() so it can be used later to compare easily                'expires' => $expires->format('U')            ]        );        // Add relevant query params to $queryParams array        $queryParams['token'] = $token;        $queryParams['id'] = $tokenId;        // Send verification mail        $this->emailService->setSubject('One more step to register'); // Subject asserted in testRegisterUser        $this->emailService->setContentFromTemplate(            'auth/register.email.php',            ['user' => $user, 'queryParams' => $queryParams]        );        $this->emailService->setFrom('slim-example-project@samuel-gfeller.ch', 'Slim Example Project');        $this->emailService->sendTo($user->email, $user->name);        // PHPMailer errors caught in action        return $tokenId;    }    /**     * Verify token     * @param int $verificationId     * @param string $token     * @return bool     */    public function verifyUser(int $verificationId, string $token): bool    {        $verification = $this->userVerificationRepository->findUserVerification($verificationId);        if ($verification->token !== null) {            $userStatus = $this->userRepository->findUserById($verification->userId)->status;            // Check if user is already verified            if (User::STATUS_UNVERIFIED !== $userStatus) {                // User is not unverified anymore, that means that user already clicked on the link                throw new UserAlreadyVerifiedException('User has not status "' . User::STATUS_UNVERIFIED . '"');            } // Verify given token with token in database            elseif ($verification->expires > time() && true === password_verify($token, $verification->token)) {                // Change user status to active                $hasUpdated = $this->userRepository->changeUserStatus(User::STATUS_ACTIVE, $verification->userId);                if ($hasUpdated === true) {                    // Mark token as being used only after making sure that user is active                    $this->userVerificationRepository->setVerificationEntryToUsed($verificationId);                }                return $hasUpdated;            }            // Same exception messages than AuthServiceUserVerificationTest.php            throw new InvalidTokenException('Invalid or expired token.');        }        // If no token was found and user is still unverified, that means that the token is invalid        throw new InvalidTokenException('No token was found for id "' . $verificationId . '".');    }    /**     * @param string $verificationId     */    public function getUserIdFromVerification(string $verificationId): string    {        return $this->userVerificationRepository->getUserIdFromVerification($verificationId);    }    /**     * Get user role     *     * @param int $userId     * @return string     */    public function getUserRoleById(int $userId): string    {        return $this->userRepository->getUserRoleById($userId);    }}