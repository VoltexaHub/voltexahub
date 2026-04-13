<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->when($request->string('q')->toString(), function ($q, $term) {
                $q->where(fn ($w) => $w->where('name', 'ilike', "%{$term}%")->orWhere('email', 'ilike', "%{$term}%"));
            })
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => ['q' => $request->string('q')->toString()],
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'is_admin' => ['sometimes', 'boolean'],
            'name' => ['sometimes', 'string', 'max:120'],
        ]);

        if (array_key_exists('is_admin', $data) && $user->id === $request->user()->id && ! $data['is_admin']) {
            return back()->with('flash.error', 'You cannot demote yourself.');
        }

        $user->update($data);

        return back()->with('flash.success', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('flash.error', 'You cannot delete yourself.');
        }

        $user->delete();

        return back()->with('flash.success', 'User deleted.');
    }
}
