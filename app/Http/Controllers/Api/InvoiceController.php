<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['customer', 'creator', 'category']);

        // Filter by created_by (multi-tenancy)
        if ($request->user()) {
            $query->where('created_by', $request->user()->id);
        }

        // Filter by customer
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('issue_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('issue_date', '<=', $request->to_date);
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_id', 'like', "%{$search}%")
                  ->orWhere('ref_number', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $invoices = $query->latest()->paginate($perPage);

        return InvoiceResource::collection($invoices);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'category_id' => 'required|integer',
            'ref_number' => 'nullable|string',
            'status' => 'nullable|integer',
            'shipping_display' => 'nullable|boolean',
            'discount_apply' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Generate invoice_id
        $lastInvoice = Invoice::where('created_by', $request->user()->id)->latest('invoice_id')->first();
        $invoiceId = $lastInvoice ? $lastInvoice->invoice_id + 1 : 1;

        $invoice = Invoice::create([
            'invoice_id' => $invoiceId,
            'customer_id' => $request->customer_id,
            'issue_date' => $request->issue_date,
            'due_date' => $request->due_date,
            'send_date' => $request->send_date,
            'category_id' => $request->category_id,
            'ref_number' => $request->ref_number,
            'status' => $request->status ?? 0,
            'shipping_display' => $request->shipping_display ?? 1,
            'discount_apply' => $request->discount_apply ?? 0,
            'created_by' => $request->user()->id,
        ]);

        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $item) {
                if (empty($item['item'])) continue;

                \App\Models\InvoiceProduct::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['item'],
                    'quantity' => $item['quantity'] ?? 1,
                    'price' => $item['price'] ?? 0,
                    'tax' => $item['tax'] ?? 0,
                    'discount' => $item['discount'] ?? 0,
                    'description' => $item['description'] ?? '',
                ]);

                // Decrease stock
                \App\Models\StockReport::create([
                    'product_id' => $item['item'],
                    'quantity' => $item['quantity'] ?? 1,
                    'type' => 'invoice',
                    'type_id' => $invoice->id,
                    'description' => 'Invoice ' . $invoice->invoice_id,
                    'created_by' => $request->user()->id,
                ]);
            }
        }

        return (new InvoiceResource($invoice->load(['customer', 'creator'])))
            ->additional(['message' => 'Invoice created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $invoice = Invoice::with(['customer', 'creator', 'products', 'payments'])->findOrFail($id);

        return new InvoiceResource($invoice);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $invoice = Invoice::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'customer_id' => 'sometimes|required|exists:customers,id',
            'issue_date' => 'sometimes|required|date',
            'due_date' => 'sometimes|required|date',
            'category_id' => 'sometimes|required|integer',
            'status' => 'sometimes|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $invoice->update($request->except(['invoice_id', 'created_by']));

        if ($request->has('items') && is_array($request->items)) {
            \App\Models\StockReport::where('type', 'invoice')->where('type_id', $invoice->id)->delete();
            \App\Models\InvoiceProduct::where('invoice_id', $invoice->id)->delete();

            foreach ($request->items as $item) {
                if (empty($item['item'])) continue;

                \App\Models\InvoiceProduct::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['item'],
                    'quantity' => $item['quantity'] ?? 1,
                    'price' => $item['price'] ?? 0,
                    'tax' => $item['tax'] ?? 0,
                    'discount' => $item['discount'] ?? 0,
                    'description' => $item['description'] ?? '',
                ]);

                \App\Models\StockReport::create([
                    'product_id' => $item['item'],
                    'quantity' => $item['quantity'] ?? 1,
                    'type' => 'invoice',
                    'type_id' => $invoice->id,
                    'description' => 'Invoice ' . $invoice->invoice_id . ' Update',
                    'created_by' => $request->user()->id,
                ]);
            }
        }

        return (new InvoiceResource($invoice->load(['customer', 'creator'])))
            ->additional(['message' => 'Invoice updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $invoice = Invoice::findOrFail($id);
        
        \App\Models\StockReport::where('type', 'invoice')->where('type_id', $invoice->id)->delete();
        \App\Models\InvoiceProduct::where('invoice_id', $invoice->id)->delete();
        
        $invoice->delete();

        return response()->json([
            'message' => 'Invoice deleted successfully'
        ]);
    }
}
