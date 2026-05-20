<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BillResource;
use App\Http\Resources\POResource;
use App\Models\Bill;
use App\Models\BillProduct;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class POController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('vender_id')) {
            $query->where('vender_id', $request->vender_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('purchase_order_id', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $pos = $query->with(['vender', 'products'])->latest()->paginate($perPage);

        return POResource::collection($pos);
    }

    public function store(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Creating new purchase order with data:', $request->all());

        $validator = Validator::make($request->all(), [
            'vender_id' => 'required|integer',
            'po_date' => 'required|date',
            'delivery_date' => 'nullable|date',
            'status' => 'nullable|integer',
            'category_id' => 'nullable|integer',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric',
            'items.*.price' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $lastPo = PurchaseOrder::where('created_by', $request->user()->id)->latest('id')->first();
            $poNumber = $lastPo ? ((int) $lastPo->po_number + 1) : 1;

            $po = PurchaseOrder::create([
                'po_number' => (string) $poNumber,
                'vender_id' => $request->vender_id,
                'po_date' => $request->po_date,
                'delivery_date' => $request->delivery_date,
                'status' => $request->status ?? 0,
                'category_id' => $request->category_id ?? 0,
                'shipping_display' => $request->shipping_display ?? 1,
                'discount_apply' => $request->discount_apply ?? 0,
                'notes' => $request->notes,
                'created_by' => $request->user()->id,
            ]);

            foreach ($request->items as $item) {
                PurchaseOrderProduct::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'tax' => $item['tax'] ?? null,
                    'discount' => $item['discount'] ?? 0,
                    'price' => $item['price'],
                    'description' => $item['description'] ?? '',
                ]);
            }

            DB::commit();

            return (new POResource($po->load(['vender', 'products'])))
                ->additional(['message' => 'Purchase Order created successfully'])
                ->response()
                ->setStatusCode(201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('PO Store exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['message' => 'Error creating PO', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        $po = PurchaseOrder::with(['vender', 'products'])->findOrFail($id);
        return new POResource($po);
    }

    public function update(Request $request, string $id)
    {
        $po = PurchaseOrder::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'vender_id' => 'sometimes|required|integer',
            'po_date' => 'sometimes|required|date',
            'items' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $po->update($request->except(['po_number', 'created_by', 'items']));

            if ($request->has('items')) {
                PurchaseOrderProduct::where('purchase_order_id', $po->id)->delete();

                foreach ($request->items as $item) {
                    PurchaseOrderProduct::create([
                        'purchase_order_id' => $po->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'tax' => $item['tax'] ?? null,
                        'discount' => $item['discount'] ?? 0,
                        'price' => $item['price'],
                        'description' => $item['description'] ?? '',
                    ]);
                }
            }

            DB::commit();

            return (new POResource($po->load(['vender', 'products'])))
                ->additional(['message' => 'Purchase Order updated successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating PO', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        $po = PurchaseOrder::findOrFail($id);
        PurchaseOrderProduct::where('purchase_order_id', $po->id)->delete();
        $po->delete();

        return response()->json(['message' => 'Purchase Order deleted successfully']);
    }

    /**
     * Convert a Purchase Order into a Bill.
     */
    public function convertToBill(string $id, Request $request)
    {
        $po = PurchaseOrder::with('products')->findOrFail($id);

        if ($po->status === 2) {
            return response()->json(['message' => 'This PO has already been converted to a bill.'], 422);
        }

        try {
            DB::beginTransaction();

            $lastBill = Bill::where('created_by', $request->user()->id)->latest('id')->first();
            $billId = $lastBill ? ((int) $lastBill->bill_id + 1) : 1;

            $bill = Bill::create([
                'bill_id' => (string) $billId,
                'vender_id' => $po->vender_id,
                'bill_date' => now()->toDateString(),
                'due_date' => $po->delivery_date ?? now()->addDays(30)->toDateString(),
                'category_id' => $po->category_id ?? 0,
                'order_number' => $po->po_number,
                'status' => 1, // Open
                'shipping_display' => $po->shipping_display,
                'discount_apply' => $po->discount_apply,
                'created_by' => $request->user()->id,
            ]);

            foreach ($po->products as $item) {
                BillProduct::create([
                    'bill_id' => $bill->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'tax' => $item->tax,
                    'discount' => $item->discount,
                    'price' => $item->price,
                    'description' => $item->description,
                ]);
            }

            // Mark PO as Billed (status = 2)
            $po->update(['status' => 2]);

            DB::commit();

            return (new BillResource($bill->load(['vender', 'products'])))
                ->additional(['message' => 'Purchase Order converted to Bill successfully'])
                ->response()
                ->setStatusCode(201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to convert PO to Bill', 'error' => $e->getMessage()], 500);
        }
    }
}
