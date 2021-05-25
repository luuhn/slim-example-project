<?php

namespace App\Test\Unit\Domain\Post;

use App\Domain\Post\DTO\Post;
use App\Domain\Post\DTO\UserPost;
use App\Domain\Post\Service\PostFinder;
use App\Domain\User\DTO\User;
use App\Domain\User\Service\UserFinder;
use App\Infrastructure\Post\PostFinderRepository;
use App\Test\Traits\AppTestTrait;
use PHPUnit\Framework\TestCase;

class PostFinderTest extends TestCase
{
    use AppTestTrait;

    /**
     * Test function findAllPostsWithUsers from PostFinder which returns
     * Post array including the the associated user
     *
     * @dataProvider \App\Test\Provider\PostProvider::oneSetOfMultipleUserPostsProvider()
     * @param UserPost[] $userPosts
     */
    public function testFindAllPosts(array $userPosts): void
    {
        // Add mock class PostFinderRepository to container and define return value for method findAllPostsWithUsers
        $this->mock(PostFinderRepository::class)->method('findAllPostsWithUsers')->willReturn($userPosts);


        // Here we don't need to specify what the service function will do / return since its exactly that
        // which is being tested. So we can take the autowired class instance from the container directly.
        /** @var PostFinder $service */
        $service = $this->container->get(PostFinder::class);

        self::assertEquals($userPosts, $service->findAllPostsWithUsers());

    }

    /**
     * Check if findPost() from PostFinder returns
     * the post coming from the repository
     *
     * @dataProvider \App\Test\Provider\PostProvider::onePostProvider()
     * @param Post $post
     */
    public function testFindPost(Post $post): void
    {
        // Add mock class PostFinderRepository to container and define return value for method findPostById
        // I dont see the necessity of expecting method to be called. If we get the result we want
        // we can let the code free how it returns it (don't want annoying test that fails after slight code change)
        $this->mock(PostFinderRepository::class)->method('findPostById')->willReturn($post);

        // Get an empty class instance from container
        /** @var PostFinder $service */
        $service = $this->container->get(PostFinder::class);

        self::assertEquals($post, $service->findPost($post->id));
    }

    /**
     * Check if findAllPostsFromUser() from PostFinder returns
     * the posts coming from the repository AND
     * if the user names are contained in the returned array
     *
     * @dataProvider \App\Test\Provider\PostProvider::oneSetOfMultipleUserPostsProvider()
     * @param UserPost[] $userPosts
     */
    public function testFindAllPostsFromUser(array $userPosts): void
    {
        // Add mock class PostFinderRepository to container and define return value for method findPostById
        // Posts are with different user_ids from provider and logically findAllPostsFromUser has to return
        // posts with the same user_id since they belong to the same user. But this is not the point of the test.
        // The same posts array will be used in the assertions
        $this->mock(PostFinderRepository::class)->method('findAllPostsByUserId')->willReturn($userPosts);

        /** @var PostFinder $service */
        $service = $this->container->get(PostFinder::class);

        // User id not relevant because return values from repo is defined above
        self::assertEquals($userPosts, $service->findAllPostsFromUser(1));
    }
}
