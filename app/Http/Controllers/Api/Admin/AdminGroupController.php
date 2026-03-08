<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\GroupPermission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class AdminGroupController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::all()->map(fn ($r) => $this->formatRole($r));

        return response()->json(['data' => $roles]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:255', 'unique:roles,name'],
            'color'               => ['nullable', 'string', 'max:50'],
            'label'               => ['nullable', 'string', 'max:255'],
            'perks'               => ['nullable', 'array'],
            'priority'            => ['nullable', 'integer', 'min:0', 'max:100'],
            'is_staff'            => ['nullable', 'boolean'],
            'staff_permissions'   => ['nullable', 'array'],
            'staff_permissions.*' => ['string'],
        ]);

        $role = Role::create([
            'name'              => $validated['name'],
            'guard_name'        => 'web',
            'color'             => $validated['color'] ?? '#94a3b8',
            'label'             => $validated['label'] ?? null,
            'perks'             => $validated['perks'] ?? [],
            'priority'          => $validated['priority'] ?? 0,
            'is_staff'          => (bool) ($validated['is_staff'] ?? false),
            'staff_permissions' => $validated['staff_permissions'] ?? [],
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'data'    => $this->formatRole($role),
            'message' => 'Group created successfully.',
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'color'               => ['nullable', 'string', 'max:50'],
            'label'               => ['nullable', 'string', 'max:255'],
            'perks'               => ['nullable', 'array'],
            'priority'            => ['nullable', 'integer', 'min:0', 'max:100'],
            'is_staff'            => ['nullable', 'boolean'],
            'staff_permissions'   => ['nullable', 'array'],
            'staff_permissions.*' => ['string'],
            'can_view'            => ['nullable', 'boolean'],
            'can_post'            => ['nullable', 'boolean'],
            'can_reply'           => ['nullable', 'boolean'],
        ]);

        $role->update([
            'color'             => $validated['color'] ?? $role->color,
            'label'             => $validated['label'] ?? $role->label,
            'perks'             => array_key_exists('perks', $validated) ? ($validated['perks'] ?? []) : ($role->perks ?? []),
            'priority'          => array_key_exists('priority', $validated) ? ($validated['priority'] ?? 0) : $role->priority,
            'is_staff'          => array_key_exists('is_staff', $validated) ? (bool) $validated['is_staff'] : $role->is_staff,
            'staff_permissions' => array_key_exists('staff_permissions', $validated) ? ($validated['staff_permissions'] ?? []) : ($role->staff_permissions ?? []),
        ]);

        // Save group default permissions inline if provided
        if (array_key_exists('can_view', $validated) || array_key_exists('can_post', $validated) || array_key_exists('can_reply', $validated)) {
            $gp = GroupPermission::firstOrNew(['role_name' => $role->name]);
            if (array_key_exists('can_view', $validated))  $gp->can_view  = (bool) $validated['can_view'];
            if (array_key_exists('can_post', $validated))  $gp->can_post  = (bool) $validated['can_post'];
            if (array_key_exists('can_reply', $validated)) $gp->can_reply = (bool) $validated['can_reply'];
            $gp->save();
        }

        // Clear Spatie permission cache so updated role data is reflected immediately
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Refresh from DB to ensure response reflects persisted values
        $role->refresh();

        return response()->json([
            'data'    => $this->formatRole($role),
            'message' => 'Group updated successfully.',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json(['message' => 'Group deleted successfully.']);
    }

    private function formatRole(Role $role): array
    {
        $gp = GroupPermission::where('role_name', $role->name)->first();

        return [
            'id'                => $role->id,
            'name'              => $role->name,
            'guard_name'        => $role->guard_name,
            'color'             => $role->color ?? '#94a3b8',
            'label'             => $role->label ?? ucfirst($role->name),
            'perks'             => $role->perks ?? [],
            'priority'          => $role->priority ?? 0,
            'is_staff'          => (bool) ($role->is_staff ?? false),
            'staff_permissions' => $role->staff_permissions ?? [],
            'can_view'          => $gp ? (bool) $gp->can_view  : true,
            'can_post'          => $gp ? (bool) $gp->can_post  : true,
            'can_reply'         => $gp ? (bool) $gp->can_reply : true,
            'users_count'       => \DB::table('model_has_roles')->where('role_id', $role->id)->count(),
            'created_at'        => $role->created_at,
            'updated_at'        => $role->updated_at,
        ];
    }
}
