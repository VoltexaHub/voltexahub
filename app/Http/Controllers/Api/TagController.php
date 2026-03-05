<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Tag::orderByDesc('use_count')->limit(50);

        if ($search = $request->input('q')) {
            $query->where('name', 'like', "%{$search}%");
        }

        return response()->json(['data' => $query->get(['id', 'name', 'slug', 'use_count'])]);
    }

    public function threads(string $slug): JsonResponse
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();

        $threads = $tag->threads()
            ->with([
                'user:id,username,avatar_color,avatar_path',
                'user.roles',
                'lastReplyUser:id,username,avatar_color,avatar_path',
                'prefix:id,name,color,bg_color,text_color',
                'tags:id,name,slug',
            ])
            ->orderByDesc('is_pinned')
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => $threads->items(),
            'tag' => ['id' => $tag->id, 'name' => $tag->name, 'slug' => $tag->slug, 'use_count' => $tag->use_count],
            'meta' => [
                'current_page' => $threads->currentPage(),
                'last_page' => $threads->lastPage(),
                'per_page' => $threads->perPage(),
                'total' => $threads->total(),
            ],
        ]);
    }
}
