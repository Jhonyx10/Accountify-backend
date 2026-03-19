<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CompanyController extends Controller
{
    /**
     * Display a listing of companies (Super Admin only).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->type !== 'super admin') {
            return response()->json(['message' => 'Unauthorized. Only Super Admin can access companies.'], 403);
        }

        $query = User::where('type', 'company');

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
        $companies = $query->with(['currentPlan'])->paginate($perPage);

        return UserResource::collection($companies);
    }

    /**
     * Store a newly created company.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->type !== 'super admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)],
            'plan_id' => 'nullable|exists:plans,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $planId = $request->plan_id ?? Plan::first()?->id;

        $company = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => 'company',
            'lang' => 'en',
            'mode' => 'light',
            'plan' => $planId,
            'created_by' => $user->id,
            'is_active' => 1,
            'is_enable_login' => 1,
        ]);

        // Default Company Scaffolding
        // In a complete implementation, we'd trigger chart of accounts setup here:
        // Utility::chartOfAccountTypeData($company->id);
        // Utility::chartOfAccountData1($company->id);
        // Utility::userDefaultDataRegister($company->id);

        return (new UserResource($company))
            ->additional(['message' => 'Company created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified company.
     */
    public function show(Request $request, string $id)
    {
        $user = $request->user();

        if ($user->type !== 'super admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $company = User::where('type', 'company')
            ->with(['currentPlan', 'orders' => function ($q) {
                $q->orderBy('created_at', 'desc');
            }])
            ->findOrFail($id);

        return (new UserResource($company))
            ->additional([
                'usage' => [
                    'users' => $company->createdUsers()->count(),
                    'customers' => $company->customers()->count(),
                    'venders' => $company->vendors()->count(),
                ],
                'billing_history' => $company->orders,
                'company_users' => $company->createdUsers()->select('id', 'name', 'email', 'type as role')->get(),
            ]);
    }

    /**
     * Update the specified company.
     */
    public function update(Request $request, string $id)
    {
        $user = $request->user();

        if ($user->type !== 'super admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $company = User::where('type', 'company')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'password' => ['sometimes', 'confirmed', Password::min(8)],
            'plan_id' => 'nullable|exists:plans,id',
            'is_active' => 'nullable|boolean',
            'is_enable_login' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateData = $request->only([
            'name',
            'email',
            'is_active',
            'is_enable_login',
        ]);

        if ($request->has('plan_id')) {
            $updateData['plan'] = $request->plan_id;
        }

        // Hash password if provided
        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $company->update($updateData);

        return (new UserResource($company))
            ->additional(['message' => 'Company updated successfully']);
    }

    /**
     * Remove the specified company (Soft Delete).
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();

        if ($user->type !== 'super admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $company = User::where('type', 'company')->findOrFail($id);

        // Soft delete the company
        $company->delete();

        return response()->json([
            'message' => 'Company deleted successfully'
        ]);
    }

    /**
     * Suspend the specified company.
     */
    public function suspend(Request $request, string $id)
    {
        $user = $request->user();

        if ($user->type !== 'super admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $company = User::where('type', 'company')->findOrFail($id);
        $company->update([
            'is_active' => 0,
            'is_enable_login' => 0
        ]);

        return response()->json([
            'message' => 'Company suspended successfully',
            'data' => new UserResource($company)
        ]);
    }

    /**
     * Activate the specified company.
     */
    public function activate(Request $request, string $id)
    {
        $user = $request->user();

        if ($user->type !== 'super admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $company = User::where('type', 'company')->findOrFail($id);
        $company->update([
            'is_active' => 1,
            'is_enable_login' => 1
        ]);

        return response()->json([
            'message' => 'Company activated successfully',
            'data' => new UserResource($company)
        ]);
    }

    /**
     * Impersonate the specified company admin.
     */
    public function impersonate(Request $request, string $id)
    {
        $user = $request->user();

        if ($user->type !== 'super admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $company = User::where('type', 'company')->findOrFail($id);
        
        // Generate a new token for the company user
        $token = $company->createToken('impersonation-token')->plainTextToken;

        return response()->json([
            'message' => 'Impersonation token generated',
            'token' => $token,
            'user' => new UserResource($company)
        ]);
    }
}
