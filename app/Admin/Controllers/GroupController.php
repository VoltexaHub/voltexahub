<?php

namespace App\Admin\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GroupController
{
    public function index()
    {
        return Inertia::render('Admin/Groups/Index', [
            'groups' => Group::withCount('users')->orderBy('display_order')->get(),
        ]);
    }

    public function create()
    {
        return redirect()->route('admin.groups.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                          => ['required', 'string', 'max:50'],
            'color'                         => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon'                          => ['nullable', 'string', 'max:10'],
            'is_staff'                      => ['boolean'],
            'permissions'                   => ['required', 'array'],
            'permissions.can_post'          => ['boolean'],
            'permissions.can_create_thread' => ['boolean'],
            'permissions.can_upload_avatar' => ['boolean'],
            'permissions.can_use_signature' => ['boolean'],
            'permissions.can_react'         => ['boolean'],
            'permissions.is_moderator'      => ['boolean'],
            'permissions.is_admin'          => ['boolean'],
        ]);
        Group::create($data + ['display_order' => (Group::max('display_order') ?? 0) + 1]);
        return back()->with('success', 'Group created.');
    }

    public function edit(Group $group)
    {
        return redirect()->route('admin.groups.index');
    }

    public function update(Request $request, Group $group)
    {
        $data = $request->validate([
            'name'                          => ['required', 'string', 'max:50'],
            'color'                         => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon'                          => ['nullable', 'string', 'max:10'],
            'is_staff'                      => ['boolean'],
            'permissions'                   => ['required', 'array'],
            'permissions.can_post'          => ['boolean'],
            'permissions.can_create_thread' => ['boolean'],
            'permissions.can_upload_avatar' => ['boolean'],
            'permissions.can_use_signature' => ['boolean'],
            'permissions.can_react'         => ['boolean'],
            'permissions.is_moderator'      => ['boolean'],
            'permissions.is_admin'          => ['boolean'],
        ]);
        $group->update($data);
        return back()->with('success', 'Group updated.');
    }

    public function destroy(Group $group)
    {
        $group->users()->update(['group_id' => null]);
        $group->delete();
        return back()->with('success', 'Group deleted.');
    }
}
