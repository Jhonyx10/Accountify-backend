<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProposalResource;
use App\Models\Proposal;
use Illuminate\Http\Request;
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
        if ($request->has('status')) {
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
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('proposal_id', 'LIKE', "%{$search}%");
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $proposals = $query->with(['customer', 'creator'])->withCount('products')->latest()->paginate($perPage);

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
            'send_date' => 'nullable|date',
            'category_id' => 'required|integer',
            'status' => 'nullable|integer',
            'discount_apply' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Generate proposal_id
        $lastProposal = Proposal::where('created_by', $request->user()->creatorId())->latest('proposal_id')->first();
        $proposalId = $lastProposal ? $lastProposal->proposal_id + 1 : 1;

        $proposal = Proposal::create([
            'proposal_id' => $proposalId,
            'customer_id' => $request->customer_id,
            'issue_date' => $request->issue_date,
            'send_date' => $request->send_date,
            'category_id' => $request->category_id,
            'status' => $request->status ?? 0,
            'discount_apply' => $request->discount_apply ?? 0,
            'is_convert' => 0,
            'converted_invoice_id' => 0,
            'converted_retainer_id' => 0,
            'created_by' => $request->user()->creatorId(),
        ]);

        return (new ProposalResource($proposal->load(['customer', 'creator'])))
            ->additional(['message' => 'Proposal created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $proposal = Proposal::where('created_by', $request->user()->creatorId())
            ->with(['customer', 'creator', 'products'])
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

        $validator = Validator::make($request->all(), [
            'customer_id' => 'sometimes|required|exists:customers,id',
            'issue_date' => 'sometimes|required|date',
            'send_date' => 'nullable|date',
            'category_id' => 'sometimes|required|integer',
            'status' => 'nullable|integer',
            'discount_apply' => 'nullable|integer',
            'is_convert' => 'nullable|integer',
            'converted_invoice_id' => 'nullable|integer',
            'converted_retainer_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $proposal->update($request->only([
            'customer_id',
            'issue_date',
            'send_date',
            'category_id',
            'status',
            'discount_apply',
            'is_convert',
            'converted_invoice_id',
            'converted_retainer_id',
        ]));

        return (new ProposalResource($proposal->load(['customer', 'creator'])))
            ->additional(['message' => 'Proposal updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $proposal = Proposal::where('created_by', $request->user()->creatorId())->findOrFail($id);
        $proposal->delete();

        return response()->json([
            'message' => 'Proposal deleted successfully'
        ]);
    }
}
