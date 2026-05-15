<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransferResource;
use App\Models\BankAccount;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Transfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // Status filter
        if ($request->has('status') && $request->status !== 'All') {
            $query->where('status', $request->status);
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
        $transfers = $query->with(['creator', 'fromBankAccount', 'toBankAccount'])
            ->latest('date')
            ->paginate($perPage);

        return TransferResource::collection($transfers);
    }

    /**
     * Store a newly created resource in storage.
     * Creates the Transfer record and a double-entry Journal Entry.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_account'   => 'required|integer|exists:bank_accounts,id',
            'to_account'     => 'required|integer|exists:bank_accounts,id|different:from_account',
            'amount'         => 'required|numeric|min:0.01',
            'date'           => 'required|date',
            'payment_method' => 'nullable|integer',
            'reference'      => 'nullable|string|max:255',
            'description'    => 'nullable|string',
            'status'         => 'nullable|string|in:Completed,Pending,Failed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $creatorId   = $request->user()->creatorId();
        $fromAccount = BankAccount::with('chartAccount')->findOrFail($request->from_account);
        $toAccount   = BankAccount::with('chartAccount')->findOrFail($request->to_account);

        DB::beginTransaction();
        try {
            // 1. Create the Transfer record
            $transfer = Transfer::create([
                'from_account'   => $request->from_account,
                'to_account'     => $request->to_account,
                'amount'         => $request->amount,
                'date'           => $request->date,
                'payment_method' => $request->payment_method ?? 0,
                'reference'      => $request->reference,
                'description'    => $request->description,
                'status'         => $request->status ?? 'Completed',
                'created_by'     => $creatorId,
            ]);

            // 2. Create double-entry Journal Entry (if both accounts have linked Chart of Accounts)
            if ($fromAccount->chart_account_id && $toAccount->chart_account_id) {
                $journalId = (JournalEntry::where('created_by', $creatorId)->max('journal_id') ?? 0) + 1;

                $jDesc = $request->description
                    ? "Transfer: {$request->description}"
                    : "Bank Transfer – {$fromAccount->bank_name} to {$toAccount->bank_name}";

                $journalEntry = JournalEntry::create([
                    'date'        => $request->date,
                    'reference'   => $request->reference ?? "TRF-{$transfer->id}",
                    'description' => $jDesc,
                    'journal_id'  => $journalId,
                    'created_by'  => $creatorId,
                ]);

                // Debit destination (funds received — asset increases)
                JournalItem::create([
                    'journal'     => $journalEntry->id,
                    'account'     => $toAccount->chart_account_id,
                    'description' => "Transfer In – {$toAccount->bank_name}",
                    'debit'       => $request->amount,
                    'credit'      => 0,
                ]);

                // Credit source (funds sent — asset decreases)
                JournalItem::create([
                    'journal'     => $journalEntry->id,
                    'account'     => $fromAccount->chart_account_id,
                    'description' => "Transfer Out – {$fromAccount->bank_name}",
                    'debit'       => 0,
                    'credit'      => $request->amount,
                ]);

                // Link journal entry back to the transfer record
                $transfer->update(['journal_entry_id' => $journalEntry->id]);
            }

            DB::commit();

            return (new TransferResource($transfer->load(['creator', 'fromBankAccount', 'toBankAccount'])))
                ->additional(['message' => 'Transfer created successfully'])
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create transfer',
                'error'   => $e->getMessage(),
            ], 500);
        }
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
            'from_account'   => 'sometimes|required|integer|exists:bank_accounts,id',
            'to_account'     => 'sometimes|required|integer|exists:bank_accounts,id|different:from_account',
            'amount'         => 'sometimes|required|numeric|min:0.01',
            'date'           => 'sometimes|required|date',
            'payment_method' => 'nullable|integer',
            'reference'      => 'nullable|string|max:255',
            'description'    => 'nullable|string',
            'status'         => 'nullable|string|in:Completed,Pending,Failed',
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
            'status',
        ]));

        return (new TransferResource($transfer->load(['creator', 'fromBankAccount', 'toBankAccount'])))
            ->additional(['message' => 'Transfer updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     * Also removes linked Journal Entry and Items.
     */
    public function destroy(Request $request, string $id)
    {
        $transfer = Transfer::where('created_by', $request->user()->creatorId())->findOrFail($id);

        DB::beginTransaction();
        try {
            if ($transfer->journal_entry_id) {
                JournalItem::where('journal', $transfer->journal_entry_id)->delete();
                JournalEntry::where('id', $transfer->journal_entry_id)->delete();
            }

            $transfer->delete();
            DB::commit();

            return response()->json(['message' => 'Transfer deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete transfer',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
