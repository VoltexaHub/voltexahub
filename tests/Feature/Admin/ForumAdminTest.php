<?php

namespace Tests\Feature\Admin;

use App\Forum\Models\Category;
use App\Forum\Models\Forum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_forum(): void
    {
        $admin = $this->makeAdmin();
        $category = Category::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/forums', [
            'category_id' => $category->id,
            'name' => 'General Discussion',
            'description' => 'Talk about anything',
            'icon' => '💬',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('forums', ['name' => 'General Discussion', 'category_id' => $category->id]);
    }

    public function test_forum_creation_requires_valid_category(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post('/admin/forums', [
            'category_id' => 9999,
            'name' => 'Test',
        ]);
        $response->assertSessionHasErrors('category_id');
    }
}
