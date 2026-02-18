<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransferResource;
use App\Models\Transfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransferController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Transfer::where('created_by', $request->user()->creatorId());

        // Date range filter
        if ($request->has('date')) {
            if (str_contains($request->date, ' to ')) {
                $dateRange = explode(' to ', $request->date);
                $query->whereBetween('date', $dateRange);
            } else {
                $query->where('date', $request->date);
            }
        }

        // From account filter
        if ($request->has('from_account')) {
            $query->where('from_account', $request->from_account);
        }

        // To account filter
        if ($request->has('to_account')) {
            $query->where('to_account', $request->to_account);
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $transfers = $query->with(['creator', 'fromBankAccount', 'toBankAccount'])->paginate($perPage);

        return TransferResource::collection($transfers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_account' => 'required|integer|exists:bank_accounts,id',
            'to_account' => 'required|integer|exists:bank_accounts,id|different:from_account',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'payment_method' => 'nullable|integer',
            'reference' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $transfer = Transfer::create([
            'from_account' => $request->from_account,
            'to_account' => $request->to_account,
            'amount' => $request->amount,
            'date' => $request->date,
            'payment_method' => $request->payment_method ?? 0,
            'reference' => $request->reference,
            'description' => $request->description,
            'created_by' => $request->user()->creatorId(),
        ]);

        return (new TransferResource($transfer->load(['creator', 'fromBankAccount', 'toBankAccount'])))
            ->additional(['message' => 'Transfer created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $transfer = Transfer::where('created_by', $request->user()->creatorId())
            ->with(['creator', 'fromBankAccount', 'toBankAccount'])
            ->findOrFail($id);

        return new TransferResource($transfer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $transfer = Transfer::where('created_by', $request->user()->creatorId())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'from_account' => 'sometimes|required|integer|exists:bank_accounts,id',
            'to_account' => 'sometimes|required|integer|exists:bank_accounts,id|different:from_account',
            'amount' => 'sometimes|required|numeric|min:0.01',
            'date' => 'sometimes|required|date',
            'payment_method' => 'nullable|integer',
            'reference' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $transfer->update($request->only([
            'from_account',
            'to_account',
            'amount',
            'date',
            'payment_method',
            'reference',
            'description',
        ]));

        return (new TransferResource($transfer->load(['creator', 'fromBankAccount', 'toBankAccount'])))
            ->additional(['message' => 'Transfer updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $transfer = Transfer::where('created_by', $request->user()->creatorId())->findOrFail($id);
        $transfer->delete();

        return response()->json([
            'message' => 'Transfer deleted successfully'
        ]);
    }
}
