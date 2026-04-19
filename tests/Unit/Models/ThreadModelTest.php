<?php
namespace Tests\Unit\Models;

use App\Forum\Models\Category;
use App\Forum\Models\Forum;
use App\Forum\Models\Thread;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreadModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_thread_belongs_to_forum_and_user(): void
    {
        $group = Group::factory()->create();
        $user = User::factory()->create(['group_id' => $group->id]);
        $category = Category::factory()->create();
        $forum = Forum::factory()->create(['category_id' => $category->id]);
        $thread = Thread::factory()->create(['forum_id' => $forum->id, 'user_id' => $user->id]);

        $this->assertInstanceOf(Forum::class, $thread->forum);
        $this->assertInstanceOf(User::class, $thread->user);
    }
}
