<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Display a listing of orders
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Order::with(['user', 'plan']);

        // Multi-tenancy filtering
        if ($user->type != 'super admin') {
            $query->where('user_id', $user->id);
        }

        // Search by order_id, name, or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by plan_id
        if ($request->has('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        // Filter by payment_status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by payment_type
        if ($request->has('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        // Filter by is_refund
        if ($request->has('is_refund')) {
            $query->where('is_refund', $request->is_refund);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        $perPage = $request->input('per_page', 15);
        $orders = $query->latest()->paginate($perPage);

        return OrderResource::collection($orders);
    }

    /**
     * Store a newly created order
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:100',
            'card_number' => 'nullable|string|max:10',
            'card_exp_month' => 'nullable|string|max:10',
            'card_exp_year' => 'nullable|string|max:10',
            'plan_name' => 'required|string|max:100',
            'plan_id' => 'required|exists:plans,id',
            'price' => 'required|numeric|min:0',
            'price_currency' => 'required|string|max:10',
            'txn_id' => 'required|string|max:100',
            'payment_status' => 'required|string|max:100',
            'payment_type' => 'nullable|string|max:255',
            'receipt' => 'nullable|string|max:255',
            'user_id' => 'required|exists:users,id',
            'is_refund' => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Auto-generate order_id
        $lastOrder = Order::orderBy('id', 'desc')->first();
        $orderId = $lastOrder ? 'ORD-' . str_pad((int)substr($lastOrder->order_id, 4) + 1, 6, '0', STR_PAD_LEFT) : 'ORD-000001';

        $order = Order::create([
            'order_id' => $orderId,
            'name' => $request->name,
            'email' => $request->email,
            'card_number' => $request->card_number,
            'card_exp_month' => $request->card_exp_month,
            'card_exp_year' => $request->card_exp_year,
            'plan_name' => $request->plan_name,
            'plan_id' => $request->plan_id,
            'price' => $request->price,
            'price_currency' => $request->price_currency,
            'txn_id' => $request->txn_id,
            'payment_status' => $request->payment_status,
            'payment_type' => $request->payment_type ?? 'Manually',
            'receipt' => $request->receipt,
            'user_id' => $request->user_id,
            'is_refund' => $request->is_refund ?? 0,
        ]);

        return (new OrderResource($order->load(['user', 'plan'])))
            ->additional(['message' => 'Order created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified order
     */
    public function show(string $id)
    {
        $user = Auth::user();

        $query = Order::with(['user', 'plan']);

        if ($user->type != 'super admin') {
            $query->where('user_id', $user->id);
        }

        $order = $query->findOrFail($id);

        return new OrderResource($order);
    }

    /**
     * Update the specified order
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();

        $query = Order::query();

        if ($user->type != 'super admin') {
            $query->where('user_id', $user->id);
        }

        $order = $query->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:100',
            'payment_status' => 'sometimes|required|string|max:100',
            'payment_type' => 'nullable|string|max:255',
            'receipt' => 'nullable|string|max:255',
            'is_refund' => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order->update($request->all());

        return (new OrderResource($order->load(['user', 'plan'])))
            ->additional(['message' => 'Order updated successfully']);
    }

    /**
     * Remove the specified order
     */
    public function destroy(string $id)
    {
        $user = Auth::user();

        $query = Order::query();

        if ($user->type != 'super admin') {
            $query->where('user_id', $user->id);
        }

        $order = $query->findOrFail($id);
        $order->delete();

        return response()->json([
            'message' => 'Order deleted successfully'
        ]);
    }

    /**
     * Mark order as refunded
     */
    public function refund(string $id)
    {
        $user = Auth::user();

        $query = Order::query();

        if ($user->type != 'super admin') {
            $query->where('user_id', $user->id);
        }

        $order = $query->findOrFail($id);
        $order->update(['is_refund' => 1]);

        return (new OrderResource($order->load(['user', 'plan'])))
            ->additional(['message' => 'Order marked as refunded successfully']);
    }
}

