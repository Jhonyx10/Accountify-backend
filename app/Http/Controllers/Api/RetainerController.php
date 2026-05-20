<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RetainerResource;
use App\Models\Retainer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RetainerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Retainer::where('created_by', $request->user()->creatorId());

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
            $query->where('retainer_id', 'LIKE', "%{$search}%");
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $retainers = $query->with(['customer', 'creator'])
            ->withCount(['products', 'payments'])
            ->latest()
            ->paginate($perPage);

        return RetainerResource::collection($retainers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Creating new retainer with data:', $request->all());

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'send_date' => 'nullable|date',
            'category_id' => 'required|integer',
            'status' => 'nullable|integer',
            'discount_apply' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Generate retainer_id
        $lastRetainer = Retainer::where('created_by', $request->user()->creatorId())->latest('retainer_id')->first();
        $retainerId = $lastRetainer ? $lastRetainer->retainer_id + 1 : 1;

        $retainer = Retainer::create([
            'retainer_id' => $retainerId,
            'customer_id' => $request->customer_id,
            'issue_date' => $request->issue_date,
            'due_date' => $request->due_date,
            'send_date' => $request->send_date,
            'category_id' => $request->category_id,
            'status' => $request->status ?? 0,
            'discount_apply' => $request->discount_apply ?? 0,
            'converted_invoice_id' => 0,
            'is_convert' => 0,
            'created_by' => $request->user()->creatorId(),
        ]);

        return (new RetainerResource($retainer->load(['customer', 'creator'])))
            ->additional(['message' => 'Retainer created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $retainer = Retainer::where('created_by', $request->user()->creatorId())
            ->with(['customer', 'creator', 'products', 'payments'])
            ->withCount(['products', 'payments'])
            ->findOrFail($id);

        return new RetainerResource($retainer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $retainer = Retainer::where('created_by', $request->user()->creatorId())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'customer_id' => 'sometimes|required|exists:customers,id',
            'issue_date' => 'sometimes|required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'send_date' => 'nullable|date',
            'category_id' => 'sometimes|required|integer',
            'status' => 'nullable|integer',
            'discount_apply' => 'nullable|integer',
            'converted_invoice_id' => 'nullable|integer',
            'is_convert' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $retainer->update($request->only([
            'customer_id',
            'issue_date',
            'due_date',
            'send_date',
            'category_id',
            'status',
            'discount_apply',
            'converted_invoice_id',
            'is_convert',
        ]));

        return (new RetainerResource($retainer->load(['customer', 'creator'])))
            ->additional(['message' => 'Retainer updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $retainer = Retainer::where('created_by', $request->user()->creatorId())->findOrFail($id);
        $retainer->delete();

        return response()->json([
            'message' => 'Retainer deleted successfully'
        ]);
    }
}
