<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AssetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Asset::query();

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'LIKE', "%{$search}%");
        }

        // Filter by purchase date range
        if ($request->has('purchase_date_from')) {
            $query->whereDate('purchase_date', '>=', $request->purchase_date_from);
        }
        if ($request->has('purchase_date_to')) {
            $query->whereDate('purchase_date', '<=', $request->purchase_date_to);
        }

        // Filter by supported date range
        if ($request->has('supported_date_from')) {
            $query->whereDate('supported_date', '>=', $request->supported_date_from);
        }
        if ($request->has('supported_date_to')) {
            $query->whereDate('supported_date', '<=', $request->supported_date_to);
        }

        // Load relationships
        $query->with('creator');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $assets = $query->latest()->paginate($perPage);

        return AssetResource::collection($assets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'supported_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $asset = Asset::create([
            'name' => $request->name,
            'purchase_date' => $request->purchase_date,
            'supported_date' => $request->supported_date,
            'amount' => $request->amount,
            'description' => $request->description,
        ]);

        $asset->load('creator');

        return response()->json([
            'success' => true,
            'message' => 'Asset created successfully',
            'data' => new AssetResource($asset)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $asset = Asset::with('creator')->find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new AssetResource($asset)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $asset = Asset::find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'purchase_date' => 'sometimes|required|date',
            'supported_date' => 'sometimes|required|date',
            'amount' => 'sometimes|required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $asset->update($request->only([
            'name',
            'purchase_date',
            'supported_date',
            'amount',
            'description',
        ]));

        $asset->load('creator');

        return response()->json([
            'success' => true,
            'message' => 'Asset updated successfully',
            'data' => new AssetResource($asset)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $asset = Asset::find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found'
            ], 404);
        }

        $asset->delete();

        return response()->json([
            'success' => true,
            'message' => 'Asset deleted successfully'
        ]);
    }
}
