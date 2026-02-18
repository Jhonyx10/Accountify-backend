<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ContractController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Contract::query();

        // Multi-tenancy filtering
        if ($user->type == 'super admin') {
            // Super admin sees all contracts
        } elseif ($user->type == 'customer') {
            // Customer sees only their own contracts
            $query->where('customer', $user->id);
        } else {
            // Company users see contracts created by their company
            $query->where('created_by', $user->creatorId());
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('subject', 'LIKE', "%{$search}%");
        }

        // Filter by customer
        if ($request->has('customer')) {
            $query->where('customer', $request->customer);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('edit_status')) {
            $query->where('edit_status', $request->edit_status);
        }

        // Filter by start date range
        if ($request->has('start_date_from')) {
            $query->whereDate('start_date', '>=', $request->start_date_from);
        }
        if ($request->has('start_date_to')) {
            $query->whereDate('start_date', '<=', $request->start_date_to);
        }

        // Filter by end date range
        if ($request->has('end_date_from')) {
            $query->whereDate('end_date', '>=', $request->end_date_from);
        }
        if ($request->has('end_date_to')) {
            $query->whereDate('end_date', '<=', $request->end_date_to);
        }

        // Load relationships
        $query->with(['creator', 'customerRelation']);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $contracts = $query->latest()->paginate($perPage);

        return ContractResource::collection($contracts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer' => 'required|exists:customers,id',
            'subject' => 'required|string|max:255',
            'value' => 'required|numeric|min:0',
            'type' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'edit_status' => 'nullable|string|in:pending,accepted,declined',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'customer_signature' => 'nullable|string',
            'company_signature' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify customer belongs to the same company
        $customer = Customer::find($request->customer);
        if ($customer->created_by != Auth::user()->creatorId()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid customer'
            ], 403);
        }

        $contract = Contract::create([
            'customer' => $request->customer,
            'subject' => $request->subject,
            'value' => $request->value,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'edit_status' => $request->edit_status ?? 'pending',
            'description' => $request->description,
            'notes' => $request->notes,
            'customer_signature' => $request->customer_signature,
            'company_signature' => $request->company_signature,
            'created_by' => Auth::user()->creatorId(),
        ]);

        $contract->load(['creator', 'customerRelation']);

        return response()->json([
            'success' => true,
            'message' => 'Contract created successfully',
            'data' => new ContractResource($contract)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $contract = Contract::with(['creator', 'customerRelation'])->find($id);

        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found'
            ], 404);
        }

        // Check access
        if ($user->type == 'customer') {
            if ($contract->customer != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }
        } elseif ($user->type != 'super admin' && $contract->created_by != $user->creatorId()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new ContractResource($contract)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $contract = Contract::find($id);

        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found'
            ], 404);
        }

        // Check access
        if ($user->type == 'customer') {
            return response()->json([
                'success' => false,
                'message' => 'Customers cannot update contracts'
            ], 403);
        } elseif ($user->type != 'super admin' && $contract->created_by != $user->creatorId()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'customer' => 'sometimes|required|exists:customers,id',
            'subject' => 'sometimes|required|string|max:255',
            'value' => 'sometimes|required|numeric|min:0',
            'type' => 'sometimes|required|integer',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'edit_status' => 'nullable|string|in:pending,accepted,declined',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'customer_signature' => 'nullable|string',
            'company_signature' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify customer belongs to the same company if updating customer
        if ($request->has('customer')) {
            $customer = Customer::find($request->customer);
            if ($customer->created_by != $user->creatorId()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid customer'
                ], 403);
            }
        }

        $contract->update($request->only([
            'customer',
            'subject',
            'value',
            'type',
            'start_date',
            'end_date',
            'edit_status',
            'description',
            'notes',
            'customer_signature',
            'company_signature',
        ]));

        $contract->load(['creator', 'customerRelation']);

        return response()->json([
            'success' => true,
            'message' => 'Contract updated successfully',
            'data' => new ContractResource($contract)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $contract = Contract::find($id);

        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found'
            ], 404);
        }

        // Check access
        if ($user->type == 'customer') {
            return response()->json([
                'success' => false,
                'message' => 'Customers cannot delete contracts'
            ], 403);
        } elseif ($user->type != 'super admin' && $contract->created_by != $user->creatorId()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $contract->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contract deleted successfully'
        ]);
    }
}
