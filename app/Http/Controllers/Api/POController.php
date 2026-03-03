<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\POResource;
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

        if ($request->user()) {
            $query->where('created_by', $request->user()->id);
        }

        $perPage = $request->input('per_page', 15);
        $pos = $query->with(['vender', 'items'])->latest()->paginate($perPage);

        return POResource::collection($pos);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vender_id' => 'required|integer',
            'po_date' => 'required|date',
            'delivery_date' => 'nullable|date',
            'status' => 'nullable|integer',
            'category_id' => 'nullable|integer',
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

            return (new POResource($po))
                ->additional(['message' => 'Purchase Order created successfully'])
                ->response()
                ->setStatusCode(201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating PO', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        $po = PurchaseOrder::findOrFail($id);
        $items = PurchaseOrderProduct::where('purchase_order_id', $po->id)->get();
        $po->setAttribute('items', $items);
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

            return (new POResource($po))
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
}
