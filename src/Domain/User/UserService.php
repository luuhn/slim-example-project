<?php


namespace App\Domain\User;

use App\Infrastructure\Persistence\Exceptions\PersistenceRecordNotFoundException;

class UserService
{
    
    private $userRepositoryInterface;
    protected $userValidation;
    
    
    public function __construct(UserRepositoryInterface $userRepositoryInterface, UserValidation $userValidation)
    {
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->userValidation = $userValidation;
    }
    
    public function findAllUsers()
    {
        $allUsers = $this->userRepositoryInterface->findAllUsers();
        return $allUsers;
    }
    
    public function findUser(int $id): array
    {
        return $this->userRepositoryInterface->findUserById($id);
    }
    
    public function findUserByEmail($email): array
    {
        return $this->userRepositoryInterface->findUserByEmail($email);
    }
    
    /**
     * Insert user in database
     *
     * @param $user
     * @return string
     */
    public function createUser(User $user): string
    {
//        $this->userValidation->validateUserRegistration($data);
        return $this->userRepositoryInterface->insertUser($user->toArray());
    }
    
    /**
     * @param $id
     * @param $data array Data to update
     * @return bool
     */
    public function updateUser($id, array $data): bool
    {
        $validatedData = [];
        if (isset($data['name'])) {
            $validatedData['name'] = $data['name'];
        }
        if (isset($data['email'])) {
            $validatedData['email'] = $data['email'];
        }
        if (isset($data['password1'], $data['password2'])) {
            // passwords are already identical since they were validated in UserValidation.php
            $validatedData['password'] = password_hash($data['password1'], PASSWORD_DEFAULT);
        }
        
        
        return $this->userRepositoryInterface->updateuser($validatedData, $id);
    }
    
    public function deleteUser($id): bool
    {
        return $this->userRepositoryInterface->deleteUser($id);
    }
    
    /**
     * Get user role
     *
     * @param int $id
     * @return string
     */
    public function getUserRole(int $id): string
    {
        return $this->userRepositoryInterface->getUserRole($id);
    }
    
}
