<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PermissionNormalizer;
use App\Support\UserRoleResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:customers',
            'password' => ['required', 'confirmed', Password::min(8)],
            'referral_code' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $createdBy = 0;

        // Check if referral code is provided and belongs to a Customer
        if ($request->filled('referral_code')) {
            $customer = \App\Models\Customer::where('referral_code', $request->referral_code)->first();
            if ($customer) {
                // If the referral is from a specific Company's Customer, assign the user exactly to them
                $createdBy = $customer->created_by;
            }
        }

        $lastCustomer = \App\Models\Customer::where('created_by', $createdBy)->latest('customer_id')->first();
        $customerId = $lastCustomer ? $lastCustomer->customer_id + 1 : 1;

        $user = \App\Models\Customer::create([
            'customer_id' => $customerId,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'lang' => $request->lang ?? 'en',
            'created_by' => $createdBy,
            'used_referral_code' => $request->referral_code,
            'is_active' => 1,
            'is_enable_login' => 1,
        ]);

        // Create authentication token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Customer registered successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'type' => 'customer',
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Login user and create token
     */
    public function login(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');

        // 1. Try logging in as a User (Admin/Staff)
        if (Auth::guard('web')->attempt($credentials)) {
            $user = User::where('email', $request->email)->first();

            if (!$user->is_active || !$user->is_enable_login) {
                Auth::guard('web')->logout();
                return response()->json([
                    'message' => 'Your account has been disabled. Please contact administrator.'
                ], 403);
            }

            $user->update(['last_login_at' => now()]);
            UserRoleResolver::ensureDefaultRole($user);
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'type' => $user->type,
                    'avatar' => $user->avatar,
                    'lang' => $user->lang,
                    'mode' => $user->mode,
                    'plan' => $user->plan,
                    'plan_expire_date' => $user->plan_expire_date ? $user->plan_expire_date->format('Y-m-d') : null,
                    'permissions' => PermissionNormalizer::forUser($user),
                    'roles' => $user->getRoleNames(),
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
                'guard' => 'web',
            ]);
        }

        // 2. If that fails, try logging in as a Customer
        if (Auth::guard('customer')->attempt($credentials)) {
            $customer = \App\Models\Customer::where('email', $request->email)->first();

            if (!$customer->is_active || !$customer->is_enable_login) {
                Auth::guard('customer')->logout();
                return response()->json([
                    'message' => 'Your account has been disabled. Please contact administrator.'
                ], 403);
            }

            $customer->update(['last_login_at' => now()]);
            $token = $customer->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'user' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'type' => 'customer',
                    'avatar' => $customer->avatar,
                    'lang' => $customer->lang,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
                'guard' => 'customer',
            ]);
        }

        return response()->json([
            'message' => 'Invalid login credentials'
        ], 401);
    }

    /**
     * Logout user (Revoke token)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'type' => $user->type,
                'avatar' => $user->avatar,
                'lang' => $user->lang,
                'mode' => $user->mode,
                'plan' => $user->plan,
                'plan_expire_date' => $user->plan_expire_date?->format('Y-m-d'),
                'created_at' => $user->created_at?->format('Y-m-d H:i:s'),
                'permissions' => PermissionNormalizer::forUser($user),
                'roles' => $user->getRoleNames(),
            ]
        ]);
    }
}
