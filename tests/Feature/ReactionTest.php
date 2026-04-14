<?php

namespace Tests\Feature;

use App\Models\Forum;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_and_remove_a_reaction(): void
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create();
        $thread = Thread::factory()->for($forum)->for($user, 'author')->create();
        $post = Post::factory()->for($thread)->for($user, 'author')->create();

        $this->actingAs($user)
            ->postJson(route('posts.reactions.toggle', $post->id), ['emoji' => '👍'])
            ->assertOk()
            ->assertJsonPath('summary.0.emoji', '👍')
            ->assertJsonPath('summary.0.count', 1)
            ->assertJsonPath('summary.0.mine', true);

        $this->assertDatabaseHas('reactions', [
            'post_id' => $post->id, 'user_id' => $user->id, 'emoji' => '👍',
        ]);

        $this->actingAs($user)
            ->postJson(route('posts.reactions.toggle', $post->id), ['emoji' => '👍'])
            ->assertOk()
            ->assertJsonPath('summary', []);

        $this->assertDatabaseMissing('reactions', [
            'post_id' => $post->id, 'user_id' => $user->id, 'emoji' => '👍',
        ]);
    }

    public function test_unsupported_emoji_is_rejected(): void
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create();
        $thread = Thread::factory()->for($forum)->for($user, 'author')->create();
        $post = Post::factory()->for($thread)->for($user, 'author')->create();

        $this->actingAs($user)
            ->postJson(route('posts.reactions.toggle', $post->id), ['emoji' => '💩'])
            ->assertUnprocessable();

        $this->assertDatabaseCount('reactions', 0);
    }

    public function test_guest_cannot_react(): void
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create();
        $thread = Thread::factory()->for($forum)->for($user, 'author')->create();
        $post = Post::factory()->for($thread)->for($user, 'author')->create();

        $this->post(route('posts.reactions.toggle', $post->id), ['emoji' => '👍'])
            ->assertRedirect('/login');
    }
}
