<?php

namespace App\Domain\Post\Data;


use App\Common\ArrayReader;
use App\Domain\User\Data\UserData;

class PostData
{
    public ?int $id;
    public ?int $userId;
    public ?string $message;
    public ?string $createdAt;
    public ?string $updatedAt;
    public ?string $deletedAt;
    public ?UserData $user;

    /**
     * Post constructor.
     * @param array|null $postData
     */
    public function __construct(array $postData = null) {
        $arrayReader = new ArrayReader($postData);
        $this->id = $arrayReader->findAsInt('id');
        $this->userId = $arrayReader->findAsInt('user_id');
        $this->message = $arrayReader->findAsString('message');
        $this->createdAt = $arrayReader->findAsString('created_at');
        $this->updatedAt = $arrayReader->findAsString('updated_at');
        $this->deletedAt = $arrayReader->findAsString('deleted_at');
    }

    /**
     * Returns all values of object as array.
     * The array keys should match with the database
     * column names since it is likely used to
     * modify a database table
     *
     * @return array
     */
    public function toArray(): array
    {
        // Not include required, from db non nullable values if they are null -> for update
        if($this->id !== null){ $post['id'] = $this->id;}
        if($this->userId !== null){ $post['user_id'] = $this->userId;}

        // Message is nullable and null is a valid value so it has to be included todo detect null values and add IS for cakequery builder IS NULL
        $post['message'] = $this->message;

        return $post;
    }
}