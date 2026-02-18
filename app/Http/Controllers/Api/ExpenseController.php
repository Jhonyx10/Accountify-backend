<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with(['creator', 'user']);

        if ($request->user()) {
            $query->where('created_by', $request->user()->id);
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
            $query->where('description', 'like', "%{$search}%");
        }

        $perPage = $request->input('per_page', 15);
        $expenses = $query->latest('date')->paginate($perPage);

        return ExpenseResource::collection($expenses);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|integer',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $expense = Expense::create([
            'category_id' => $request->category_id,
            'description' => $request->description,
            'amount' => $request->amount,
            'date' => $request->date,
            'project' => $request->project ?? 0,
            'user_id' => $request->user_id ?? 0,
            'attachment' => $request->attachment,
            'created_by' => $request->user()->id,
        ]);

        return (new ExpenseResource($expense))
            ->additional(['message' => 'Expense created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $id)
    {
        $expense = Expense::with(['creator', 'user'])->findOrFail($id);

        return new ExpenseResource($expense);
    }

    public function update(Request $request, string $id)
    {
        $expense = Expense::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'category_id' => 'sometimes|required|integer',
            'amount' => 'sometimes|required|numeric|min:0',
            'date' => 'sometimes|required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $expense->update($request->except(['created_by']));

        return (new ExpenseResource($expense))
            ->additional(['message' => 'Expense updated successfully']);
    }

    public function destroy(string $id)
    {
        $expense = Expense::findOrFail($id);
        $expense->delete();

        return response()->json(['message' => 'Expense deleted successfully']);
    }
}
