<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DebitNoteResource;
use App\Models\Bill;
use App\Models\DebitNote;
use App\Models\Vender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DebitNoteController extends Controller
{
    public function index(Request $request)
    {
        $query = DebitNote::query();

        if ($request->user()) {
            // Filter by vendor's debit notes the user created
            // Since DebitNote has no created_by, scope via bill
            $userId = $request->user()->id;
            $userBillIds = Bill::where('created_by', $userId)->pluck('id');
            $query->whereIn('bill', $userBillIds);
        }

        if ($request->has('bill') && $request->bill) {
            $query->where('bill', $request->bill);
        }

        if ($request->has('vendor') && $request->vendor) {
            $query->where('vendor', $request->vendor);
        }

        if ($request->has('from_date') && $request->from_date) {
            $query->whereDate('date', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        if ($request->has('search') && $request->search) {
            $search = '%' . $request->search . '%';
            $query->where('description', 'like', $search);
        }

        $perPage = $request->input('per_page', 15);
        $debitNotes = $query->with(['billRelation', 'vendorRelation'])->latest()->paginate($perPage);

        return DebitNoteResource::collection($debitNotes);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vendor'      => 'required|integer|exists:venders,id',
            'bill'        => 'nullable|integer|exists:bills,id',
            'amount'      => 'required|numeric|min:0.01',
            'date'        => 'required|date',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // If bill is linked, validate it belongs to this user
        if ($request->bill) {
            $bill = Bill::where('id', $request->bill)
                ->where('created_by', $request->user()->id)
                ->first();

            if (!$bill) {
                return response()->json(['errors' => ['bill' => ['Bill not found or access denied']]], 422);
            }
        }

        // Validate vendor belongs to this user
        $vendor = Vender::where('id', $request->vendor)
            ->where('created_by', $request->user()->id)
            ->first();

        if (!$vendor) {
            return response()->json(['errors' => ['vendor' => ['Vendor not found or access denied']]], 422);
        }

        $debitNote = DebitNote::create([
            'bill'        => $request->bill ?? 0,
            'vendor'      => $request->vendor,
            'amount'      => $request->amount,
            'date'        => $request->date,
            'description' => $request->description ?? '',
        ]);

        return (new DebitNoteResource($debitNote->load(['billRelation', 'vendorRelation'])))
            ->additional(['message' => 'Debit note created successfully.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, string $id)
    {
        $debitNote = DebitNote::with(['billRelation', 'vendorRelation'])->findOrFail($id);

        return new DebitNoteResource($debitNote);
    }

    public function update(Request $request, string $id)
    {
        $debitNote = DebitNote::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'vendor'      => 'sometimes|required|integer|exists:venders,id',
            'bill'        => 'sometimes|nullable|integer|exists:bills,id',
            'amount'      => 'sometimes|required|numeric|min:0.01',
            'date'        => 'sometimes|required|date',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $debitNote->update($request->only(['bill', 'vendor', 'amount', 'date', 'description']));

        return (new DebitNoteResource($debitNote->load(['billRelation', 'vendorRelation'])))
            ->additional(['message' => 'Debit note updated successfully.']);
    }

    public function destroy(Request $request, string $id)
    {
        $debitNote = DebitNote::findOrFail($id);
        $debitNote->delete();

        return response()->json(['message' => 'Debit note deleted successfully.']);
    }
}
