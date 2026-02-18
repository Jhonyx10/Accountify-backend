<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Super admin can see all users, company users see their own users
        if ($user->type === 'super admin') {
            $query = User::query();
        } else {
            $query = User::where('created_by', $user->creatorId())
                ->orWhere('id', $user->creatorId());
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $users = $query->with(['roles', 'permissions'])->paginate($perPage);

        return UserResource::collection($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)],
            'type' => 'nullable|string|in:company,super admin',
            'lang' => 'nullable|string|max:100',
            'mode' => 'nullable|string|in:light,dark',
            'plan' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'is_enable_login' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => $request->type ?? 'company',
            'lang' => $request->lang ?? 'en',
            'mode' => $request->mode ?? 'light',
            'plan' => $request->plan,
            'created_by' => $request->user()->creatorId(),
            'is_active' => $request->is_active ?? 1,
            'is_enable_login' => $request->is_enable_login ?? 1,
        ]);

        // Assign role if provided
        if ($request->has('role')) {
            $user->assignRole($request->role);
        }

        return (new UserResource($user->load(['roles', 'permissions'])))
            ->additional(['message' => 'User created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $currentUser = $request->user();

        $query = User::query();

        // Apply multi-tenancy filter
        if ($currentUser->type !== 'super admin') {
            $query->where(function ($q) use ($currentUser) {
                $q->where('created_by', $currentUser->creatorId())
                  ->orWhere('id', $currentUser->creatorId());
            });
        }

        $user = $query->with(['roles', 'permissions'])->findOrFail($id);

        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $currentUser = $request->user();

        $query = User::query();

        // Apply multi-tenancy filter
        if ($currentUser->type !== 'super admin') {
            $query->where(function ($q) use ($currentUser) {
                $q->where('created_by', $currentUser->creatorId())
                  ->orWhere('id', $currentUser->creatorId());
            });
        }

        $user = $query->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'password' => ['sometimes', 'confirmed', Password::min(8)],
            'type' => 'nullable|string|in:company,super admin',
            'lang' => 'nullable|string|max:100',
            'mode' => 'nullable|string|in:light,dark',
            'plan' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'is_enable_login' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateData = $request->only([
            'name',
            'email',
            'type',
            'lang',
            'mode',
            'plan',
            'is_active',
            'is_enable_login',
        ]);

        // Hash password if provided
        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        // Update role if provided
        if ($request->has('role')) {
            $user->syncRoles([$request->role]);
        }

        return (new UserResource($user->load(['roles', 'permissions'])))
            ->additional(['message' => 'User updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $currentUser = $request->user();

        $query = User::query();

        // Apply multi-tenancy filter
        if ($currentUser->type !== 'super admin') {
            $query->where(function ($q) use ($currentUser) {
                $q->where('created_by', $currentUser->creatorId())
                  ->orWhere('id', $currentUser->creatorId());
            });
        }

        $user = $query->findOrFail($id);

        // Prevent deleting yourself
        if ($user->id === $currentUser->id) {
            return response()->json([
                'message' => 'You cannot delete yourself'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }
}
