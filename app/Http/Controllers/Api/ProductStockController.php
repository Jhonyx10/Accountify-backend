<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockReportResource;
use App\Models\StockReport;
use App\Models\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProductStockController extends Controller
{
    /**
     * Display a listing of stock reports
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = StockReport::with(['creator', 'product']);

        // Multi-tenancy filtering
        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Search by description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('description', 'like', "%{$search}%");
        }

        $perPage = $request->input('per_page', 15);
        $stockReports = $query->latest()->paginate($perPage);

        return StockReportResource::collection($stockReports);
    }

    /**
     * Get stock summary for all products
     */
    public function summary(Request $request)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        // Get all products with type 'product'
        $productsQuery = ProductService::where('created_by', $creatorId)
            ->where('type', 'product');

        $products = $productsQuery->get();

        $stockSummary = [];

        foreach ($products as $product) {
            // Calculate total stock from stock reports (invoices subtract, bills & manual additions add)
            $totalStock = StockReport::where('product_id', $product->id)
                ->where('created_by', $creatorId)
                ->sum(DB::raw('CASE WHEN type = "invoice" THEN -quantity ELSE quantity END'));

            $stockSummary[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'current_stock' => (int) $totalStock,
                'sale_price' => (float) $product->sale_price,
                'purchase_price' => (float) $product->purchase_price,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $stockSummary,
        ]);
    }

    /**
     * Store a newly created stock report
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:product_services,id',
            'quantity' => 'required|integer',
            'type' => 'required|string|max:255',
            'type_id' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $stockReport = StockReport::create([
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'type' => $request->type,
            'type_id' => $request->type_id ?? 0,
            'description' => $request->description,
            'created_by' => $request->user()->creatorId(),
        ]);

        return (new StockReportResource($stockReport->load(['creator', 'product'])))
            ->additional(['message' => 'Stock report created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified stock report
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = StockReport::with(['creator', 'product']);

        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        $stockReport = $query->findOrFail($id);

        return new StockReportResource($stockReport);
    }

    /**
     * Update the specified stock report
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = StockReport::query();

        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        $stockReport = $query->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'product_id' => 'sometimes|required|integer|exists:product_services,id',
            'quantity' => 'sometimes|required|integer',
            'type' => 'sometimes|required|string|max:255',
            'type_id' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $stockReport->update($request->only([
            'product_id',
            'quantity',
            'type',
            'type_id',
            'description',
        ]));

        return (new StockReportResource($stockReport->load(['creator', 'product'])))
            ->additional(['message' => 'Stock report updated successfully']);
    }

    /**
     * Remove the specified stock report
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = StockReport::query();

        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        $stockReport = $query->findOrFail($id);
        $stockReport->delete();

        return response()->json([
            'message' => 'Stock report deleted successfully'
        ]);
    }
}

