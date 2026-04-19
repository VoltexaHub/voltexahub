<?php

namespace App\Admin\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController
{
    public function index(Request $request)
    {
        $users = User::with('group')
            ->when($request->search, fn ($q, $s) => $q->where('username', 'ilike', "%{$s}%")
                ->orWhere('email', 'ilike', "%{$s}%"))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users'   => $users,
            'groups'  => Group::orderBy('display_order')->get(),
            'filters' => $request->only('search'),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'group_id'   => ['nullable', 'exists:groups,id'],
            'is_trusted' => ['boolean'],
        ]);
        $user->update($data);
        return back()->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return back()->with('success', 'User deleted.');
    }

    public function ban(Request $request, User $user)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $user->update(['banned_at' => now(), 'banned_reason' => $data['reason'] ?? null]);
        return back()->with('success', 'User banned.');
    }

    public function unban(User $user)
    {
        $user->update(['banned_at' => null, 'banned_reason' => null]);
        return back()->with('success', 'User unbanned.');
    }
}
