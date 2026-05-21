<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RevenueResource;
use App\Models\Revenue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RevenueController extends Controller
{
    public function index(Request $request)
    {
        $query = Revenue::with(['customer', 'account', 'category', 'creator']);

        if ($request->user()) {
            $query->where('created_by', $request->user()->id);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $revenues = $query->latest('date')->paginate($perPage);

        return RevenueResource::collection($revenues);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'customer_id' => 'nullable|exists:customers,id',
            'account_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $revenue = Revenue::create([
            'date' => $request->date,
            'amount' => $request->amount,
            'account_id' => $request->account_id,
            'customer_id' => $request->customer_id,
            'category_id' => $request->category_id,
            'payment_method' => $request->payment_method,
            'reference' => $request->reference,
            'add_receipt' => $request->add_receipt,
            'description' => $request->description,
            'created_by' => $request->user()->id,
        ]);

        return (new RevenueResource($revenue->load(['customer', 'account', 'category'])))
            ->additional(['message' => 'Revenue created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $id)
    {
        $revenue = Revenue::with(['customer', 'account', 'category', 'creator'])->findOrFail($id);

        return new RevenueResource($revenue);
    }

    public function update(Request $request, string $id)
    {
        $revenue = Revenue::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'date' => 'sometimes|required|date',
            'amount' => 'sometimes|required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $revenue->update($request->except(['created_by']));

        return (new RevenueResource($revenue->load(['customer', 'account', 'category'])))
            ->additional(['message' => 'Revenue updated successfully']);
    }

    public function destroy(string $id)
    {
        $revenue = Revenue::findOrFail($id);
        $revenue->delete();

        return response()->json(['message' => 'Revenue deleted successfully']);
    }
}
