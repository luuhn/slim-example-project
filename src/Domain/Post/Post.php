<?php

namespace App\Domain\Post;


use App\Domain\Utility\ArrayReader;

class Post
{
    private ?int $id;
    private ?int $userId;
    private string $message;

    /**
     * Post constructor.
     * @param array|null $postData
     */
    public function __construct(array $postData = null) {
        $arrayReader = new ArrayReader($postData);
        $this->id = $arrayReader->findInt('id');
        $this->userId = $arrayReader->findInt('user_id');
        $this->message = $arrayReader->getString('message');
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

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @param int|null $userId
     */
    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

}