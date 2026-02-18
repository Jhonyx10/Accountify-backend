<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions
     */
    public function index(Request $request)
    {
        $query = Permission::query();

        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $perPage = $request->input('per_page', 50);
        $permissions = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ]);
    }

    /**
     * Get all permissions (no pagination)
     */
    public function all()
    {
        $permissions = Permission::all();

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ]);
    }

    /**
     * Store a newly created permission
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:permissions,name',
            'guard_name' => 'nullable|string|max:255',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $permission = Permission::create([
            'name' => $request->name,
            'guard_name' => $request->guard_name ?? 'web',
        ]);

        // Assign permission to roles if provided
        if ($request->has('roles') && !empty($request->roles)) {
            foreach ($request->roles as $roleId) {
                $role = Role::findOrFail($roleId);
                $role->givePermissionTo($permission);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Permission created successfully',
            'data' => $permission,
        ], 201);
    }

    /**
     * Display the specified permission
     */
    public function show(string $id)
    {
        $permission = Permission::with('roles')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $permission,
        ]);
    }

    /**
     * Update the specified permission
     */
    public function update(Request $request, string $id)
    {
        $permission = Permission::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:permissions,name,' . $id,
            'guard_name' => 'nullable|string|max:255',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $permission->update($request->only(['name', 'guard_name']));

        // Sync roles if provided
        if ($request->has('roles')) {
            // Remove all existing role assignments
            $permission->roles()->detach();

            // Assign new roles
            if (!empty($request->roles)) {
                foreach ($request->roles as $roleId) {
                    $role = Role::findOrFail($roleId);
                    $role->givePermissionTo($permission);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Permission updated successfully',
            'data' => $permission->load('roles'),
        ]);
    }

    /**
     * Remove the specified permission
     */
    public function destroy(string $id)
    {
        $permission = Permission::findOrFail($id);

        // Remove permission from all roles
        $permission->roles()->detach();

        $permission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Permission deleted successfully'
        ]);
    }

    /**
     * Assign permission to a role
     */
    public function assignToRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'permission_id' => 'required|exists:permissions,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $permission = Permission::findOrFail($request->permission_id);
        $role = Role::findOrFail($request->role_id);

        $role->givePermissionTo($permission);

        return response()->json([
            'success' => true,
            'message' => 'Permission assigned to role successfully',
        ]);
    }
}

