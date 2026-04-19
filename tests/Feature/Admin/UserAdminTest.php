<?php

namespace Tests\Feature\Admin;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_ban_user(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create();

        $this->actingAs($admin)->post("/admin/users/{$user->id}/ban", ['reason' => 'Spam'])
             ->assertRedirect();
        $this->assertNotNull($user->fresh()->banned_at);
    }

    public function test_admin_can_change_user_group(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create();
        $group = Group::factory()->create();

        $this->actingAs($admin)->put("/admin/users/{$user->id}", ['group_id' => $group->id])
             ->assertRedirect();
        $this->assertEquals($group->id, $user->fresh()->group_id);
    }
}
