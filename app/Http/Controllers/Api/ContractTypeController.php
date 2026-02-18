<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContractTypeResource;
use App\Models\ContractType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ContractTypeController extends Controller
{
    /**
     * Display a listing of contract types
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = ContractType::with('creator')->withCount('contracts');

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
        $contractTypes = $query->latest()->paginate($perPage);

        return ContractTypeResource::collection($contractTypes);
    }

    /**
     * Store a newly created contract type
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $creatorId = $request->user()->creatorId();

        $contractType = ContractType::create([
            'name' => $request->name,
            'created_by' => $creatorId,
        ]);

        return (new ContractTypeResource($contractType->load('creator')))
            ->additional(['message' => 'Contract type created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified contract type
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = ContractType::with('creator')->withCount('contracts');

        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        $contractType = $query->findOrFail($id);

        return new ContractTypeResource($contractType);
    }

    /**
     * Update the specified contract type
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = ContractType::query();

        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        $contractType = $query->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $contractType->update($request->all());

        return (new ContractTypeResource($contractType->load('creator')))
            ->additional(['message' => 'Contract type updated successfully']);
    }

    /**
     * Remove the specified contract type
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = ContractType::query();

        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        $contractType = $query->findOrFail($id);
        $contractType->delete();

        return response()->json([
            'message' => 'Contract type deleted successfully'
        ]);
    }
}

