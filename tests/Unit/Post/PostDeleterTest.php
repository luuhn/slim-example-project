<?php

namespace App\Test\Unit\Post;

use App\Domain\Post\Service\PostDeleter;
use App\Infrastructure\Post\PostDeleterRepository;
use App\Test\Traits\AppTestTrait;
use PHPUnit\Framework\TestCase;

class PostDeleterTest extends TestCase
{
    use AppTestTrait;

    /**
     * Test that PostDeleterRepository:deletePost() is called in
     * post service
     */
    public function testDeletePost(): void
    {
        $postId = 1;

        $this->mock(PostDeleterRepository::class)
            ->expects(self::once())
            ->method('deletePost')
            // With parameter user id
            ->with(self::equalTo($postId))
            ->willReturn(true);

        /** @var PostDeleter $service */
        $service = $this->container->get(PostDeleter::class);

        self::assertTrue($service->deletePost($postId));
    }
}
