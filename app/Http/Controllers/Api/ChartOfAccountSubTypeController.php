<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChartOfAccountSubTypeResource;
use App\Models\ChartOfAccountSubType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChartOfAccountSubTypeController extends Controller
{
    /**
     * Display a listing of chart of account sub-types
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = ChartOfAccountSubType::with(['creator', 'typeRelation']);

        if ($user->type != 'super admin') {
            $query->whereIn('created_by', [$creatorId, 0]);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $perPage = $request->input('per_page', 15);
        if ($perPage == -1) {
            $subTypes = $query->get();
            return ChartOfAccountSubTypeResource::collection($subTypes);
        } else {
            $subTypes = $query->latest()->paginate($perPage);
            return ChartOfAccountSubTypeResource::collection($subTypes);
        }
    }

    /**
     * Store a newly created chart of account sub-type
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|integer|exists:chart_of_account_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $subType = ChartOfAccountSubType::create([
            'name' => $request->name,
            'type' => $request->type,
            'created_by' => $request->user()->creatorId(),
        ]);

        return (new ChartOfAccountSubTypeResource($subType->load(['creator', 'typeRelation'])))
            ->additional(['message' => 'Chart of account sub-type created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified chart of account sub-type
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = ChartOfAccountSubType::with(['creator', 'typeRelation']);

        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        $subType = $query->findOrFail($id);

        return new ChartOfAccountSubTypeResource($subType);
    }

    /**
     * Update the specified chart of account sub-type
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = ChartOfAccountSubType::query();

        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        $subType = $query->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|integer|exists:chart_of_account_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $subType->update($request->all());

        return (new ChartOfAccountSubTypeResource($subType->load(['creator', 'typeRelation'])))
            ->additional(['message' => 'Chart of account sub-type updated successfully']);
    }

    /**
     * Remove the specified chart of account sub-type
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = ChartOfAccountSubType::query();

        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        $subType = $query->findOrFail($id);

        // Check if there are any chart of accounts using this sub-type
        if ($subType->chartOfAccounts()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete sub-type that is being used by chart of accounts'
            ], 422);
        }

        $subType->delete();

        return response()->json([
            'message' => 'Chart of account sub-type deleted successfully'
        ]);
    }
}
