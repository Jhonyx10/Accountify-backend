<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\JournalEntryResource;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class JournalEntryController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = JournalEntry::query();

        if ($user->type != 'super admin') {
            $query->where('created_by', $user->creatorId());
        }

        if ($request->has('search')) {
            $query->where('reference', 'LIKE', "%{$request->search}%");
        }

        if ($request->has('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $query->with(['creator', 'items.chartOfAccount']);
        $perPage = $request->input('per_page', 15);
        if ($perPage == -1) {
            $journalEntries = $query->latest()->get();
        } else {
            $journalEntries = $query->latest()->paginate($perPage);
        }

        return JournalEntryResource::collection($journalEntries);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'items' => 'required|array|min:2',
            'items.*.account' => 'required|exists:chart_of_accounts,id',
            'items.*.description' => 'nullable|string',
            'items.*.debit' => 'required|numeric|min:0',
            'items.*.credit' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Validate that total debits equal total credits
        $totalDebit = collect($request->items)->sum('debit');
        $totalCredit = collect($request->items)->sum('credit');

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Total debits must equal total credits',
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit
            ], 422);
        }

        // Validate that each item has either debit or credit (not both)
        foreach ($request->items as $item) {
            if ($item['debit'] > 0 && $item['credit'] > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Each item must have either debit or credit, not both'
                ], 422);
            }
            if ($item['debit'] == 0 && $item['credit'] == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Each item must have either debit or credit'
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            // Generate journal_id
            $journalId = JournalEntry::where('created_by', Auth::user()->creatorId())->max('journal_id') + 1;

            $journalEntry = JournalEntry::create([
                'date' => $request->date,
                'reference' => $request->reference,
                'description' => $request->description,
                'journal_id' => $journalId,
                'created_by' => Auth::user()->creatorId(),
            ]);

            // Create journal items
            foreach ($request->items as $item) {
                JournalItem::create([
                    'journal' => $journalEntry->id,
                    'account' => $item['account'],
                    'description' => $item['description'] ?? null,
                    'debit' => $item['debit'],
                    'credit' => $item['credit'],
                ]);
            }

            DB::commit();

            $journalEntry->load(['creator', 'items.chartOfAccount']);

            return response()->json([
                'success' => true,
                'message' => 'Journal entry created successfully',
                'data' => new JournalEntryResource($journalEntry)
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create journal entry',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        $user = Auth::user();
        $journalEntry = JournalEntry::with(['creator', 'items.chartOfAccount'])->find($id);

        if (!$journalEntry) {
            return response()->json(['success' => false, 'message' => 'Journal entry not found'], 404);
        }

        if ($user->type != 'super admin' && $journalEntry->created_by != $user->creatorId()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        return response()->json(['success' => true, 'data' => new JournalEntryResource($journalEntry)]);
    }

    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $journalEntry = JournalEntry::find($id);

        if (!$journalEntry) {
            return response()->json(['success' => false, 'message' => 'Journal entry not found'], 404);
        }

        if ($user->type != 'super admin' && $journalEntry->created_by != $user->creatorId()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $validator = Validator::make($request->all(), [
            'date' => 'sometimes|required|date',
            'reference' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'items' => 'sometimes|required|array|min:2',
            'items.*.account' => 'required|exists:chart_of_accounts,id',
            'items.*.description' => 'nullable|string',
            'items.*.debit' => 'required|numeric|min:0',
            'items.*.credit' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        if ($request->has('items')) {
            $totalDebit = collect($request->items)->sum('debit');
            $totalCredit = collect($request->items)->sum('credit');

            if (abs($totalDebit - $totalCredit) > 0.01) {
                return response()->json(['success' => false, 'message' => 'Total debits must equal total credits'], 422);
            }

            foreach ($request->items as $item) {
                if ($item['debit'] > 0 && $item['credit'] > 0) {
                    return response()->json(['success' => false, 'message' => 'Each item must have either debit or credit, not both'], 422);
                }
                if ($item['debit'] == 0 && $item['credit'] == 0) {
                    return response()->json(['success' => false, 'message' => 'Each item must have either debit or credit'], 422);
                }
            }
        }

        DB::beginTransaction();
        try {
            $journalEntry->update($request->only(['date', 'reference', 'description']));

            if ($request->has('items')) {
                JournalItem::where('journal', $journalEntry->id)->delete();

                foreach ($request->items as $item) {
                    JournalItem::create([
                        'journal' => $journalEntry->id,
                        'account' => $item['account'],
                        'description' => $item['description'] ?? null,
                        'debit' => $item['debit'],
                        'credit' => $item['credit'],
                    ]);
                }
            }

            DB::commit();
            $journalEntry->load(['creator', 'items.chartOfAccount']);

            return response()->json([
                'success' => true,
                'message' => 'Journal entry updated successfully',
                'data' => new JournalEntryResource($journalEntry)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to update journal entry', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        $user = Auth::user();
        $journalEntry = JournalEntry::find($id);

        if (!$journalEntry) {
            return response()->json(['success' => false, 'message' => 'Journal entry not found'], 404);
        }

        if ($user->type != 'super admin' && $journalEntry->created_by != $user->creatorId()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        DB::beginTransaction();
        try {
            JournalItem::where('journal', $journalEntry->id)->delete();
            $journalEntry->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Journal entry deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to delete journal entry', 'error' => $e->getMessage()], 500);
        }
    }
}
