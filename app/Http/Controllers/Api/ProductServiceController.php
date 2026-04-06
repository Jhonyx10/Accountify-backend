<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductServiceResource;
use App\Models\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductService::with(['creator', 'category', 'unit', 'saleChartAccount', 'expenseChartAccount']);

        if ($request->user()) {
            $query->where('created_by', $request->user()->id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $products = $query->latest()->paginate($perPage);

        return ProductServiceResource::collection($products);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:product_services,sku',
            'sale_price' => 'required|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'type' => 'required|string|in:product,service',
            'category_id' => 'nullable|integer',
            'unit_id' => 'nullable|integer',
            'sale_chartaccount_id' => 'nullable|integer',
            'expense_chartaccount_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = ProductService::create([
            'name' => $request->name,
            'sku' => $request->sku,
            'sale_price' => $request->sale_price,
            'purchase_price' => $request->purchase_price,
            'quantity' => $request->quantity ?? 0,
            'tax_id' => $request->tax_id,
            'category_id' => $request->category_id ?? 0,
            'unit_id' => $request->unit_id ?? 0,
            'type' => $request->type,
            'sale_chartaccount_id' => $request->sale_chartaccount_id ?? 0,
            'expense_chartaccount_id' => $request->expense_chartaccount_id ?? 0,
            'description' => $request->description,
            'created_by' => $request->user()->id,
        ]);

        return (new ProductServiceResource($product->load(['category', 'unit'])))
            ->additional(['message' => 'Product/Service created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $id)
    {
        $product = ProductService::with(['creator', 'category', 'unit', 'saleChartAccount', 'expenseChartAccount'])->findOrFail($id);

        return new ProductServiceResource($product);
    }

    public function update(Request $request, string $id)
    {
        $product = ProductService::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'sku' => 'sometimes|required|string|max:255|unique:product_services,sku,' . $id,
            'sale_price' => 'sometimes|required|numeric|min:0',
            'purchase_price' => 'sometimes|required|numeric|min:0',
            'type' => 'sometimes|required|string|in:product,service',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product->update($request->except(['created_by']));

        return (new ProductServiceResource($product->load(['category', 'unit'])))
            ->additional(['message' => 'Product/Service updated successfully']);
    }

    public function destroy(string $id)
    {
        $product = ProductService::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Product/Service deleted successfully']);
    }
}
