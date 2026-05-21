<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BillResource;
use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BillController extends Controller
{
    public function index(Request $request)
    {
        $query = Bill::with(['vender', 'creator', 'category', 'products', 'accounts']);

        if ($request->user()) {
            $query->where('created_by', $request->user()->id);
        }

        if ($request->has('vender_id')) {
            $query->where('vender_id', $request->vender_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('from_date')) {
            $query->whereDate('bill_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('bill_date', '<=', $request->to_date);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('bill_id', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $bills = $query->latest()->paginate($perPage);

        return BillResource::collection($bills);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vender_id' => 'required|exists:venders,id',
            'bill_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:bill_date',
            'category_id' => 'required|integer',
            'status' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        \Illuminate\Support\Facades\Log::info('Creating new bill with data: ' . json_encode($request->all()));

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $lastBill = Bill::where('created_by', $request->user()->id)->latest('id')->first();
            $billId = $lastBill ? ((int) $lastBill->bill_id + 1) : 1;

            $bill = Bill::create([
                'bill_id' => (string) $billId,
                'vender_id' => $request->vender_id,
                'bill_date' => $request->bill_date,
                'due_date' => $request->due_date,
                'send_date' => $request->send_date,
                'category_id' => $request->category_id,
                'order_number' => $request->order_number ?? 0,
                'status' => $request->status ?? 0,
                'shipping_display' => $request->shipping_display ?? 1,
                'discount_apply' => $request->discount_apply ?? 0,
                'notes' => $request->notes,
                'created_by' => $request->user()->id,
            ]);

            if ($request->has('items') && is_array($request->items)) {
                foreach ($request->items as $item) {
                    if (empty($item['product_id'])) continue;

                    \App\Models\BillProduct::create([
                        'bill_id' => $bill->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'] ?? 1,
                        'price' => $item['price'] ?? 0,
                        'tax' => $item['tax_rate'] ?? 0,
                        'discount' => $item['discount'] ?? 0,
                        'description' => $item['description'] ?? '',
                    ]);

                    // Increase stock
                    \App\Models\StockReport::create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'] ?? 1,
                        'type' => 'bill',
                        'type_id' => $bill->id,
                        'description' => 'Bill ' . $bill->bill_id,
                        'created_by' => $request->user()->id,
                    ]);
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            return (new BillResource($bill->load(['vender', 'creator'])))
                ->additional(['message' => 'Bill created successfully'])
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Bill Store exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['message' => 'Error creating Bill', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        $bill = Bill::with(['vender', 'creator', 'products', 'category', 'payments.account'])->findOrFail($id);

        return new BillResource($bill);
    }

    public function update(Request $request, string $id)
    {
        $bill = Bill::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'vender_id' => 'sometimes|required|exists:venders,id',
            'bill_date' => 'sometimes|required|date',
            'due_date' => 'sometimes|required|date',
            'category_id' => 'sometimes|required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $bill->update($request->except(['bill_id', 'created_by']));

        if ($request->has('items') && is_array($request->items)) {
            \App\Models\StockReport::where('type', 'bill')->where('type_id', $bill->id)->delete();
            \App\Models\BillProduct::where('bill_id', $bill->id)->delete();

            foreach ($request->items as $item) {
                if (empty($item['product_id'])) continue;

                \App\Models\BillProduct::create([
                    'bill_id' => $bill->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'] ?? 1,
                    'price' => $item['price'] ?? 0,
                    'tax' => $item['tax_rate'] ?? 0,
                    'discount' => $item['discount'] ?? 0,
                    'description' => $item['description'] ?? '',
                ]);

                \App\Models\StockReport::create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'] ?? 1,
                    'type' => 'bill',
                    'type_id' => $bill->id,
                    'description' => 'Bill ' . $bill->bill_id . ' Update',
                    'created_by' => $request->user()->id,
                ]);
            }
        }

        return (new BillResource($bill->load(['vender', 'creator'])))
            ->additional(['message' => 'Bill updated successfully']);
    }

    public function destroy(string $id)
    {
        $bill = Bill::findOrFail($id);
        
        \App\Models\StockReport::where('type', 'bill')->where('type_id', $bill->id)->delete();
        \App\Models\BillProduct::where('bill_id', $bill->id)->delete();
        
        $bill->delete();

        return response()->json(['message' => 'Bill deleted successfully']);
    }
}
