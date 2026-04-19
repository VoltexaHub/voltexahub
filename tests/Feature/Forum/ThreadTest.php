<?php
namespace Tests\Feature\Forum;

use App\Forum\Models\Forum;
use App\Forum\Models\Post;
use App\Forum\Models\Thread;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreadTest extends TestCase
{
    use RefreshDatabase;

    public function test_thread_show_renders_posts(): void
    {
        $thread = Thread::factory()->create();
        Post::factory()->count(3)->create(['thread_id' => $thread->id]);

        $response = $this->get("/thread/{$thread->slug}");
        $response->assertInertia(fn ($page) => $page
            ->component('Thread/Show')
            ->has('posts.data', 3)
        );
    }

    public function test_authenticated_user_can_post_reply(): void
    {
        $group = Group::factory()->create();
        $user = User::factory()->create(['group_id' => $group->id]);
        $thread = Thread::factory()->create();

        $response = $this->actingAs($user)->post("/thread/{$thread->slug}/reply", [
            'body' => '## Hello world\n\nThis is a test reply.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('posts', ['thread_id' => $thread->id, 'user_id' => $user->id]);
    }
}
