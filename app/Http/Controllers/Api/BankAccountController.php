<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BankAccountResource;
use App\Models\BankAccount;
use Illuminate\Http\Request;
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
        $bankAccounts = $query->with(['creator', 'chartAccount'])->paginate($perPage);

        return BankAccountResource::collection($bankAccounts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'holder_name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'chart_account_id' => 'required|integer|exists:chart_of_accounts,id',
            'opening_balance' => 'nullable|numeric|min:0',
            'contact_number' => 'nullable|string|max:255',
            'bank_address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $bankAccount = BankAccount::create([
            'holder_name' => $request->holder_name,
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'chart_account_id' => $request->chart_account_id,
            'opening_balance' => $request->opening_balance ?? 0,
            'contact_number' => $request->contact_number,
            'bank_address' => $request->bank_address,
            'created_by' => $request->user()->creatorId(),
        ]);

        return (new BankAccountResource($bankAccount->load(['creator', 'chartAccount'])))
            ->additional(['message' => 'Bank account created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $bankAccount = BankAccount::where('created_by', $request->user()->creatorId())
            ->with(['creator', 'chartAccount'])
            ->findOrFail($id);

        return new BankAccountResource($bankAccount);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $bankAccount = BankAccount::where('created_by', $request->user()->creatorId())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'holder_name' => 'sometimes|required|string|max:255',
            'bank_name' => 'sometimes|required|string|max:255',
            'account_number' => 'sometimes|required|string|max:255',
            'chart_account_id' => 'sometimes|required|integer|exists:chart_of_accounts,id',
            'opening_balance' => 'nullable|numeric|min:0',
            'contact_number' => 'nullable|string|max:255',
            'bank_address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $bankAccount->update($request->only([
            'holder_name',
            'bank_name',
            'account_number',
            'chart_account_id',
            'opening_balance',
            'contact_number',
            'bank_address',
        ]));

        return (new BankAccountResource($bankAccount->load(['creator', 'chartAccount'])))
            ->additional(['message' => 'Bank account updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $bankAccount = BankAccount::where('created_by', $request->user()->creatorId())->findOrFail($id);
        $bankAccount->delete();

        return response()->json([
            'message' => 'Bank account deleted successfully'
        ]);
    }
}
