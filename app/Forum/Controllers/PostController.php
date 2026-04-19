<?php
namespace App\Forum\Controllers;

use App\Forum\Models\Post;
use App\Forum\Models\Thread;
use Illuminate\Http\Request;

// NOTE: Post model has a global scope filtering is_deleted=false.
// Use Post::withoutGlobalScope('active') when editing/deleting by ID.

class PostController
{
    public function store(Request $request, Thread $thread)
    {
        abort(501, 'Not implemented');
    }

    public function update(Request $request, Post $post)
    {
        abort(501, 'Not implemented');
    }

    public function destroy(Request $request, Post $post)
    {
        abort(501, 'Not implemented');
    }
}
