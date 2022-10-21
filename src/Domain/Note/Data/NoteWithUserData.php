<?php


namespace App\Domain\Note\Data;

use App\Common\ArrayReader;
use App\Domain\User\Data\MutationRights;

/**
 * Note with user info
 */
class NoteWithUserData
{
    public ?int $noteId;
    public ?string $noteMessage;
    public ?string $noteCreatedAt;
    public ?string $noteUpdatedAt;
    public ?int $userId;
    public ?string $userFullName;
    public ?int $userRoleId;

    // Not note value from db, populated in NoteUserRightSetter
    public ?MutationRights $mutationRights; // json_encode automatically takes $enum->value

    /**
     * Note constructor.
     * @param array $noteData
     */
    public function __construct(array $noteData = [])
    {
        $arrayReader = new ArrayReader($noteData);
        $this->noteId = $arrayReader->findAsInt('note_id');
        $this->userId = $arrayReader->findAsInt('user_id');
        $this->noteMessage = $arrayReader->findAsString('note_message');
        $this->noteCreatedAt = $arrayReader->findAsString('note_created_at');
        $this->noteUpdatedAt = $arrayReader->findAsString('note_updated_at');
        $this->userFullName = $arrayReader->findAsString('user_full_name');
        $this->userRoleId = $arrayReader->findAsString('user_role_id');
    }
}