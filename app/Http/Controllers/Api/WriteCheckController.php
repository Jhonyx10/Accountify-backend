<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WriteCheckResource;
use App\Models\WriteCheck;
use App\Models\WriteCheckItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class WriteCheckController extends Controller
{
    public function index(Request $request)
    {
        $query = WriteCheck::query();

        if ($request->user()) {
            $query->where('created_by', $request->user()->id);
        }

        $perPage = $request->input('per_page', 15);
        $checks = $query->with(['bankAccount', 'items'])->latest()->paginate($perPage);

        foreach ($checks as $check) {
            $check->payee_name = $check->payee ? $check->payee->name ?? $check->payee->first_name ?? '' : null;
        }

        return WriteCheckResource::collection($checks);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bank_account_id' => 'required|integer',
            'date' => 'required|date',
            'amount' => 'required|numeric',
            'items' => 'required|array',
            'items.*.chart_of_account_id' => 'required|integer',
            'items.*.amount' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $check = WriteCheck::create([
                'bank_account_id' => $request->bank_account_id,
                'payee_id' => $request->payee_id ?? 0,
                'payee_type' => $request->payee_type ?? 0,
                'date' => $request->date,
                'reference' => $request->reference ?? '',
                'amount' => $request->amount,
                'description' => $request->description ?? '',
                'created_by' => $request->user()->id,
            ]);

            foreach ($request->items as $item) {
                WriteCheckItem::create([
                    'write_check_id' => $check->id,
                    'chart_of_account_id' => $item['chart_of_account_id'],
                    'product_id' => $item['product_id'] ?? 0,
                    'description' => $item['description'] ?? '',
                    'amount' => $item['amount'],
                ]);
            }

            DB::commit();

            return (new WriteCheckResource($check))
                ->additional(['message' => 'Write Check created successfully'])
                ->response()
                ->setStatusCode(201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating Write Check', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        $check = WriteCheck::findOrFail($id);
        $items = WriteCheckItem::where('write_check_id', $check->id)->get();
        $check->setAttribute('items', $items);
        return new WriteCheckResource($check);
    }

    public function update(Request $request, string $id)
    {
        $check = WriteCheck::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'bank_account_id' => 'sometimes|required|integer',
            'date' => 'sometimes|required|date',
            'amount' => 'sometimes|required|numeric',
            'items' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $check->update($request->except(['created_by', 'items']));

            if ($request->has('items')) {
                WriteCheckItem::where('write_check_id', $check->id)->delete();

                foreach ($request->items as $item) {
                    WriteCheckItem::create([
                        'write_check_id' => $check->id,
                        'chart_of_account_id' => $item['chart_of_account_id'],
                        'product_id' => $item['product_id'] ?? 0,
                        'description' => $item['description'] ?? '',
                        'amount' => $item['amount'],
                    ]);
                }
            }

            DB::commit();

            return (new WriteCheckResource($check))
                ->additional(['message' => 'Write Check updated successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating Write Check', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        $check = WriteCheck::findOrFail($id);
        WriteCheckItem::where('write_check_id', $check->id)->delete();
        $check->delete();

        return response()->json(['message' => 'Write Check deleted successfully']);
    }
}
