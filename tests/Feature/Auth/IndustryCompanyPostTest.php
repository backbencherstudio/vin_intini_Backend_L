<?php

namespace Tests\Feature\Auth;

use App\Models\Comment;
use App\Models\CommentLike;
use App\Models\Industry;
use App\Models\IndustryFollow;
use App\Models\Plan;
use App\Models\Post;
use App\Models\PostIndustry;
use App\Models\PostLike;
use App\Models\PostMedia;
use App\Models\Reply;
use App\Models\ReplyLike;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class IndustryCompanyPostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_returns_401_when_post_is_created_without_authentication(): void
    {
        $this->postJson('/api/industry/post/create', [
            'content' => 'Hello',
            'visibility' => 'public',
        ])->assertUnauthorized();
    }

    public function test_valid_payload_creates_company_post_and_returns_201(): void
    {
        $owner = $this->makeUser();
        $industry = $this->makeIndustry($owner);

        $response = $this->actingAs($owner, 'api')->postJson('/api/industry/post/create', [
            'content' => 'We are hiring across three teams.',
            'visibility' => 'public',
            'media' => [UploadedFile::fake()->image('banner.jpg')],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.company_id', $industry->id)
            ->assertJsonPath('data.created_by', $owner->id)
            ->assertJsonPath('data.content', 'We are hiring across three teams.')
            ->assertJsonPath('data.visibility', 'public')
            ->assertJsonCount(1, 'data.media');

        $post = Post::sole();

        $this->assertTrue($post->isCompanyPost());
        $this->assertSame(1, PostIndustry::count());
        $this->assertSame(1, PostMedia::count());
        $this->assertSame('image', PostMedia::sole()->type);
        Storage::disk('public')->assertExists(PostMedia::sole()->file_path);
    }

    public function test_company_post_is_stored_on_the_shared_posts_table(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);

        $this->actingAs($owner, 'api')->postJson('/api/industry/post/create', [
            'content' => 'Shared table post',
            'visibility' => 'public',
        ])->assertCreated();

        $post = Post::sole();

        $this->assertSame($owner->id, $post->user_id);
        $this->assertTrue($post->isCompanyPost());
    }

    public function test_returns_403_when_user_has_no_company_page(): void
    {
        $owner = $this->makeUser();
        $this->subscribe($owner);

        $this->actingAs($owner, 'api')->postJson('/api/industry/post/create', [
            'content' => 'No company yet',
            'visibility' => 'public',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'You do not have a company page.');

        $this->assertSame(0, Post::count());
    }

    public function test_returns_403_when_subscription_is_inactive(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);
        $this->subscribe($owner, ['status' => 'canceled']);

        $this->actingAs($owner, 'api')->postJson('/api/industry/post/create', [
            'content' => 'Expired plan',
            'visibility' => 'public',
        ])
            ->assertForbidden()
            ->assertJsonPath(
                'message',
                'Your subscription is not active. Please renew your subscription to create a post.'
            );

        $this->assertSame(0, Post::count());
    }

    public function test_returns_422_when_visibility_is_missing(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);

        $this->actingAs($owner, 'api')->postJson('/api/industry/post/create', [
            'content' => 'No visibility',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('visibility');

        $this->assertSame(0, Post::count());
    }

    public function test_returns_422_when_visibility_is_not_a_company_visibility(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);

        $this->actingAs($owner, 'api')->postJson('/api/industry/post/create', [
            'content' => 'Groups visibility is not allowed',
            'visibility' => 'groups',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('visibility');

        $this->assertSame(0, Post::count());
    }

    public function test_returns_422_when_post_has_neither_text_nor_media(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);

        $this->actingAs($owner, 'api')->postJson('/api/industry/post/create', [
            'content' => '   ',
            'visibility' => 'public',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Post must contain text or media.');

        $this->assertSame(0, Post::count());
    }

    public function test_returns_422_when_more_than_one_video_is_uploaded(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);

        $this->actingAs($owner, 'api')->postJson('/api/industry/post/create', [
            'content' => 'Two videos',
            'visibility' => 'public',
            'media' => [
                UploadedFile::fake()->create('a.mp4', 10, 'video/mp4'),
                UploadedFile::fake()->create('b.mp4', 10, 'video/mp4'),
            ],
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'You can upload a maximum of 1 video per post.');

        $this->assertSame(0, Post::count());
    }

    public function test_owner_can_update_company_post_content_and_visibility(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);

        $postId = $this->createPost($owner, 'Original', 'public');

        $this->actingAs($owner, 'api')
            ->postJson("/api/industry/post/update/{$postId}", [
                'content' => 'Revised copy',
                'visibility' => 'followers',
            ])
            ->assertOk()
            ->assertJsonPath('data.content', 'Revised copy')
            ->assertJsonPath('data.visibility', 'followers');

        $post = Post::sole();

        $this->assertSame('Revised copy', $post->description);
        $this->assertSame('followers', $post->visibility);
    }

    public function test_replacing_media_deletes_the_previous_file(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);

        $postId = $this->createPost($owner, 'With media', 'public', [
            UploadedFile::fake()->image('old.jpg'),
        ]);

        $oldPath = PostMedia::sole()->file_path;

        $this->actingAs($owner, 'api')
            ->postJson("/api/industry/post/update/{$postId}", [
                'media' => [UploadedFile::fake()->image('new.jpg')],
            ])
            ->assertOk();

        $this->assertSame(1, PostMedia::count());
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists(PostMedia::sole()->file_path);
    }

    public function test_returns_422_when_update_payload_is_empty(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);

        $postId = $this->createPost($owner, 'Original', 'public');

        $this->actingAs($owner, 'api')
            ->postJson("/api/industry/post/update/{$postId}", [])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Nothing to update.');
    }

    public function test_returns_403_when_non_owner_updates_another_users_company_post(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $this->makeIndustry($owner);

        $postId = $this->createPost($owner, 'Not yours', 'public');

        $this->actingAs($other, 'api')
            ->postJson("/api/industry/post/update/{$postId}", ['content' => 'Hijacked'])
            ->assertForbidden()
            ->assertJsonPath('message', 'You are not allowed to edit this post.');

        $this->assertSame('Not yours', Post::sole()->description);
    }

    public function test_company_post_endpoints_ignore_regular_user_posts(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();

        $regularPost = Post::create([
            'user_id' => $stranger->id,
            'description' => 'A regular user post',
            'visibility' => 'public',
        ]);

        $url = "/api/industry/post/update/{$regularPost->id}";

        $this->actingAs($owner, 'api')
            ->postJson($url, ['content' => 'Should not apply'])
            ->assertNotFound();

        $this->actingAs($owner, 'api')
            ->deleteJson("/api/industry/post/delete/{$regularPost->id}")
            ->assertNotFound();

        $this->actingAs($owner, 'api')
            ->postJson("/api/industry/post/like/{$regularPost->id}")
            ->assertNotFound();

        $this->actingAs($owner, 'api')
            ->getJson("/api/industry/post/likes/{$regularPost->id}")
            ->assertNotFound();

        $this->actingAs($owner, 'api')
            ->postJson("/api/industry/post/comment/{$regularPost->id}", ['comment' => 'Hi'])
            ->assertNotFound();

        $this->actingAs($owner, 'api')
            ->getJson("/api/industry/post/comments/{$regularPost->id}")
            ->assertNotFound();

        $this->assertSame('A regular user post', $regularPost->fresh()->description);
        $this->assertSame(0, Comment::count());
    }

    public function test_comment_endpoints_ignore_comments_on_regular_user_posts(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();

        $regularPost = Post::create([
            'user_id' => $stranger->id,
            'description' => 'Regular post',
            'visibility' => 'public',
        ]);

        $comment = Comment::create([
            'post_id' => $regularPost->id,
            'user_id' => $stranger->id,
            'comment' => 'Original comment',
        ]);

        $this->actingAs($owner, 'api')
            ->postJson("/api/industry/post/comment/like/{$comment->id}")
            ->assertNotFound();

        $this->actingAs($owner, 'api')
            ->getJson("/api/industry/post/comment/likes/{$comment->id}")
            ->assertNotFound();

        $this->actingAs($owner, 'api')
            ->postJson("/api/industry/post/comment/reply/{$comment->id}", ['comment' => 'Reply'])
            ->assertNotFound();

        $this->actingAs($owner, 'api')
            ->getJson("/api/industry/post/comment/replies/{$comment->id}")
            ->assertNotFound();

        $this->actingAs($owner, 'api')
            ->deleteJson("/api/industry/post/comment/{$comment->id}")
            ->assertNotFound();

        $this->assertSame(0, CommentLike::count());
        $this->assertSame(0, Reply::count());
        $this->assertModelExists($comment);
    }

    public function test_reply_endpoints_ignore_replies_on_regular_user_posts(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();

        $regularPost = Post::create([
            'user_id' => $stranger->id,
            'description' => 'Regular post',
            'visibility' => 'public',
        ]);

        $comment = Comment::create([
            'post_id' => $regularPost->id,
            'user_id' => $stranger->id,
            'comment' => 'Original comment',
        ]);

        $reply = Reply::create([
            'post_id' => $regularPost->id,
            'comment_id' => $comment->id,
            'user_id' => $stranger->id,
            'reply' => 'Original reply',
        ]);

        $this->actingAs($owner, 'api')
            ->postJson("/api/industry/post/comment/reply/like/{$reply->id}")
            ->assertNotFound();

        $this->actingAs($owner, 'api')
            ->getJson("/api/industry/post/comment/reply/likes/{$reply->id}")
            ->assertNotFound();

        $this->assertSame(0, ReplyLike::count());
    }

    public function test_owner_can_delete_company_post_and_its_dependents(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);

        $postId = $this->createPost($owner, 'Doomed', 'public', [
            UploadedFile::fake()->image('doomed.jpg'),
        ]);

        $mediaPath = PostMedia::sole()->file_path;

        $commentId = $this->createComment($owner, $postId, 'A comment');
        $this->createReply($owner, $postId, $commentId, 'A reply');

        $this->actingAs($owner, 'api')
            ->deleteJson("/api/industry/post/delete/{$postId}")
            ->assertOk()
            ->assertJsonPath('data.post_id', $postId);

        $this->assertSame(0, Post::count());
        $this->assertSame(0, PostMedia::count());
        $this->assertSame(0, Comment::count());
        $this->assertSame(0, Reply::count());
        Storage::disk('public')->assertMissing($mediaPath);
    }

    public function test_returns_403_when_non_owner_deletes_another_users_company_post(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $this->makeIndustry($owner);

        $postId = $this->createPost($owner, 'Protected', 'public');

        $this->actingAs($other, 'api')
            ->deleteJson("/api/industry/post/delete/{$postId}")
            ->assertForbidden()
            ->assertJsonPath('message', 'You are not allowed to delete this post.');

        $this->assertSame(1, Post::count());
    }

    public function test_public_company_post_is_visible_to_anyone(): void
    {
        $owner = $this->makeUser();
        $industry = $this->makeIndustry($owner);
        $viewer = $this->makeUser();

        $this->createPost($owner, 'Public news', 'public');

        $this->actingAs($viewer, 'api')
            ->getJson("/api/industry/post/view/{$industry->id}")
            ->assertOk()
            ->assertJsonPath('data.0.content', 'Public news');
    }

    public function test_followers_only_company_post_is_hidden_from_non_followers(): void
    {
        $owner = $this->makeUser();
        $industry = $this->makeIndustry($owner);

        $follower = $this->makeUser();
        $stranger = $this->makeUser();

        IndustryFollow::create([
            'industry_id' => $industry->id,
            'user_id' => $follower->id,
        ]);

        $this->createPost($owner, 'Followers only', 'followers');

        $this->actingAs($follower, 'api')
            ->getJson("/api/industry/post/view/{$industry->id}")
            ->assertOk()
            ->assertJsonPath('data.0.content', 'Followers only');

        $this->actingAs($stranger, 'api')
            ->getJson("/api/industry/post/view/{$industry->id}")
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_private_company_post_is_visible_only_to_its_author(): void
    {
        $owner = $this->makeUser();
        $industry = $this->makeIndustry($owner);
        $follower = $this->makeUser();

        IndustryFollow::create([
            'industry_id' => $industry->id,
            'user_id' => $follower->id,
        ]);

        $this->createPost($owner, 'Private note', 'private');

        $this->actingAs($owner, 'api')
            ->getJson("/api/industry/post/view/{$industry->id}")
            ->assertOk()
            ->assertJsonPath('data.0.content', 'Private note');

        $this->actingAs($follower, 'api')
            ->getJson("/api/industry/post/view/{$industry->id}")
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_returns_403_when_user_likes_a_post_they_cannot_view(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);
        $stranger = $this->makeUser();

        $postId = $this->createPost($owner, 'Followers only', 'followers');

        $this->actingAs($stranger, 'api')
            ->postJson("/api/industry/post/like/{$postId}")
            ->assertForbidden()
            ->assertJsonPath('message', 'You are not allowed to like this post.');

        $this->assertSame(0, PostLike::count());
    }

    public function test_post_like_toggles_on_and_off(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);

        $postId = $this->createPost($owner, 'Likeable', 'public');

        $this->actingAs($owner, 'api')
            ->postJson("/api/industry/post/like/{$postId}")
            ->assertOk()
            ->assertJsonPath('data.liked', true)
            ->assertJsonPath('data.likes_count', 1);

        $this->assertSame(1, PostLike::count());

        $this->actingAs($owner, 'api')
            ->postJson("/api/industry/post/like/{$postId}")
            ->assertOk()
            ->assertJsonPath('data.liked', false)
            ->assertJsonPath('data.likes_count', 0);

        $this->assertSame(0, PostLike::count());
        $this->assertSame(0, (int) Post::sole()->total_like);
    }

    public function test_like_list_returns_the_users_who_liked_the_post(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);
        $liker = $this->makeUser('Ada', 'Lovelace');

        $postId = $this->createPost($owner, 'Likeable', 'public');

        $this->actingAs($liker, 'api')->postJson("/api/industry/post/like/{$postId}")
            ->assertOk();

        $this->actingAs($owner, 'api')
            ->getJson("/api/industry/post/likes/{$postId}")
            ->assertOk()
            ->assertJsonPath('data.0.id', $liker->id)
            ->assertJsonPath('data.0.name', 'Ada Lovelace');
    }

    public function test_comment_creation_increments_total_comment(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);

        $postId = $this->createPost($owner, 'Discuss', 'public');

        $this->actingAs($owner, 'api')
            ->postJson("/api/industry/post/comment/{$postId}", ['comment' => 'Nice update'])
            ->assertCreated()
            ->assertJsonPath('data.comment', 'Nice update')
            ->assertJsonPath('data.likes_count', 0);

        $this->assertSame(1, Comment::count());
        $this->assertSame(1, (int) Post::sole()->total_comment);
    }

    public function test_returns_422_when_comment_has_neither_text_nor_image(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);

        $postId = $this->createPost($owner, 'Discuss', 'public');

        $this->actingAs($owner, 'api')
            ->postJson("/api/industry/post/comment/{$postId}", ['comment' => '  '])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Comment must contain text or image.');

        $this->assertSame(0, Comment::count());
    }

    public function test_returns_403_when_user_comments_on_a_post_they_cannot_view(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);
        $stranger = $this->makeUser();

        $postId = $this->createPost($owner, 'Followers only', 'followers');

        $this->actingAs($stranger, 'api')
            ->postJson("/api/industry/post/comment/{$postId}", ['comment' => 'Intruding'])
            ->assertForbidden()
            ->assertJsonPath('message', 'You are not allowed to comment on this post.');

        $this->assertSame(0, Comment::count());
    }

    public function test_comment_list_returns_at_most_three_replies_per_comment(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);

        $postId = $this->createPost($owner, 'Busy thread', 'public');
        $commentId = $this->createComment($owner, $postId, 'Thread');

        for ($index = 1; $index <= 5; $index++) {
            $this->createReply($owner, $postId, $commentId, "Reply {$index}");
        }

        $this->actingAs($owner, 'api')
            ->getJson("/api/industry/post/comments/{$postId}")
            ->assertOk()
            ->assertJsonPath('data.0.comment', 'Thread')
            ->assertJsonPath('data.0.replies_count', 5)
            ->assertJsonCount(3, 'data.0.replies');

        $this->assertSame(5, Reply::count());
    }

    public function test_reply_list_returns_every_reply_for_the_comment(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);

        $postId = $this->createPost($owner, 'Busy thread', 'public');
        $commentId = $this->createComment($owner, $postId, 'Thread');

        for ($index = 1; $index <= 5; $index++) {
            $this->createReply($owner, $postId, $commentId, "Reply {$index}");
        }

        $this->actingAs($owner, 'api')
            ->getJson("/api/industry/post/comment/replies/{$commentId}")
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('pagination.total', 5);
    }

    public function test_reply_creation_increments_reply_count_and_total_comment(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);

        $postId = $this->createPost($owner, 'Discuss', 'public');
        $commentId = $this->createComment($owner, $postId, 'Thread');

        $this->actingAs($owner, 'api')
            ->postJson("/api/industry/post/comment/reply/{$commentId}", ['comment' => 'Agreed'])
            ->assertCreated()
            ->assertJsonPath('data.comment', 'Agreed')
            ->assertJsonPath('data.comment_id', $commentId);

        $this->assertSame(1, Reply::count());
        $this->assertSame(1, (int) Comment::sole()->reply_count);
        $this->assertSame(2, (int) Post::sole()->total_comment);
    }

    public function test_comment_like_toggles_on_and_off(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);

        $postId = $this->createPost($owner, 'Discuss', 'public');
        $commentId = $this->createComment($owner, $postId, 'Thread');

        $this->actingAs($owner, 'api')
            ->postJson("/api/industry/post/comment/like/{$commentId}")
            ->assertOk()
            ->assertJsonPath('data.liked', true)
            ->assertJsonPath('data.likes_count', 1);

        $this->actingAs($owner, 'api')
            ->postJson("/api/industry/post/comment/like/{$commentId}")
            ->assertOk()
            ->assertJsonPath('data.liked', false)
            ->assertJsonPath('data.likes_count', 0);

        $this->assertSame(0, CommentLike::count());
    }

    public function test_reply_like_toggles_on_and_off(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);

        $postId = $this->createPost($owner, 'Discuss', 'public');
        $commentId = $this->createComment($owner, $postId, 'Thread');
        $replyId = $this->createReply($owner, $postId, $commentId, 'Agreed');

        $this->actingAs($owner, 'api')
            ->postJson("/api/industry/post/comment/reply/like/{$replyId}")
            ->assertOk()
            ->assertJsonPath('data.liked', true)
            ->assertJsonPath('data.likes_count', 1);

        $this->actingAs($owner, 'api')
            ->postJson("/api/industry/post/comment/reply/like/{$replyId}")
            ->assertOk()
            ->assertJsonPath('data.liked', false)
            ->assertJsonPath('data.likes_count', 0);

        $this->assertSame(0, ReplyLike::count());
    }

    public function test_comment_author_can_delete_their_own_comment_with_its_replies(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);
        $commenter = $this->makeUser();

        $postId = $this->createPost($owner, 'Discuss', 'public');

        $this->actingAs($commenter, 'api')
            ->postJson("/api/industry/post/comment/{$postId}", ['comment' => 'Mine to delete'])
            ->assertCreated();

        $comment = Comment::sole();
        $this->createReply($commenter, $postId, $comment->id, 'My reply');

        $this->actingAs($commenter, 'api')
            ->deleteJson("/api/industry/post/comment/{$comment->id}")
            ->assertOk()
            ->assertJsonPath('data.deleted_count', 2)
            ->assertJsonPath('data.comments_count', 0);

        $this->assertSame(0, Comment::count());
        $this->assertSame(0, Reply::count());
        $this->assertSame(0, (int) Post::sole()->total_comment);
    }

    public function test_returns_403_when_a_third_party_deletes_someone_elses_comment(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);
        $commenter = $this->makeUser();
        $intruder = $this->makeUser();

        $postId = $this->createPost($owner, 'Discuss', 'public');

        $this->actingAs($commenter, 'api')
            ->postJson("/api/industry/post/comment/{$postId}", ['comment' => 'Not yours'])
            ->assertCreated();

        $this->actingAs($intruder, 'api')
            ->deleteJson('/api/industry/post/comment/'.Comment::sole()->id)
            ->assertForbidden()
            ->assertJsonPath('message', 'You are not allowed to delete this comment.');

        $this->assertSame(1, Comment::count());
    }

    public function test_deleting_company_page_removes_its_posts_follows_and_files(): void
    {
        $owner = $this->makeUser();
        $industry = $this->makeIndustry($owner);
        $follower = $this->makeUser();

        IndustryFollow::create([
            'industry_id' => $industry->id,
            'user_id' => $follower->id,
        ]);

        $postId = $this->createPost($owner, 'Doomed with the page', 'public', [
            UploadedFile::fake()->image('page.jpg'),
        ]);

        $mediaPath = PostMedia::sole()->file_path;
        $commentId = $this->createComment($owner, $postId, 'Doomed comment');
        $this->createReply($owner, $postId, $commentId, 'Doomed reply');

        $this->actingAs($owner, 'api')
            ->deleteJson('/api/industry/delete')
            ->assertOk()
            ->assertJsonPath('data.company_id', $industry->id)
            ->assertJsonPath('data.deleted_posts', 1)
            ->assertJsonPath('data.deleted_post_media', 1)
            ->assertJsonPath('data.deleted_comment_images', 0);

        $this->assertSame(0, Post::count());
        $this->assertSame(0, PostMedia::count());
        $this->assertSame(0, Comment::count());
        $this->assertSame(0, Reply::count());
        $this->assertSame(0, IndustryFollow::count());
        Storage::disk('public')->assertMissing($mediaPath);
        $this->assertSoftDeleted('industries', ['id' => $industry->id]);
    }

    public function test_deleting_company_page_keeps_regular_user_posts(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);

        $regularPost = Post::create([
            'user_id' => $owner->id,
            'description' => 'My personal post',
            'visibility' => 'public',
        ]);

        $this->actingAs($owner, 'api')
            ->deleteJson('/api/industry/delete')
            ->assertOk();

        $this->assertModelExists($regularPost);
    }

    public function test_latest_posts_returns_at_most_five_posts_for_the_owner(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);

        for ($index = 1; $index <= 7; $index++) {
            $this->createPost($owner, "Post {$index}", 'public');
        }

        $this->actingAs($owner, 'api')
            ->getJson('/api/industry/post/recent')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('data.0.content', 'Post 7');
    }

    public function test_public_company_post_appears_in_the_newsfeed_of_any_user(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);
        $viewer = $this->makeUser();

        $this->createPost($owner, 'Company announcement', 'public');

        $this->actingAs($viewer, 'api')
            ->getJson('/api/newsfeed')
            ->assertOk()
            ->assertJsonPath('data.0.description', 'Company announcement')
            ->assertJsonPath('data.0.industry.id', Industry::where('created_by', $owner->id)->value('id'));
    }

    public function test_followers_only_company_post_is_absent_from_the_newsfeed_of_non_followers(): void
    {
        $owner = $this->makeUser();
        $this->makeIndustry($owner);
        $stranger = $this->makeUser();
        $follower = $this->makeUser();

        $this->createPost($owner, 'Members only', 'followers');

        $this->actingAs($stranger, 'api')
            ->getJson('/api/newsfeed')
            ->assertOk()
            ->assertJsonPath('data', []);

        IndustryFollow::create([
            'industry_id' => Industry::where('created_by', $owner->id)->value('id'),
            'user_id' => $follower->id,
        ]);

        $this->actingAs($follower, 'api')
            ->getJson('/api/newsfeed')
            ->assertOk()
            ->assertJsonPath('data.0.description', 'Members only');
    }

    public function test_private_company_post_is_absent_from_the_newsfeed_of_followers(): void
    {
        $owner = $this->makeUser();
        $industry = $this->makeIndustry($owner);
        $follower = $this->makeUser();

        IndustryFollow::create([
            'industry_id' => $industry->id,
            'user_id' => $follower->id,
        ]);

        $this->createPost($owner, 'Internal note', 'private');

        $this->actingAs($follower, 'api')
            ->getJson('/api/newsfeed')
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_owner_cannot_follow_their_own_company_page(): void
    {
        $owner = $this->makeUser();
        $industry = $this->makeIndustry($owner);

        $this->actingAs($owner, 'api')
            ->postJson("/api/industry/follow/{$industry->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'You cannot follow your own company page.');

        $this->assertSame(0, IndustryFollow::count());
    }

    public function test_following_a_company_page_toggles(): void
    {
        $owner = $this->makeUser();
        $industry = $this->makeIndustry($owner);
        $follower = $this->makeUser();

        $this->actingAs($follower, 'api')
            ->postJson("/api/industry/follow/{$industry->id}")
            ->assertOk()
            ->assertJsonPath('data.is_following', true)
            ->assertJsonPath('data.followers_count', 1);

        $this->actingAs($follower, 'api')
            ->postJson("/api/industry/follow/{$industry->id}")
            ->assertOk()
            ->assertJsonPath('data.is_following', false)
            ->assertJsonPath('data.followers_count', 0);

        $this->assertSame(0, IndustryFollow::count());
    }

    public function test_generic_like_endpoint_rejects_a_private_company_post(): void
    {
        $owner = $this->makeUser('Private', 'Liker');
        $this->makeIndustry($owner);
        $postId = $this->createPost($owner, 'Private company post', 'private');

        $intruder = $this->makeUser('Nosy', 'Stranger');

        $this->actingAs($intruder, 'api')
            ->postJson("/api/toggle-like/{$postId}")
            ->assertForbidden();

        $this->assertSame(0, PostLike::where('post_id', $postId)->count());
    }

    public function test_generic_like_endpoint_rejects_a_followers_only_company_post(): void
    {
        $owner = $this->makeUser('Followers', 'Only');
        $this->makeIndustry($owner);
        $postId = $this->createPost($owner, 'Followers only company post', 'followers');

        $intruder = $this->makeUser('Stranger', 'Again');

        $this->actingAs($intruder, 'api')
            ->postJson("/api/toggle-like/{$postId}")
            ->assertForbidden();

        $this->assertSame(0, PostLike::where('post_id', $postId)->count());
    }

    public function test_generic_like_endpoint_allows_a_follower_to_like_a_followers_only_company_post(): void
    {
        $owner = $this->makeUser('Followers', 'Friendly');
        $industry = $this->makeIndustry($owner);
        $postId = $this->createPost($owner, 'Followers only company post', 'followers');

        $follower = $this->makeUser('Admirer', 'Fan');

        $this->actingAs($follower, 'api')
            ->postJson("/api/industry/follow/{$industry->id}")
            ->assertOk();

        $this->actingAs($follower, 'api')
            ->postJson("/api/toggle-like/{$postId}")
            ->assertOk();

        $this->assertSame(1, PostLike::where('post_id', $postId)->count());
    }

    public function test_generic_comment_endpoint_rejects_a_private_company_post(): void
    {
        $owner = $this->makeUser('Private', 'Post');
        $this->makeIndustry($owner);
        $postId = $this->createPost($owner, 'Private company post', 'private');

        $intruder = $this->makeUser('Nosy', 'Commenter');

        $this->actingAs($intruder, 'api')
            ->postJson("/api/comment/{$postId}", ['comment' => 'Should not be allowed'])
            ->assertForbidden();

        $this->assertSame(0, Comment::where('post_id', $postId)->count());
    }

    public function test_generic_comment_list_rejects_a_followers_only_company_post(): void
    {
        $owner = $this->makeUser('Hidden', 'Comments');
        $this->makeIndustry($owner);
        $postId = $this->createPost($owner, 'Followers only company post', 'followers');
        $this->createComment($owner, $postId, 'Visible to followers only');

        $intruder = $this->makeUser('Stranger', 'Nosy');

        $this->actingAs($intruder, 'api')
            ->getJson("/api/comment-list/{$postId}")
            ->assertForbidden();
    }

    public function test_generic_like_list_rejects_a_private_company_post(): void
    {
        $owner = $this->makeUser('Private', 'Likes');
        $this->makeIndustry($owner);
        $postId = $this->createPost($owner, 'Private company post', 'private');

        $this->actingAs($owner, 'api')
            ->postJson("/api/toggle-like/{$postId}")
            ->assertOk();

        $intruder = $this->makeUser('Nosy', 'Lister');

        $this->actingAs($intruder, 'api')
            ->getJson("/api/liked-list/{$postId}")
            ->assertForbidden();
    }

    public function test_a_post_can_only_be_linked_to_one_company_page(): void
    {
        $owner = $this->makeUser('Single', 'Company');
        $industry = $this->makeIndustry($owner);
        $postId = $this->createPost($owner, 'Belongs to one company only', 'public');

        $otherOwner = $this->makeUser('Other', 'Owner');
        $otherIndustry = $this->makeIndustry($otherOwner);

        $this->expectException(QueryException::class);

        PostIndustry::create([
            'post_id' => $postId,
            'industry_id' => $otherIndustry->id,
        ]);
    }

    public function test_company_post_relationship_is_stored_in_the_mapping_table_not_on_posts(): void
    {
        $owner = $this->makeUser('Mapping', 'Table');
        $industry = $this->makeIndustry($owner);
        $postId = $this->createPost($owner, 'Mapped through a join table', 'public');

        $this->assertArrayNotHasKey(
            'industry_id',
            Post::findOrFail($postId)->getAttributes()
        );

        $this->assertDatabaseHas('post_industry', [
            'post_id' => $postId,
            'industry_id' => $industry->id,
        ]);

        $this->assertSame(
            $industry->id,
            Post::findOrFail($postId)->industryLink->industry_id
        );
    }

    public function test_deleting_a_company_post_removes_its_mapping_row(): void
    {
        $owner = $this->makeUser('Detach', 'OnDelete');
        $this->makeIndustry($owner);
        $postId = $this->createPost($owner, 'Post that gets deleted', 'public');

        $this->assertSame(1, PostIndustry::where('post_id', $postId)->count());

        $this->actingAs($owner, 'api')
            ->deleteJson("/api/industry/post/delete/{$postId}")
            ->assertOk();

        $this->assertSame(0, PostIndustry::where('post_id', $postId)->count());
    }

    public function test_company_page_posts_relation_uses_the_mapping_table(): void
    {
        $owner = $this->makeUser('Relation', 'Check');
        $industry = $this->makeIndustry($owner);
        $postId = $this->createPost($owner, 'Reachable through the relation', 'public');

        $this->assertTrue(
            $industry->posts()->where('posts.id', $postId)->exists()
        );
    }

    public function test_company_posts_do_not_leak_into_a_users_personal_timeline(): void
    {
        $owner = $this->makeUser('Timeline', 'Owner');
        $this->makeIndustry($owner);
        $postId = $this->createPost($owner, 'Public company announcement', 'public');

        $viewer = $this->makeUser('Viewer', 'Nosy');

        $response = $this->actingAs($viewer, 'api')
            ->getJson("/api/timeline/{$owner->id}")
            ->assertOk();

        $this->assertNotContains(
            $postId,
            array_column($response->json('data'), 'id'),
            'A company post leaked into a personal timeline.'
        );
    }

    public function test_regular_posts_still_appear_in_a_users_personal_timeline(): void
    {
        $owner = $this->makeUser('Regular', 'Poster');

        $regularId = Post::create([
            'user_id' => $owner->id,
            'description' => 'A normal personal post',
            'visibility' => 'public',
        ])->id;

        $viewer = $this->makeUser('Viewer', 'Friendly');

        $response = $this->actingAs($viewer, 'api')
            ->getJson("/api/timeline/{$owner->id}")
            ->assertOk();

        $this->assertContains(
            $regularId,
            array_column($response->json('data'), 'id')
        );
    }

    public function test_generic_profile_post_update_rejects_a_company_post(): void
    {
        $owner = $this->makeUser('Bypass', 'Attempt');
        $this->makeIndustry($owner);
        $postId = $this->createPost($owner, 'Company post content', 'public');

        $this->actingAs($owner, 'api')
            ->postJson("/api/profile/posts/{$postId}", [
                'description' => 'Rewritten through the profile route',
                'visibility' => 'public',
                'who_can_comment' => 'anyone',
            ])
            ->assertForbidden();

        $this->assertSame(
            'Company post content',
            Post::findOrFail($postId)->description
        );
    }

    public function test_generic_profile_post_delete_rejects_a_company_post(): void
    {
        $owner = $this->makeUser('Bypass', 'Delete');
        $this->makeIndustry($owner);
        $postId = $this->createPost($owner, 'Company post must survive', 'public');

        $this->actingAs($owner, 'api')
            ->deleteJson("/api/profile/posts/{$postId}")
            ->assertForbidden();

        $this->assertSame(1, Post::where('id', $postId)->count());
    }

    public function test_generic_comment_like_endpoint_rejects_a_comment_on_a_private_company_post(): void
    {
        $owner = $this->makeUser('Private', 'Thread');
        $this->makeIndustry($owner);
        $postId = $this->createPost($owner, 'Private company post', 'private');
        $commentId = $this->createComment($owner, $postId, 'A private comment');

        $intruder = $this->makeUser('Nosy', 'Liker');

        $this->actingAs($intruder, 'api')
            ->postJson("/api/comment-toggle-like/{$commentId}")
            ->assertForbidden();

        $this->assertSame(0, CommentLike::where('comment_id', $commentId)->count());
    }

    public function test_generic_reply_like_endpoint_rejects_a_reply_on_a_private_company_post(): void
    {
        $owner = $this->makeUser('Private', 'Thread');
        $this->makeIndustry($owner);
        $postId = $this->createPost($owner, 'Private company post', 'private');
        $commentId = $this->createComment($owner, $postId, 'A private comment');
        $replyId = $this->createReply($owner, $postId, $commentId, 'A private reply');

        $intruder = $this->makeUser('Nosy', 'Liker');

        $this->actingAs($intruder, 'api')
            ->postJson("/api/reply-toggle-like/{$replyId}")
            ->assertForbidden();

        $this->assertSame(0, ReplyLike::where('reply_id', $replyId)->count());
    }

    public function test_generic_reply_list_rejects_replies_on_a_private_company_post(): void
    {
        $owner = $this->makeUser('Private', 'Thread');
        $this->makeIndustry($owner);
        $postId = $this->createPost($owner, 'Private company post', 'private');
        $commentId = $this->createComment($owner, $postId, 'A private comment');
        $this->createReply($owner, $postId, $commentId, 'A private reply');

        $intruder = $this->makeUser('Nosy', 'Reader');

        $this->actingAs($intruder, 'api')
            ->getJson("/api/reply-list/{$commentId}")
            ->assertForbidden();
    }

    public function test_generic_comment_like_list_rejects_a_comment_on_a_private_company_post(): void
    {
        $owner = $this->makeUser('Private', 'Thread');
        $this->makeIndustry($owner);
        $postId = $this->createPost($owner, 'Private company post', 'private');
        $commentId = $this->createComment($owner, $postId, 'A private comment');

        $intruder = $this->makeUser('Nosy', 'Reader');

        $this->actingAs($intruder, 'api')
            ->getJson("/api/comment-liked-list/{$commentId}")
            ->assertForbidden();
    }

    public function test_comment_and_reply_likes_still_work_on_regular_posts(): void
    {
        $author = $this->makeUser('Regular', 'Author');

        $postId = Post::create([
            'user_id' => $author->id,
            'description' => 'A normal post',
            'visibility' => 'public',
        ])->id;

        $commentId = (int) $this->actingAs($author, 'api')
            ->postJson("/api/comment/{$postId}", ['comment' => 'A normal comment'])
            ->assertOk()
            ->json('data.id');

        $replyId = Reply::create([
            'post_id' => $postId,
            'comment_id' => $commentId,
            'user_id' => $author->id,
            'reply' => 'A normal reply',
        ])->id;

        $fan = $this->makeUser('Fan', 'Engaged');

        $this->actingAs($fan, 'api')
            ->postJson("/api/comment-toggle-like/{$commentId}")
            ->assertOk();

        $this->actingAs($fan, 'api')
            ->postJson("/api/reply-toggle-like/{$replyId}")
            ->assertOk();

        $this->actingAs($fan, 'api')
            ->getJson("/api/reply-list/{$commentId}")
            ->assertOk();

        $this->assertSame(1, CommentLike::where('comment_id', $commentId)->count());
        $this->assertSame(1, ReplyLike::where('reply_id', $replyId)->count());
    }

    private function createPost(
        User $owner,
        string $content,
        string $visibility,
        array $media = []
    ): int {
        $industry = Industry::where('created_by', $owner->id)->firstOrFail();

        $response = $this->actingAs($owner, 'api')->postJson('/api/industry/post/create', [
            'content' => $content,
            'visibility' => $visibility,
            'media' => $media,
        ]);

        $response->assertCreated();

        return (int) $response->json('data.post_id');
    }

    private function createComment(User $user, int $postId, string $body): int
    {
        $response = $this->actingAs($user, 'api')->postJson(
            "/api/industry/post/comment/{$postId}",
            ['comment' => $body]
        );

        $response->assertCreated();

        return (int) $response->json('data.id');
    }

    private function createReply(User $user, int $postId, int $commentId, string $body): int
    {
        $response = $this->actingAs($user, 'api')->postJson(
            "/api/industry/post/comment/reply/{$commentId}",
            ['comment' => $body]
        );

        $response->assertCreated();

        return (int) $response->json('data.id');
    }

    private function makeUser(?string $firstName = null, ?string $lastName = null): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $user = User::factory()->create([
            'is_verified' => true,
            'first_name' => $firstName ?? fake()->firstName(),
            'last_name' => $lastName ?? fake()->lastName(),
            'title' => fake()->jobTitle(),
        ]);

        $user->assignRole($role);

        UserProfile::create([
            'user_id' => $user->id,
        ]);

        $this->subscribe($user);

        return $user;
    }

    private function subscribe(User $user, array $overrides = []): Subscription
    {
        $existing = Subscription::where('user_id', $user->id)->latest('id')->first();

        if ($existing) {
            $existing->update($overrides);

            return $existing;
        }

        $plan = Plan::create([
            'name' => 'Company Plan',
            'billing_rate' => 10,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'features' => ['company_profile'],
        ]);

        return Subscription::create(array_merge([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'platform' => 'stripe',
            'provider_subscription_id' => 'sub_'.$user->id,
            'provider_customer_id' => (string) $user->id,
            'product_id' => 'prod_company',
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ], $overrides));
    }

    private function makeIndustry(User $owner): Industry
    {
        return Industry::create([
            'name' => 'Acme '.$owner->id,
            'slug' => 'acme-'.$owner->id,
            'industry' => 'Technology',
            'tagline' => 'We build things',
            'created_by' => $owner->id,
            'authorization_confirmed' => true,
            'authorization_confirmed_at' => now(),
        ]);
    }
}
