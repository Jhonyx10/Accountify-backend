<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BudgetController extends Controller
{
    /**
     * Display a listing of budgets
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = Budget::with('creator');

        // Multi-tenancy filtering
        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter by period
        if ($request->has('period')) {
            $query->where('period', $request->period);
        }

        // Filter by year
        if ($request->has('year')) {
            $query->where('from', $request->year);
        }

        $perPage = $request->input('per_page', 15);
        $budgets = $query->latest()->paginate($perPage);

        // Collect all category IDs from all budgets in the current page
        $categoryIds = [];
        foreach ($budgets as $budget) {
            $categoryIds = array_merge($categoryIds, $budget->getAllCategoryIds());
        }
        $categoryIds = array_unique($categoryIds);

        // Fetch the categories
        $categories = Category::whereIn('id', $categoryIds)->get()->keyBy('id');

        // Map categories back to budgets if needed, or pass them to the resource
        return BudgetResource::collection($budgets)->additional([
            'categories' => $categories
        ]);
    }

    /**
     * Store a newly created budget
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'period' => 'required|in:monthly,quarterly,half-yearly,yearly',
            'from' => 'nullable|string',
            'to' => 'nullable|string',
            'income_data' => 'nullable|array',
            'expense_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        Log::info('Budget store requested with data:', $request->all());

        $budget = Budget::create([
            'name' => $request->name,
            'period' => $request->period,
            'from' => $request->from,
            'to' => $request->to,
            'income_data' => $request->income_data,
            'expense_data' => $request->expense_data,
            'created_by' => $request->user()->creatorId(),
        ]);

        return (new BudgetResource($budget->load('creator')))
            ->additional(['message' => 'Budget created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified budget
     */
    public function show(Request $request, string $id)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = Budget::with('creator');

        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        $budget = $query->findOrFail($id);

        // Load categories for this specific budget
        $categoryIds = $budget->getAllCategoryIds();
        $categories = Category::whereIn('id', $categoryIds)->get()->keyBy('id');

        return (new BudgetResource($budget))->additional([
            'categories' => $categories
        ]);
    }

    /**
     * Update the specified budget
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = Budget::query();

        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        $budget = $query->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'period' => 'sometimes|required|in:monthly,quarterly,half-yearly,yearly',
            'from' => 'nullable|string',
            'to' => 'nullable|string',
            'income_data' => 'nullable|array',
            'expense_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $budget->update([
            'name' => $request->name,
            'period' => $request->period,
            'from' => $request->from,
            'to' => $request->to,
            'income_data' => $request->income_data,
            'expense_data' => $request->expense_data,
        ]);

        return (new BudgetResource($budget->load('creator')))
            ->additional(['message' => 'Budget updated successfully']);
    }

    /**
     * Remove the specified budget
     */
    public function destroy(Request $request, string $id)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = Budget::query();

        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        $budget = $query->findOrFail($id);
        $budget->delete();

        return response()->json([
            'message' => 'Budget deleted successfully'
        ]);
    }
}

