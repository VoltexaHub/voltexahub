<?php

namespace Tests\Feature\Admin;

use App\Forum\Models\Category;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_category(): void
    {
        $admin = $this->makeAdmin();
        $response = $this->actingAs($admin)->post('/admin/categories', [
            'name' => 'Development', 'description' => 'Dev stuff',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('categories', ['name' => 'Development']);
    }

    public function test_admin_can_reorder_categories(): void
    {
        $admin = $this->makeAdmin();
        $cats = Category::factory()->count(3)->create();
        $order = $cats->pluck('id')->reverse()->values()->toArray();

        $this->actingAs($admin)->post('/admin/categories/reorder', ['order' => $order])
             ->assertOk();
        $this->assertEquals(0, Category::find($order[0])->display_order);
        $this->assertEquals(1, Category::find($order[1])->display_order);
        $this->assertEquals(2, Category::find($order[2])->display_order);
    }
}
