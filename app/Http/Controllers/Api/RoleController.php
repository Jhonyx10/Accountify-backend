<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Filter roles by creator
        $query = Role::where('created_by', $user->creatorId());

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'LIKE', "%{$search}%");
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $roles = $query->with('permissions')->withCount(['permissions', 'users'])->paginate($perPage);

        return RoleResource::collection($roles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
            'created_by' => $request->user()->creatorId(),
        ]);

        // Assign permissions if provided
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return (new RoleResource($role->load('permissions')))
            ->additional(['message' => 'Role created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $role = Role::where('created_by', $request->user()->creatorId())
            ->with('permissions')
            ->withCount(['permissions', 'users'])
            ->findOrFail($id);

        return new RoleResource($role);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $role = Role::where('created_by', $request->user()->creatorId())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:roles,name,' . $id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('name')) {
            $role->update(['name' => $request->name]);
        }

        // Update permissions if provided
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return (new RoleResource($role->load('permissions')))
            ->additional(['message' => 'Role updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $role = Role::where('created_by', $request->user()->creatorId())->findOrFail($id);

        // Check if role is assigned to any users
        if ($role->users()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete role that is assigned to users'
            ], 422);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully'
        ]);
    }

    /**
     * Get all available permissions
     */
    public function permissions()
    {
        $permissions = Permission::all()->pluck('name', 'id');

        return response()->json([
            'data' => $permissions
        ]);
    }
}
