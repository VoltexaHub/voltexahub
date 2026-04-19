<?php
namespace Tests\Feature\Auth;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_can_register(): void
    {
        $response = $this->post('/register', [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            '_turnstile' => 'test-token',
        ]);
        $response->assertRedirect(route('forum.index'));
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_login_with_valid_credentials(): void
    {
        $group = Group::factory()->create();
        $user = User::factory()->create([
            'group_id' => $group->id,
            'password' => bcrypt('Password1!'),
        ]);
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password1!',
            '_turnstile' => 'test-token',
        ]);
        $response->assertRedirect(route('forum.index'));
        $this->assertAuthenticatedAs($user);
    }
}
