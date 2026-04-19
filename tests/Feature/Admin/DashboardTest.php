<?php
namespace Tests\Feature\Admin;

use App\Forum\Models\Thread;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_stats(): void
    {
        $group = Group::factory()->create(['permissions' => ['is_admin' => true]]);
        $admin = User::factory()->create(['group_id' => $group->id]);
        Thread::factory()->count(5)->create();

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->has('stats')
        );
    }
}
