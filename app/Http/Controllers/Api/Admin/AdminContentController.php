<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ForumConfig;
use App\Models\HelpArticle;
use App\Models\Post;
use App\Models\Thread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminContentController extends Controller
{
    public function threads(Request $request): JsonResponse
    {
        $query = Thread::with([
            'forum:id,name',
            'user:id,username,avatar_color,avatar_path',
        ]);

        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $threads = $query->latest()->paginate(25);

        $data = collect($threads->items())->map(fn (Thread $t) => [
            'id' => $t->id,
            'title' => $t->title,
            'forum_name' => $t->forum->name ?? null,
            'author_username' => $t->user->username ?? null,
            'reply_count' => $t->reply_count,
            'is_pinned' => $t->is_pinned,
            'is_locked' => $t->is_locked,
            'created_at' => $t->created_at,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $threads->currentPage(),
                'last_page' => $threads->lastPage(),
                'per_page' => $threads->perPage(),
                'total' => $threads->total(),
            ],
        ]);
    }

    public function posts(Request $request): JsonResponse
    {
        $query = Post::with([
            'user:id,username',
            'thread:id,title',
        ]);

        if ($search = $request->input('search')) {
            $query->where('body', 'like', "%{$search}%");
        }

        $posts = $query->latest()->paginate(25);

        $data = collect($posts->items())->map(fn (Post $p) => [
            'id' => $p->id,
            'content' => \Illuminate\Support\Str::limit($p->body, 150),
            'author_username' => $p->user->username ?? null,
            'thread_title' => $p->thread->title ?? null,
            'thread_id' => $p->thread_id,
            'created_at' => $p->created_at,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    // ─── Static Pages ────────────────────────────────────────────
    public function getPages(): JsonResponse
    {
        return response()->json(['data' => [
            'rules'   => ForumConfig::get('page_rules', ''),
            'privacy' => ForumConfig::get('page_privacy', ''),
            'tos'     => ForumConfig::get('page_tos', ''),
        ]]);
    }

    public function updatePages(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rules'   => ['nullable', 'string'],
            'privacy' => ['nullable', 'string'],
            'tos'     => ['nullable', 'string'],
        ]);

        foreach (['rules', 'privacy', 'tos'] as $key) {
            if (array_key_exists($key, $validated)) {
                ForumConfig::set('page_' . $key, $validated[$key] ?? '');
            }
        }

        return response()->json(['message' => 'Pages updated.']);
    }

    public function getPage(string $page): JsonResponse
    {
        $allowed = ['rules', 'privacy', 'tos'];
        if (!in_array($page, $allowed)) {
            abort(404);
        }

        return response()->json(['data' => ['content' => ForumConfig::get('page_' . $page, '')]]);
    }

    // ─── Help Articles ───────────────────────────────────────────
    public function adminHelpIndex(): JsonResponse
    {
        $articles = HelpArticle::orderBy('category')->orderBy('display_order')->orderBy('title')->get();

        return response()->json(['data' => $articles]);
    }

    public function helpStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'         => ['required', 'string', 'max:255'],
            'category'      => ['required', 'string', 'max:255'],
            'content'       => ['required', 'string'],
            'display_order' => ['nullable', 'integer'],
            'is_published'  => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['title']);

        // Ensure unique slug
        $base = $validated['slug'];
        $i = 1;
        while (HelpArticle::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $base . '-' . $i++;
        }

        $article = HelpArticle::create($validated);

        return response()->json(['data' => $article], 201);
    }

    public function helpUpdate(Request $request, int $id): JsonResponse
    {
        $article = HelpArticle::findOrFail($id);

        $validated = $request->validate([
            'title'         => ['sometimes', 'required', 'string', 'max:255'],
            'category'      => ['sometimes', 'required', 'string', 'max:255'],
            'content'       => ['sometimes', 'required', 'string'],
            'display_order' => ['nullable', 'integer'],
            'is_published'  => ['nullable', 'boolean'],
        ]);

        if (isset($validated['title'])) {
            $slug = Str::slug($validated['title']);
            $base = $slug;
            $i = 1;
            while (HelpArticle::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = $base . '-' . $i++;
            }
            $validated['slug'] = $slug;
        }

        $article->update($validated);

        return response()->json(['data' => $article]);
    }

    public function helpDestroy(int $id): JsonResponse
    {
        $article = HelpArticle::findOrFail($id);
        $article->delete();

        return response()->json(['message' => 'Article deleted.']);
    }

    public function helpIndex(): JsonResponse
    {
        $articles = HelpArticle::where('is_published', true)
            ->orderBy('category')
            ->orderBy('display_order')
            ->orderBy('title')
            ->get()
            ->groupBy('category')
            ->map(fn ($group) => $group->map(fn ($a) => [
                'id'       => $a->id,
                'title'    => $a->title,
                'slug'     => $a->slug,
                'category' => $a->category,
                'content'  => $a->content,
                'display_order' => $a->display_order,
            ])->values());

        return response()->json(['data' => $articles]);
    }

    public function helpShow(string $slug): JsonResponse
    {
        $article = HelpArticle::where('slug', $slug)->where('is_published', true)->firstOrFail();

        return response()->json(['data' => $article]);
    }
}
