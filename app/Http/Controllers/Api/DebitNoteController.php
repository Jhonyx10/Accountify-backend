<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DebitNoteResource;
use App\Models\DebitNote;
use App\Models\Bill;
use App\Models\Vender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DebitNoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DebitNote::query();

        // Filter by bill
        if ($request->has('bill')) {
            $query->where('bill', $request->bill);
        }

        // Filter by vendor
        if ($request->has('vendor')) {
            $query->where('vendor', $request->vendor);
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
        $debitNotes = $query->with(['billRelation', 'vendorRelation'])->latest()->paginate($perPage);

        return DebitNoteResource::collection($debitNotes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bill' => 'required|exists:bills,id',
            'vendor' => 'required|exists:venders,id',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verify bill and vendor belong to the user
        $bill = Bill::where('id', $request->bill)
            ->where('created_by', $request->user()->creatorId())
            ->first();

        if (!$bill) {
            return response()->json(['errors' => ['bill' => ['Bill not found or access denied']]], 422);
        }

        $vendor = Vender::where('id', $request->vendor)
            ->where('created_by', $request->user()->creatorId())
            ->first();

        if (!$vendor) {
            return response()->json(['errors' => ['vendor' => ['Vendor not found or access denied']]], 422);
        }

        $debitNote = DebitNote::create($request->only([
            'bill',
            'vendor',
            'amount',
            'date',
            'description',
        ]));

        return (new DebitNoteResource($debitNote->load(['billRelation', 'vendorRelation'])))
            ->additional(['message' => 'Debit note created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $debitNote = DebitNote::with(['billRelation', 'vendorRelation'])->findOrFail($id);

        // Verify access through bill
        $bill = Bill::where('id', $debitNote->bill)
            ->where('created_by', $request->user()->creatorId())
            ->first();

        if (!$bill) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        return new DebitNoteResource($debitNote);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $debitNote = DebitNote::findOrFail($id);

        // Verify access through bill
        $bill = Bill::where('id', $debitNote->bill)
            ->where('created_by', $request->user()->creatorId())
            ->first();

        if (!$bill) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'bill' => 'sometimes|required|exists:bills,id',
            'vendor' => 'sometimes|required|exists:venders,id',
            'amount' => 'sometimes|required|numeric|min:0',
            'date' => 'sometimes|required|date',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $debitNote->update($request->only([
            'bill',
            'vendor',
            'amount',
            'date',
            'description',
        ]));

        return (new DebitNoteResource($debitNote->load(['billRelation', 'vendorRelation'])))
            ->additional(['message' => 'Debit note updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $debitNote = DebitNote::findOrFail($id);

        // Verify access through bill
        $bill = Bill::where('id', $debitNote->bill)
            ->where('created_by', $request->user()->creatorId())
            ->first();

        if (!$bill) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $debitNote->delete();

        return response()->json([
            'message' => 'Debit note deleted successfully'
        ]);
    }
}
