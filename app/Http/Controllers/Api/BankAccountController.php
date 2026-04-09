<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BankAccountResource;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountType;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BankAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = BankAccount::where('created_by', $request->user()->creatorId());

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('holder_name', 'LIKE', "%{$search}%")
                  ->orWhere('bank_name', 'LIKE', "%{$search}%")
                  ->orWhere('account_number', 'LIKE', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        if ($perPage == -1) {
            $bankAccounts = $query->with(['creator', 'chartAccount.journalItems'])->get();
        } else {
            $bankAccounts = $query->with(['creator', 'chartAccount.journalItems'])->paginate($perPage);
        }

        return BankAccountResource::collection($bankAccounts);
    }

    /**
     * Find the equity account to use as the offsetting account for opening balances.
     * Prefers an account named "Opening Balance Equity", then falls back to any Equity account.
     */
    private function findEquityAccount(int $creatorId): ?ChartOfAccount
    {
        $equityTypeIds = ChartOfAccountType::where(function ($q) use ($creatorId) {
                $q->where('created_by', $creatorId)->orWhereNull('created_by');
            })
            ->whereRaw("LOWER(name) LIKE '%equity%'")
            ->pluck('id');

        if ($equityTypeIds->isEmpty()) {
            return null;
        }

        // Prefer "Opening Balance Equity" named account
        $account = ChartOfAccount::where('created_by', $creatorId)
            ->whereIn('type', $equityTypeIds)
            ->where('is_enabled', 1)
            ->whereRaw("LOWER(name) LIKE '%opening balance%'")
            ->first();

        // Fall back to any equity account
        if (!$account) {
            $account = ChartOfAccount::where('created_by', $creatorId)
                ->whereIn('type', $equityTypeIds)
                ->where('is_enabled', 1)
                ->first();
        }

        return $account;
    }

    /**
     * Post a double-entry journal entry for a bank account's opening balance.
     * Debit: the bank's linked chart account (asset increases)
     * Credit: an equity account (to balance the books)
     */
    private function createOpeningBalanceJournal(BankAccount $bankAccount, float $amount, int $creatorId): void
    {
        $equityAccount = $this->findEquityAccount($creatorId);
        $journalId     = (JournalEntry::where('created_by', $creatorId)->max('journal_id') ?? 0) + 1;

        $journalEntry = JournalEntry::create([
            'date'        => now()->toDateString(),
            'reference'   => "OB-{$bankAccount->id}",
            'description' => "Opening Balance – {$bankAccount->bank_name} ({$bankAccount->account_number})",
            'journal_id'  => $journalId,
            'created_by'  => $creatorId,
        ]);

        // Debit bank chart account (asset goes up)
        JournalItem::create([
            'journal'     => $journalEntry->id,
            'account'     => $bankAccount->chart_account_id,
            'description' => "Opening Balance – {$bankAccount->bank_name}",
            'debit'       => $amount,
            'credit'      => 0,
        ]);

        // Credit equity account (other side of the double-entry)
        if ($equityAccount) {
            JournalItem::create([
                'journal'     => $journalEntry->id,
                'account'     => $equityAccount->id,
                'description' => "Opening Balance – {$bankAccount->bank_name}",
                'debit'       => 0,
                'credit'      => $amount,
            ]);
        }
    }

    /**
     * Delete the opening balance journal entry for a bank account (tagged OB-{id}).
     */
    private function deleteOpeningBalanceJournal(BankAccount $bankAccount): void
    {
        $journalEntry = JournalEntry::where('reference', "OB-{$bankAccount->id}")
            ->where('created_by', $bankAccount->created_by)
            ->first();

        if ($journalEntry) {
            JournalItem::where('journal', $journalEntry->id)->delete();
            $journalEntry->delete();
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'holder_name'      => 'required|string|max:255',
            'bank_name'        => 'required|string|max:255',
            'account_number'   => 'required|string|max:255',
            'chart_account_id' => 'required|integer|exists:chart_of_accounts,id',
            'opening_balance'  => 'nullable|numeric|min:0',
            'contact_number'   => 'nullable|string|max:255',
            'bank_address'     => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $creatorId      = $request->user()->creatorId();
        $openingBalance = (float) ($request->opening_balance ?? 0);

        DB::beginTransaction();
        try {
            $bankAccount = BankAccount::create([
                'holder_name'      => $request->holder_name,
                'bank_name'        => $request->bank_name,
                'account_number'   => $request->account_number,
                'chart_account_id' => $request->chart_account_id,
                'opening_balance'  => $openingBalance,
                'contact_number'   => $request->contact_number,
                'bank_address'     => $request->bank_address,
                'created_by'       => $creatorId,
            ]);

            if ($openingBalance > 0) {
                $this->createOpeningBalanceJournal($bankAccount, $openingBalance, $creatorId);
            }

            DB::commit();

            return (new BankAccountResource($bankAccount->load(['creator', 'chartAccount.journalItems'])))
                ->additional(['message' => 'Bank account created successfully'])
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create bank account',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $bankAccount = BankAccount::where('created_by', $request->user()->creatorId())
            ->with(['creator', 'chartAccount.journalItems'])
            ->findOrFail($id);

        return new BankAccountResource($bankAccount);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $creatorId   = $request->user()->creatorId();
        $bankAccount = BankAccount::where('created_by', $creatorId)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'holder_name'      => 'sometimes|required|string|max:255',
            'bank_name'        => 'sometimes|required|string|max:255',
            'account_number'   => 'sometimes|required|string|max:255',
            'chart_account_id' => 'sometimes|required|integer|exists:chart_of_accounts,id',
            'opening_balance'  => 'nullable|numeric|min:0',
            'contact_number'   => 'nullable|string|max:255',
            'bank_address'     => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $oldBalance = (float) $bankAccount->opening_balance;
            $newBalance = $request->has('opening_balance')
                ? (float) $request->opening_balance
                : $oldBalance;

            $bankAccount->update($request->only([
                'holder_name',
                'bank_name',
                'account_number',
                'chart_account_id',
                'opening_balance',
                'contact_number',
                'bank_address',
            ]));

            // If opening balance changed, reverse old JE and post a new one
            if ($newBalance !== $oldBalance) {
                $this->deleteOpeningBalanceJournal($bankAccount);

                if ($newBalance > 0) {
                    $this->createOpeningBalanceJournal($bankAccount, $newBalance, $creatorId);
                }
            }

            DB::commit();

            return (new BankAccountResource($bankAccount->load(['creator', 'chartAccount.journalItems'])))
                ->additional(['message' => 'Bank account updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update bank account',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Also cleans up the opening balance journal entry.
     */
    public function destroy(Request $request, string $id)
    {
        $bankAccount = BankAccount::where('created_by', $request->user()->creatorId())->findOrFail($id);

        DB::beginTransaction();
        try {
            $this->deleteOpeningBalanceJournal($bankAccount);
            $bankAccount->delete();
            DB::commit();

            return response()->json(['message' => 'Bank account deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete bank account',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
