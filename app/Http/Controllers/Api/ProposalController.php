<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProposalResource;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Proposal;
use App\Models\ProposalProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProposalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Proposal::where('created_by', $request->user()->creatorId());

        // Filter by customer
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by status
        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter by conversion status
        if ($request->has('is_convert')) {
            $query->where('is_convert', $request->is_convert);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('issue_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('issue_date', '<=', $request->to_date);
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('proposal_id', 'LIKE', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $proposals = $query->with(['customer', 'creator', 'category', 'products.product'])
            ->withCount('products')
            ->latest()
            ->paginate($perPage);

        return ProposalResource::collection($proposals);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'send_date' => 'nullable|date',
            'category_id' => 'required|integer',
            'status' => 'nullable|integer|in:0,1,2,3',
            'discount_apply' => 'nullable|integer',
            'notes' => 'nullable|string|max:2000',
            // Line items
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:product_services,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax' => 'nullable|numeric',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            Log::error('Proposal Validation Failed', [
            'errors' => $validator->errors()->toArray(),
            'payload' => $request->all()
        ]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return DB::transaction(function () use ($request) {
            // Generate proposal_id
            $lastProposal = Proposal::where('created_by', $request->user()->creatorId())
                ->latest('proposal_id')
                ->first();
            $proposalId = $lastProposal ? $lastProposal->proposal_id + 1 : 1;

            $proposal = Proposal::create([
                'proposal_id' => $proposalId,
                'customer_id' => $request->customer_id,
                'issue_date' => $request->issue_date,
                'due_date' => $request->due_date,
                'send_date' => $request->send_date,
                'category_id' => $request->category_id,
                'status' => $request->status ?? Proposal::STATUS_DRAFT,
                'discount_apply' => $request->discount_apply ?? 0,
                'notes' => $request->notes,
                'is_convert' => 0,
                'converted_invoice_id' => 0,
                'converted_retainer_id' => 0,
                'created_by' => $request->user()->creatorId(),
            ]);

            // Create line items
            foreach ($request->items as $item) {
                ProposalProduct::create([
                    'proposal_id' => $proposal->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'tax' => $item['tax'] ?? null,
                    'discount' => $item['discount'] ?? 0,
                    'description' => $item['description'] ?? null,
                ]);
            }

            return (new ProposalResource($proposal->load(['customer', 'creator', 'products.product'])))
                ->additional(['message' => 'Proposal created successfully'])
                ->response()
                ->setStatusCode(201);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $proposal = Proposal::where('created_by', $request->user()->creatorId())
            ->with(['customer', 'creator', 'category', 'products.product', 'convertedInvoice'])
            ->withCount('products')
            ->findOrFail($id);

        return new ProposalResource($proposal);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $proposal = Proposal::where('created_by', $request->user()->creatorId())->findOrFail($id);

        // Prevent editing converted proposals
        if ($proposal->is_convert) {
            return response()->json([
                'message' => 'Cannot edit a proposal that has already been converted to an invoice.'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => 'sometimes|required|exists:customers,id',
            'issue_date' => 'sometimes|required|date',
            'due_date' => 'nullable|date',
            'send_date' => 'nullable|date',
            'category_id' => 'sometimes|required|integer',
            'status' => 'nullable|integer|in:0,1,2,3',
            'discount_apply' => 'nullable|integer',
            'notes' => 'nullable|string|max:2000',
            // Line items (optional on update)
            'items' => 'sometimes|array|min:1',
            'items.*.product_id' => 'required_with:items|exists:product_services,id',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.price' => 'required_with:items|numeric|min:0',
            'items.*.tax' => 'nullable|string',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return DB::transaction(function () use ($request, $proposal) {
            $proposal->update($request->only([
                'customer_id',
                'issue_date',
                'due_date',
                'send_date',
                'category_id',
                'status',
                'discount_apply',
                'notes',
            ]));

            // If items are provided, sync them (delete old, create new)
            if ($request->has('items')) {
                $proposal->products()->delete();

                foreach ($request->items as $item) {
                    ProposalProduct::create([
                        'proposal_id' => $proposal->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'tax' => $item['tax'] ?? null,
                        'discount' => $item['discount'] ?? 0,
                        'description' => $item['description'] ?? null,
                    ]);
                }
            }

            return (new ProposalResource($proposal->load(['customer', 'creator', 'products.product'])))
                ->additional(['message' => 'Proposal updated successfully']);
        });
    }

    /**
     * Update the status of a proposal (approval workflow).
     * Valid transitions: Draft -> Sent -> Accepted/Declined
     */
    public function updateStatus(Request $request, string $id)
    {
        $proposal = Proposal::where('created_by', $request->user()->creatorId())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|integer|in:0,1,2,3',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $newStatus = (int) $request->status;
        $currentStatus = $proposal->status;

        // Validate status transitions
        $allowedTransitions = [
            Proposal::STATUS_DRAFT    => [Proposal::STATUS_SENT],
            Proposal::STATUS_SENT     => [Proposal::STATUS_ACCEPTED, Proposal::STATUS_DECLINED],
            Proposal::STATUS_DECLINED => [Proposal::STATUS_DRAFT], // Allow re-opening declined proposals
        ];

        if (!isset($allowedTransitions[$currentStatus]) || !in_array($newStatus, $allowedTransitions[$currentStatus])) {
            $currentLabel = Proposal::STATUS_MAP[$currentStatus] ?? 'Unknown';
            $newLabel = Proposal::STATUS_MAP[$newStatus] ?? 'Unknown';

            return response()->json([
                'message' => "Cannot transition from '{$currentLabel}' to '{$newLabel}'.",
                'allowed_transitions' => collect($allowedTransitions[$currentStatus] ?? [])
                    ->map(fn($s) => Proposal::STATUS_MAP[$s])
                    ->values(),
            ], 422);
        }

        // Auto-set send_date when status changes to Sent
        $updateData = ['status' => $newStatus];
        if ($newStatus === Proposal::STATUS_SENT && !$proposal->send_date) {
            $updateData['send_date'] = now();
        }

        $proposal->update($updateData);

        return (new ProposalResource($proposal->load(['customer', 'creator', 'products.product'])))
            ->additional([
                'message' => 'Proposal status updated to ' . Proposal::STATUS_MAP[$newStatus],
            ]);
    }

    /**
     * Convert an accepted proposal to an invoice.
     */
    public function convertToInvoice(Request $request, string $id)
    {
        $proposal = Proposal::where('created_by', $request->user()->creatorId())
            ->with(['products'])
            ->findOrFail($id);

        // Must be accepted
        if ($proposal->status !== Proposal::STATUS_ACCEPTED) {
            return response()->json([
                'message' => 'Only accepted proposals can be converted to invoices.',
            ], 422);
        }

        // Must not be already converted
        if ($proposal->is_convert) {
            return response()->json([
                'message' => 'This proposal has already been converted to an invoice.',
                'converted_invoice_id' => $proposal->converted_invoice_id,
            ], 422);
        }

        return DB::transaction(function () use ($request, $proposal) {
            // Generate invoice_id
            $creatorId = $request->user()->creatorId();
            $lastInvoice = Invoice::where('created_by', $creatorId)
                ->latest('invoice_id')
                ->first();
            $invoiceId = $lastInvoice ? $lastInvoice->invoice_id + 1 : 1;

            // Create the invoice from proposal data
            $invoice = Invoice::create([
                'invoice_id' => $invoiceId,
                'customer_id' => $proposal->customer_id,
                'issue_date' => now(),
                'due_date' => $proposal->due_date ?? now()->addDays(30),
                'send_date' => null,
                'category_id' => $proposal->category_id,
                'ref_number' => 'PROP-' . str_pad($proposal->proposal_id, 5, '0', STR_PAD_LEFT),
                'status' => 0, // Draft
                'shipping_display' => 1,
                'discount_apply' => $proposal->discount_apply,
                'created_by' => $creatorId,
            ]);

            // Copy all line items from proposal to invoice
            foreach ($proposal->products as $proposalProduct) {
                InvoiceProduct::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $proposalProduct->product_id,
                    'quantity' => $proposalProduct->quantity,
                    'price' => $proposalProduct->price,
                    'tax' => $proposalProduct->tax,
                    'discount' => $proposalProduct->discount,
                    'description' => $proposalProduct->description,
                ]);
            }

            // Mark the proposal as converted
            $proposal->update([
                'is_convert' => 1,
                'converted_invoice_id' => $invoice->id,
            ]);

            return response()->json([
                'message' => 'Proposal successfully converted to invoice.',
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoiceId,
                'proposal' => new ProposalResource($proposal->fresh()->load(['customer', 'creator', 'products.product'])),
            ], 201);
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $proposal = Proposal::where('created_by', $request->user()->creatorId())->findOrFail($id);

        // Prevent deleting converted proposals
        if ($proposal->is_convert) {
            return response()->json([
                'message' => 'Cannot delete a proposal that has been converted to an invoice.',
            ], 422);
        }

        DB::transaction(function () use ($proposal) {
            $proposal->products()->delete();
            $proposal->delete();
        });

        return response()->json([
            'message' => 'Proposal deleted successfully'
        ]);
    }
}
