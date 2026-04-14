<?php

namespace Tests\Feature;

use App\Models\Forum;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_query_shows_prompt(): void
    {
        $this->get('/search')->assertOk()->assertSee('What are you looking for?');
    }

    public function test_search_matches_thread_titles_and_post_bodies(): void
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create();
        $thread = Thread::factory()
            ->for($forum)
            ->for($user, 'author')
            ->create(['title' => 'Supercalifragilistic thread']);
        Post::factory()->for($thread)->for($user, 'author')->create(['body' => 'body containing zxqvwymx']);

        $this->get('/search?q=Supercalifragilistic')->assertOk()->assertSee('Supercalifragilistic thread');
        $this->get('/search?q=zxqvwymx')->assertOk()->assertSee('Supercalifragilistic thread');
    }
}
