<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Customer::with('creator');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('contact', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $customers = $query->latest()->paginate($perPage);

        return CustomerResource::collection($customers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'password' => 'required|string|min:6',
            'contact' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:255',
            'billing_name' => 'nullable|string|max:255',
            'billing_country' => 'nullable|string|max:255',
            'billing_state' => 'nullable|string|max:255',
            'billing_city' => 'nullable|string|max:255',
            'billing_phone' => 'nullable|string|max:255',
            'billing_zip' => 'nullable|string|max:255',
            'billing_address' => 'nullable|string',
            'shipping_name' => 'nullable|string|max:255',
            'shipping_country' => 'nullable|string|max:255',
            'shipping_state' => 'nullable|string|max:255',
            'shipping_city' => 'nullable|string|max:255',
            'shipping_phone' => 'nullable|string|max:255',
            'shipping_zip' => 'nullable|string|max:255',
            'shipping_address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Generate customer_id
        $companyId = $request->user()->creatorId();
        $lastCustomer = Customer::where('company_id', $companyId)->latest('customer_id')->first();
        $customerId = $lastCustomer ? $lastCustomer->customer_id + 1 : 1;

        $customer = Customer::create([
            'company_id' => $companyId,
            'customer_id' => $customerId,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'contact' => $request->contact,
            'tax_number' => $request->tax_number,
            'billing_name' => $request->billing_name,
            'billing_country' => $request->billing_country,
            'billing_state' => $request->billing_state,
            'billing_city' => $request->billing_city,
            'billing_phone' => $request->billing_phone,
            'billing_zip' => $request->billing_zip,
            'billing_address' => $request->billing_address,
            'shipping_name' => $request->shipping_name,
            'shipping_country' => $request->shipping_country,
            'shipping_state' => $request->shipping_state,
            'shipping_city' => $request->shipping_city,
            'shipping_phone' => $request->shipping_phone,
            'shipping_zip' => $request->shipping_zip,
            'shipping_address' => $request->shipping_address,
        ]);

        return (new CustomerResource($customer))
            ->additional(['message' => 'Customer created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $customer = Customer::where('company_id', $request->user()->creatorId())
            ->with(['creator', 'invoices', 'proposals', 'retainers'])
            ->findOrFail($id);

        return new CustomerResource($customer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $customer = Customer::where('company_id', $request->user()->creatorId())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:customers,email,' . $id,
            'password' => 'nullable|string|min:6',
            'contact' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->except(['password', 'customer_id', 'created_by', 'company_id']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $customer->update($data);

        return (new CustomerResource($customer))
            ->additional(['message' => 'Customer updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $customer = Customer::where('company_id', $request->user()->creatorId())->findOrFail($id);
        $customer->delete();

        return response()->json([
            'message' => 'Customer deleted successfully'
        ]);
    }
}
