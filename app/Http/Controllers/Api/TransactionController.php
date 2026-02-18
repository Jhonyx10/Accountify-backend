<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    /**
     * Display a listing of transactions
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = Transaction::with(['creator', 'bankAccount', 'user', 'payment']);

        // Multi-tenancy filtering
        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        // Search by description or category
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Filter by account
        if ($request->has('account')) {
            $query->where('account', $request->account);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $perPage = $request->input('per_page', 15);
        $transactions = $query->latest('date')->paginate($perPage);

        return TransactionResource::collection($transactions);
    }

    /**
     * Store a newly created transaction
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'user_type' => 'required|string|max:255',
            'account' => 'required|integer|exists:bank_accounts,id',
            'type' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'payment_id' => 'nullable|integer',
            'category' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $transaction = Transaction::create([
            'user_id' => $request->user_id,
            'user_type' => $request->user_type,
            'account' => $request->account,
            'type' => $request->type,
            'amount' => $request->amount,
            'description' => $request->description,
            'date' => $request->date,
            'created_by' => $request->user()->creatorId(),
            'payment_id' => $request->payment_id ?? 0,
            'category' => $request->category,
        ]);

        return (new TransactionResource($transaction->load(['creator', 'bankAccount', 'user', 'payment'])))
            ->additional(['message' => 'Transaction created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified transaction
     */
    public function show(Request $request, string $id)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = Transaction::with(['creator', 'bankAccount', 'user', 'payment']);

        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        $transaction = $query->findOrFail($id);

        return new TransactionResource($transaction);
    }

    /**
     * Update the specified transaction
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = Transaction::query();

        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        $transaction = $query->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|required|integer|exists:users,id',
            'user_type' => 'sometimes|required|string|max:255',
            'account' => 'sometimes|required|integer|exists:bank_accounts,id',
            'type' => 'sometimes|required|string|max:255',
            'amount' => 'sometimes|required|numeric|min:0',
            'description' => 'nullable|string',
            'date' => 'sometimes|required|date',
            'payment_id' => 'nullable|integer',
            'category' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $transaction->update($request->only([
            'user_id',
            'user_type',
            'account',
            'type',
            'amount',
            'description',
            'date',
            'payment_id',
            'category',
        ]));

        return (new TransactionResource($transaction->load(['creator', 'bankAccount', 'user', 'payment'])))
            ->additional(['message' => 'Transaction updated successfully']);
    }

    /**
     * Remove the specified transaction
     */
    public function destroy(Request $request, string $id)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = Transaction::query();

        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        $transaction = $query->findOrFail($id);
        $transaction->delete();

        return response()->json([
            'message' => 'Transaction deleted successfully'
        ]);
    }
}

