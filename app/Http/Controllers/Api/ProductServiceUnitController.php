<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductServiceUnitResource;
use App\Models\ProductServiceUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProductServiceUnitController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = ProductServiceUnit::query();

        if ($user->type != 'super admin') {
            $query->where('created_by', $user->creatorId());
        }

        if ($request->has('search')) {
            $query->where('name', 'LIKE', "%{$request->search}%");
        }

        $query->with('creator');
        $perPage = $request->input('per_page', 15);
        $units = $query->latest()->paginate($perPage);

        return ProductServiceUnitResource::collection($units);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $unit = ProductServiceUnit::create([
            'name' => $request->name,
            'created_by' => Auth::user()->creatorId(),
        ]);

        $unit->load('creator');

        return response()->json([
            'success' => true,
            'message' => 'Product/Service unit created successfully',
            'data' => new ProductServiceUnitResource($unit)
        ], 201);
    }

    public function show(string $id)
    {
        $user = Auth::user();
        $unit = ProductServiceUnit::with('creator')->find($id);

        if (!$unit) {
            return response()->json(['success' => false, 'message' => 'Unit not found'], 404);
        }

        if ($user->type != 'super admin' && $unit->created_by != $user->creatorId()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        return response()->json(['success' => true, 'data' => new ProductServiceUnitResource($unit)]);
    }

    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $unit = ProductServiceUnit::find($id);

        if (!$unit) {
            return response()->json(['success' => false, 'message' => 'Unit not found'], 404);
        }

        if ($user->type != 'super admin' && $unit->created_by != $user->creatorId()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $unit->update($request->only(['name']));
        $unit->load('creator');

        return response()->json([
            'success' => true,
            'message' => 'Product/Service unit updated successfully',
            'data' => new ProductServiceUnitResource($unit)
        ]);
    }

    public function destroy(string $id)
    {
        $user = Auth::user();
        $unit = ProductServiceUnit::find($id);

        if (!$unit) {
            return response()->json(['success' => false, 'message' => 'Unit not found'], 404);
        }

        if ($user->type != 'super admin' && $unit->created_by != $user->creatorId()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $unit->delete();

        return response()->json(['success' => true, 'message' => 'Product/Service unit deleted successfully']);
    }
}
