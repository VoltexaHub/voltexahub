<?php
namespace Tests\Feature\Admin;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_admin(): void
    {
        $group = Group::factory()->create(['permissions' => ['is_admin' => false, 'is_moderator' => false]]);
        $user = User::factory()->create(['group_id' => $group->id]);
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_user_can_access_admin(): void
    {
        $group = Group::factory()->create(['permissions' => ['is_admin' => true]]);
        $user = User::factory()->create(['group_id' => $group->id]);
        $this->actingAs($user)->get('/admin')->assertOk();
    }
}
