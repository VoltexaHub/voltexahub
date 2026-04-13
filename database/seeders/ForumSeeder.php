<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ForumSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::count() < 10
            ? User::factory(10)->create()->concat(User::all())
            : User::all();

        $structure = [
            'Community' => [
                'Announcements' => 'Official updates from the VoltexaHub team.',
                'Introductions' => 'New here? Say hello.',
                'General Discussion' => 'Anything and everything.',
            ],
            'Support' => [
                'Installation Help' => 'Getting VoltexaHub up and running.',
                'Bug Reports' => 'Something broken? Let us know.',
                'Feature Requests' => 'Ideas for future releases.',
            ],
            'Development' => [
                'Theme Development' => 'Build and share VoltexaHub themes.',
                'Plugin Development' => 'Extend VoltexaHub with plugins.',
            ],
        ];

        $catPos = 0;
        foreach ($structure as $catName => $forums) {
            $category = Category::create([
                'name' => $catName,
                'slug' => Str::slug($catName),
                'description' => null,
                'position' => $catPos++,
            ]);

            $forumPos = 0;
            foreach ($forums as $forumName => $desc) {
                $forum = Forum::create([
                    'category_id' => $category->id,
                    'name' => $forumName,
                    'slug' => Str::slug($forumName),
                    'description' => $desc,
                    'position' => $forumPos++,
                ]);

                $threadCount = rand(2, 5);
                for ($i = 0; $i < $threadCount; $i++) {
                    $thread = Thread::factory()
                        ->for($forum)
                        ->for($users->random(), 'author')
                        ->create();

                    $posts = Post::factory(rand(1, 8))
                        ->for($thread)
                        ->state(fn () => ['user_id' => $users->random()->id])
                        ->create();

                    $last = $posts->last();
                    $thread->update([
                        'posts_count' => $posts->count(),
                        'last_post_id' => $last->id,
                        'last_post_at' => $last->created_at,
                    ]);
                }

                $forumLast = Post::whereIn('thread_id', $forum->threads()->pluck('id'))
                    ->latest('created_at')->first();

                $forum->update([
                    'threads_count' => $forum->threads()->count(),
                    'posts_count' => Post::whereIn('thread_id', $forum->threads()->pluck('id'))->count(),
                    'last_post_id' => $forumLast?->id,
                    'last_post_at' => $forumLast?->created_at,
                ]);
            }
        }
    }
}
