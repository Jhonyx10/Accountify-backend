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
        $query = Invoice::with(['customer', 'creator', 'category', 'products']);

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
            'category_id' => 'nullable|integer',
            'ref_number' => 'nullable|string',
            'status' => 'nullable|integer',
            'notes' => 'nullable|string',
            'shipping_display' => 'nullable|boolean',
            'discount_apply' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|integer',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0',
            'items.*.description' => 'nullable|string',
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
            'notes' => $request->notes,
            'shipping_display' => $request->shipping_display ?? 1,
            'discount_apply' => $request->discount_apply ?? 0,
            'created_by' => $request->user()->id,
        ]);

        if ($request->has('items')) {
            foreach ($request->items as $item) {
                $invoice->products()->create([
                    'product_id' => $item['product_id'] ?? 0,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'tax' => $item['tax_rate'] ?? 0,
                    'description' => $item['description'] ?? '',
                ]);
            }
        }

        return (new InvoiceResource($invoice->load(['customer', 'creator', 'products'])))
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
            'category_id' => 'nullable|integer',
            'status' => 'sometimes|integer',
            'notes' => 'nullable|string',
            'items' => 'sometimes|array',
            'items.*.product_id' => 'nullable|integer',
            'items.*.quantity' => 'required_with:items|numeric|min:1',
            'items.*.price' => 'required_with:items|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $invoice->update($request->except(['invoice_id', 'created_by', 'items']));

        if ($request->has('items')) {
            $invoice->products()->delete(); // Remove old items
            foreach ($request->items as $item) {
                $invoice->products()->create([
                    'product_id' => $item['product_id'] ?? 0,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'tax' => $item['tax_rate'] ?? 0,
                    'description' => $item['description'] ?? '',
                ]);
            }
        }

        return (new InvoiceResource($invoice->load(['customer', 'creator', 'products'])))
            ->additional(['message' => 'Invoice updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->delete();

        return response()->json([
            'message' => 'Invoice deleted successfully'
        ]);
    }

    /**
     * Send the invoice via email.
     */
    public function send(Request $request, string $id)
    {
        $invoice = Invoice::with(['customer', 'products'])->findOrFail($id);

        if (!$invoice->customer || !$invoice->customer->email) {
            return response()->json(['message' => 'Customer email not found'], 400);
        }

        // Fetch settings for branding
        $settings = \App\Models\Setting::where('created_by', $invoice->created_by)
            ->pluck('value', 'name')
            ->toArray();

        // Calculate totals
        $subtotal = 0;
        $totalTax = 0;
        foreach ($invoice->products as $product) {
            $itemSubtotal = $product->quantity * $product->price;
            $subtotal += $itemSubtotal;
            $totalTax += $itemSubtotal * ((float)$product->tax / 100);
        }
        $invoice->subtotal = $subtotal;
        $invoice->total_tax = $totalTax;
        $invoice->grand_total = $subtotal + $totalTax;

        $data = [
            'invoice' => $invoice,
            'settings' => $settings,
        ];

        try {
            // Generate PDF
            $pdfContent = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', $data)->output();

            // Send Email
            \Illuminate\Support\Facades\Mail::to($invoice->customer->email)->send(new \App\Mail\InvoiceMail($invoice, $pdfContent));

            // Update status to 'Sent' if draft
            if ($invoice->status == 0) {
                $invoice->update(['status' => 1, 'send_date' => now()]);
            }

            return response()->json(['message' => 'Invoice sent successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to send invoice: ' . $e->getMessage()], 500);
        }
    }
}
