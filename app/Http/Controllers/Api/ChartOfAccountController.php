<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChartOfAccountResource;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChartOfAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = ChartOfAccount::with(['accountType', 'accountSubType', 'parentAccount', 'creator', 'journalItems']);

        if ($request->user()) {
            $query->where('created_by', $request->user()->id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('sub_type')) {
            $query->where('sub_type', $request->sub_type);
        }

        if ($request->has('is_enabled')) {
            $query->where('is_enabled', $request->is_enabled);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $accounts = $query->latest()->paginate($perPage);

        return ChartOfAccountResource::collection($accounts);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'type' => 'required|integer',
            'sub_type' => 'nullable|integer',
            'is_enabled' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $account = ChartOfAccount::create([
            'name' => $request->name,
            'code' => $request->code,
            'type' => $request->type,
            'sub_type' => $request->sub_type ?? 0,
            'parent' => $request->parent ?? 0,
            'is_enabled' => $request->is_enabled ?? 1,
            'description' => $request->description,
            'created_by' => $request->user()->id,
        ]);

        return (new ChartOfAccountResource($account->load(['accountType', 'accountSubType'])))
            ->additional(['message' => 'Chart of Account created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $id)
    {
        $account = ChartOfAccount::with(['accountType', 'accountSubType', 'parentAccount', 'creator'])->findOrFail($id);

        return new ChartOfAccountResource($account);
    }

    public function update(Request $request, string $id)
    {
        $account = ChartOfAccount::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $account->update($request->except(['created_by']));

        return (new ChartOfAccountResource($account->load(['accountType', 'accountSubType'])))
            ->additional(['message' => 'Chart of Account updated successfully']);
    }

    public function destroy(string $id)
    {
        $account = ChartOfAccount::findOrFail($id);
        $account->delete();

        return response()->json(['message' => 'Chart of Account deleted successfully']);
    }

    public function ledger(Request $request, string $id)
    {
        $account = ChartOfAccount::with(['accountType', 'accountSubType', 'parentAccount', 'creator'])->findOrFail($id);

        $query = \App\Models\JournalItem::with(['journalEntry'])
            ->where('account', $id)
            ->whereHas('journalEntry', function($q) use ($request) {
                if ($request->has('date_from')) {
                    $q->whereDate('date', '>=', $request->date_from);
                }
                if ($request->has('date_to')) {
                    $q->whereDate('date', '<=', $request->date_to);
                }
            });

        $items = $query->get()->sortBy(function($item) {
            return $item->journalEntry->date->timestamp . '-' . str_pad($item->journalEntry->id, 10, '0', STR_PAD_LEFT);
        });

        $runningBalance = 0;
        $ledger = [];

        foreach ($items as $item) {
            $debit = (float) $item->debit;
            $credit = (float) $item->credit;

            $typeName = $account->accountType?->name ?? '';

            if (in_array($typeName, ['Assets', 'Expenses', 'Costs of Goods Sold'])) {
                $runningBalance += ($debit - $credit);
            } elseif (in_array($typeName, ['Liabilities', 'Equity', 'Income'])) {
                $runningBalance += ($credit - $debit);
            } else {
                $runningBalance += ($debit - $credit);
            }

            $ledger[] = [
                'id' => $item->id,
                'date' => $item->journalEntry->date->format('Y-m-d'),
                'reference' => $item->journalEntry->reference,
                'description' => $item->description ?? $item->journalEntry->description,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $runningBalance,
                'journal_id' => $item->journalEntry->id
            ];
        }

        return response()->json([
            'data' => array_reverse($ledger),
            'account' => new \App\Http\Resources\ChartOfAccountResource($account),
            'current_balance' => $runningBalance
        ]);
    }
}
