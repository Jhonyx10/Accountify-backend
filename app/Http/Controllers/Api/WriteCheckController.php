<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WriteCheckResource;
use App\Models\BankAccount;
use App\Models\WriteCheck;
use App\Models\WriteCheckItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WriteCheckController extends Controller
{
    public function index(Request $request)
    {
        $query = WriteCheck::query();

        if ($request->user()) {
            $query->where('created_by', $request->user()->id);
        }

        if ($request->has('search') && $request->search) {
            $search = '%' . $request->search . '%';
            $query->where('reference', 'like', $search)
                  ->orWhere('description', 'like', $search);
        }

        $perPage = $request->input('per_page', 15);
        $checks = $query->with(['bankAccount', 'items.chartOfAccount'])->latest()->paginate($perPage);

        return WriteCheckResource::collection($checks);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bank_account_id' => 'required|integer|exists:bank_accounts,id',
            'date'            => 'required|date',
            'amount'          => 'required|numeric|min:0.01',
            'payee_id'        => 'nullable|integer',
            'payee_type'      => 'nullable|integer',
            'items'           => 'required|array|min:1',
            'items.*.chart_of_account_id' => 'required|integer',
            'items.*.amount'  => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Validate splits balance: sum of items must equal the check total
        $splitsTotal = collect($request->items)->sum('amount');
        if (abs($splitsTotal - $request->amount) > 0.01) {
            return response()->json([
                'message' => 'Account distribution total (' . number_format($splitsTotal, 2) . ') does not match check amount (' . number_format($request->amount, 2) . ').',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // --- Balance Drawing: deduct from BankAccount opening_balance ---
            $bankAccount = BankAccount::lockForUpdate()->findOrFail($request->bank_account_id);
            $bankAccount->opening_balance -= $request->amount;
            $bankAccount->save();
            // ----------------------------------------------------------------

            $check = WriteCheck::create([
                'bank_account_id' => $request->bank_account_id,
                'payee_id'        => $request->payee_id ?? 0,
                'payee_type'      => $request->payee_type ?? 1,
                'date'            => $request->date,
                'reference'       => $request->reference ?? '',
                'amount'          => $request->amount,
                'description'     => $request->description ?? '',
                'created_by'      => $request->user()->id,
            ]);

            foreach ($request->items as $item) {
                WriteCheckItem::create([
                    'write_check_id'      => $check->id,
                    'chart_of_account_id' => $item['chart_of_account_id'],
                    'product_id'          => $item['product_id'] ?? 0,
                    'description'         => $item['description'] ?? '',
                    'amount'              => $item['amount'],
                ]);
            }

            DB::commit();

            return (new WriteCheckResource($check->load(['bankAccount', 'items.chartOfAccount'])))
                ->additional(['message' => 'Check recorded and bank balance updated successfully.'])
                ->response()
                ->setStatusCode(201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error recording check.', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        $check = WriteCheck::with(['bankAccount', 'items.chartOfAccount'])->findOrFail($id);
        return new WriteCheckResource($check);
    }

    public function update(Request $request, string $id)
    {
        $check = WriteCheck::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'bank_account_id' => 'sometimes|required|integer|exists:bank_accounts,id',
            'date'            => 'sometimes|required|date',
            'amount'          => 'sometimes|required|numeric|min:0.01',
            'items'           => 'sometimes|array|min:1',
            'items.*.chart_of_account_id' => 'required_with:items|integer',
            'items.*.amount'  => 'required_with:items|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Validate splits balance if both amount and items are provided
        if ($request->has('items') && $request->has('amount')) {
            $splitsTotal = collect($request->items)->sum('amount');
            if (abs($splitsTotal - $request->amount) > 0.01) {
                return response()->json([
                    'message' => 'Account distribution total (' . number_format($splitsTotal, 2) . ') does not match check amount (' . number_format($request->amount, 2) . ').',
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            // --- Balance Adjustment: reverse old amount, apply new amount ---
            $oldAmount  = (float) $check->amount;
            $newAmount  = $request->has('amount') ? (float) $request->amount : $oldAmount;
            $difference = $newAmount - $oldAmount;

            if ($difference != 0) {
                $bankAccountId = $request->bank_account_id ?? $check->bank_account_id;
                $bankAccount   = BankAccount::lockForUpdate()->findOrFail($bankAccountId);
                $bankAccount->opening_balance -= $difference;
                $bankAccount->save();
            }
            // ----------------------------------------------------------------

            $check->update($request->except(['created_by', 'items']));

            if ($request->has('items')) {
                WriteCheckItem::where('write_check_id', $check->id)->delete();

                foreach ($request->items as $item) {
                    WriteCheckItem::create([
                        'write_check_id'      => $check->id,
                        'chart_of_account_id' => $item['chart_of_account_id'],
                        'product_id'          => $item['product_id'] ?? 0,
                        'description'         => $item['description'] ?? '',
                        'amount'              => $item['amount'],
                    ]);
                }
            }

            DB::commit();

            return (new WriteCheckResource($check->load(['bankAccount', 'items.chartOfAccount'])))
                ->additional(['message' => 'Check updated and bank balance adjusted successfully.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating check.', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        $check = WriteCheck::findOrFail($id);

        try {
            DB::beginTransaction();

            // --- Reverse balance drawing on delete ---
            $bankAccount = BankAccount::lockForUpdate()->findOrFail($check->bank_account_id);
            $bankAccount->opening_balance += $check->amount;
            $bankAccount->save();
            // -----------------------------------------

            WriteCheckItem::where('write_check_id', $check->id)->delete();
            $check->delete();

            DB::commit();

            return response()->json(['message' => 'Check deleted and bank balance restored.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error deleting check.', 'error' => $e->getMessage()], 500);
        }
    }
}
