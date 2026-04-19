<?php
namespace App\Forum\Controllers;

use App\Forum\Models\Forum;
use App\Forum\Models\Thread;
use Illuminate\Http\Request;

class ThreadController
{
    public function show(Thread $thread)
    {
        abort(501, 'Not implemented');
    }

    public function create(Forum $forum)
    {
        abort(501, 'Not implemented');
    }

    public function store(Request $request, Forum $forum)
    {
        abort(501, 'Not implemented');
    }
}
