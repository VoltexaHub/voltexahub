<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class AdminGroupController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::withCount('users')->get()->map(fn ($r) => $this->formatRole($r));

        return response()->json(['data' => $roles]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255', 'unique:roles,name'],
            'color' => ['nullable', 'string', 'max:50'],
            'label' => ['nullable', 'string', 'max:255'],
        ]);

        $role = Role::create([
            'name'       => $validated['name'],
            'guard_name' => 'web',
            'color'      => $validated['color'] ?? '#94a3b8',
            'label'      => $validated['label'] ?? null,
        ]);

        return response()->json([
            'data'    => $this->formatRole($role),
            'message' => 'Group created successfully.',
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'color' => ['nullable', 'string', 'max:50'],
            'label' => ['nullable', 'string', 'max:255'],
        ]);

        $role->update([
            'color' => $validated['color'] ?? $role->color,
            'label' => $validated['label'] ?? $role->label,
        ]);

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
        return [
            'id'          => $role->id,
            'name'        => $role->name,
            'guard_name'  => $role->guard_name,
            'color'       => $role->color ?? '#94a3b8',
            'label'       => $role->label ?? ucfirst($role->name),
            'users_count' => $role->users_count ?? 0,
            'created_at'  => $role->created_at,
            'updated_at'  => $role->updated_at,
        ];
    }
}
