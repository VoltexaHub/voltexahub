<?php
namespace Tests\Feature\Forum;

use App\Forum\Models\Category;
use App\Forum\Models\Forum;
use App\Forum\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_forum_index_renders(): void
    {
        $category = Category::factory()->create(['name' => 'Development']);
        Forum::factory()->create(['category_id' => $category->id, 'name' => 'Web Dev']);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Forum/Index')
            ->has('categories', 1)
        );
    }

    public function test_forum_show_lists_threads(): void
    {
        $forum = Forum::factory()->create();
        Thread::factory()->count(3)->create(['forum_id' => $forum->id]);

        $response = $this->get("/forum/{$forum->id}");
        $response->assertInertia(fn ($page) => $page
            ->component('Forum/Show')
            ->has('threads.data', 3)
        );
    }
}
