<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChartOfAccountTypeResource;
use App\Models\ChartOfAccountType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChartOfAccountTypeController extends Controller
{
    /**
     * Display a listing of chart of account types
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = ChartOfAccountType::with('creator')->withCount('chartOfAccounts');

        // Multi-tenancy filtering
        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $perPage = $request->input('per_page', 15);
        $types = $query->latest()->paginate($perPage);

        return ChartOfAccountTypeResource::collection($types);
    }

    /**
     * Store a newly created chart of account type
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $type = ChartOfAccountType::create([
            'name' => $request->name,
            'created_by' => $request->user()->creatorId(),
        ]);

        return (new ChartOfAccountTypeResource($type->load('creator')))
            ->additional(['message' => 'Chart of account type created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified chart of account type
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = ChartOfAccountType::with('creator')->withCount('chartOfAccounts');

        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        $type = $query->findOrFail($id);

        return new ChartOfAccountTypeResource($type);
    }

    /**
     * Update the specified chart of account type
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = ChartOfAccountType::query();

        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        $type = $query->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $type->update($request->only(['name']));

        return (new ChartOfAccountTypeResource($type->load('creator')))
            ->additional(['message' => 'Chart of account type updated successfully']);
    }

    /**
     * Remove the specified chart of account type
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = ChartOfAccountType::query();

        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        $type = $query->findOrFail($id);

        // Check if there are any chart of accounts using this type
        if ($type->chartOfAccounts()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete chart of account type that is being used by chart of accounts'
            ], 422);
        }

        $type->delete();

        return response()->json([
            'message' => 'Chart of account type deleted successfully'
        ]);
    }
}

