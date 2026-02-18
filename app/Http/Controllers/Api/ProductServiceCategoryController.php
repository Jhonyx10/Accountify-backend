<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductServiceCategoryResource;
use App\Models\ProductServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProductServiceCategoryController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = ProductServiceCategory::query();

        if ($user->type != 'super admin') {
            $query->where('created_by', $user->creatorId());
        }

        if ($request->has('search')) {
            $query->where('name', 'LIKE', "%{$request->search}%");
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $query->with(['creator', 'chartOfAccount']);
        $perPage = $request->input('per_page', 15);
        $categories = $query->latest()->paginate($perPage);

        return ProductServiceCategoryResource::collection($categories);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'chart_account_id' => 'nullable|exists:chart_of_accounts,id',
            'color' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $category = ProductServiceCategory::create([
            'name' => $request->name,
            'type' => $request->type,
            'chart_account_id' => $request->chart_account_id ?? 0,
            'color' => $request->color ?? '#fc544b',
            'created_by' => Auth::user()->creatorId(),
        ]);

        $category->load(['creator', 'chartOfAccount']);

        return response()->json([
            'success' => true,
            'message' => 'Product/Service category created successfully',
            'data' => new ProductServiceCategoryResource($category)
        ], 201);
    }

    public function show(string $id)
    {
        $user = Auth::user();
        $category = ProductServiceCategory::with(['creator', 'chartOfAccount'])->find($id);

        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Category not found'], 404);
        }

        if ($user->type != 'super admin' && $category->created_by != $user->creatorId()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        return response()->json(['success' => true, 'data' => new ProductServiceCategoryResource($category)]);
    }

    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $category = ProductServiceCategory::find($id);

        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Category not found'], 404);
        }

        if ($user->type != 'super admin' && $category->created_by != $user->creatorId()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|max:255',
            'chart_account_id' => 'nullable|exists:chart_of_accounts,id',
            'color' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $category->update($request->only(['name', 'type', 'chart_account_id', 'color']));
        $category->load(['creator', 'chartOfAccount']);

        return response()->json([
            'success' => true,
            'message' => 'Product/Service category updated successfully',
            'data' => new ProductServiceCategoryResource($category)
        ]);
    }

    public function destroy(string $id)
    {
        $user = Auth::user();
        $category = ProductServiceCategory::find($id);

        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Category not found'], 404);
        }

        if ($user->type != 'super admin' && $category->created_by != $user->creatorId()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $category->delete();

        return response()->json(['success' => true, 'message' => 'Product/Service category deleted successfully']);
    }
}
