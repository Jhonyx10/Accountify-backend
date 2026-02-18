<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditNoteResource;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CreditNoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get credit notes through invoices/customers created by user
        $query = CreditNote::query();

        // Filter by invoice (which filters by creator)
        if ($request->has('invoice')) {
            $query->where('invoice', $request->invoice);
        }

        // Filter by customer
        if ($request->has('customer')) {
            $query->where('customer', $request->customer);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('description', 'LIKE', "%{$search}%");
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $creditNotes = $query->with(['invoiceRelation', 'customerRelation'])->latest()->paginate($perPage);

        return CreditNoteResource::collection($creditNotes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice' => 'required|exists:invoices,id',
            'customer' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verify invoice and customer belong to the user
        $invoice = Invoice::where('id', $request->invoice)
            ->where('created_by', $request->user()->creatorId())
            ->first();

        if (!$invoice) {
            return response()->json(['errors' => ['invoice' => ['Invoice not found or access denied']]], 422);
        }

        $customer = Customer::where('id', $request->customer)
            ->where('created_by', $request->user()->creatorId())
            ->first();

        if (!$customer) {
            return response()->json(['errors' => ['customer' => ['Customer not found or access denied']]], 422);
        }

        $creditNote = CreditNote::create($request->only([
            'invoice',
            'customer',
            'amount',
            'date',
            'description',
        ]));

        return (new CreditNoteResource($creditNote->load(['invoiceRelation', 'customerRelation'])))
            ->additional(['message' => 'Credit note created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $creditNote = CreditNote::with(['invoiceRelation', 'customerRelation'])->findOrFail($id);

        // Verify access through invoice
        $invoice = Invoice::where('id', $creditNote->invoice)
            ->where('created_by', $request->user()->creatorId())
            ->first();

        if (!$invoice) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        return new CreditNoteResource($creditNote);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $creditNote = CreditNote::findOrFail($id);

        // Verify access through invoice
        $invoice = Invoice::where('id', $creditNote->invoice)
            ->where('created_by', $request->user()->creatorId())
            ->first();

        if (!$invoice) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'invoice' => 'sometimes|required|exists:invoices,id',
            'customer' => 'sometimes|required|exists:customers,id',
            'amount' => 'sometimes|required|numeric|min:0',
            'date' => 'sometimes|required|date',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $creditNote->update($request->only([
            'invoice',
            'customer',
            'amount',
            'date',
            'description',
        ]));

        return (new CreditNoteResource($creditNote->load(['invoiceRelation', 'customerRelation'])))
            ->additional(['message' => 'Credit note updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $creditNote = CreditNote::findOrFail($id);

        // Verify access through invoice
        $invoice = Invoice::where('id', $creditNote->invoice)
            ->where('created_by', $request->user()->creatorId())
            ->first();

        if (!$invoice) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $creditNote->delete();

        return response()->json([
            'message' => 'Credit note deleted successfully'
        ]);
    }
}
