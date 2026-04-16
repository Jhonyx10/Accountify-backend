<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{
    protected CouponService $couponService;

    public function __construct(CouponService $couponService)
    {
        $this->couponService = $couponService;
    }

    /**
     * Display a listing of coupons
     */
    public function index(Request $request)
    {
        $query = Coupon::withCount('userCoupons');

        // Search by name or code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Filter by is_active
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $perPage = $request->input('per_page', 15);
        $coupons = $query->latest()->paginate($perPage);

        return CouponResource::collection($coupons);
    }

    /**
     * Store a newly created coupon
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:coupons,code',
            'discount' => 'required|numeric|min:0',
            'limit' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'nullable|integer|in:0,1',
            'expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $coupon = Coupon::create([
            'name' => $request->name,
            'code' => $request->code,
            'discount' => $request->discount,
            'limit' => $request->limit,
            'description' => $request->description,
            'is_active' => $request->is_active ?? 1,
            'expires_at' => $request->expires_at,
        ]);

        return (new CouponResource($coupon))
            ->additional(['message' => 'Coupon created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified coupon
     */
    public function show(string $id)
    {
        $coupon = Coupon::withCount('userCoupons')->findOrFail($id);

        return new CouponResource($coupon);
    }

    /**
     * Update the specified coupon
     */
    public function update(Request $request, string $id)
    {
        $coupon = Coupon::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:255|unique:coupons,code,' . $id,
            'discount' => 'sometimes|required|numeric|min:0',
            'limit' => 'sometimes|required|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'nullable|integer|in:0,1',
            'expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $coupon->update($request->all());

        return (new CouponResource($coupon->load('userCoupons')))
            ->additional(['message' => 'Coupon updated successfully']);
    }

    /**
     * Remove the specified coupon
     */
    public function destroy(string $id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        return response()->json([
            'message' => 'Coupon deleted successfully'
        ]);
    }

    /**
     * Validate a coupon by code
     */
    public function validateCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|exists:coupons,code',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon is not valid or has exceeded its usage limit',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Coupon is valid',
            'data' => new CouponResource($coupon),
        ]);
    }
    /**
     * Suggest a coupon code
     */
    public function suggestCode(Request $request)
    {
        $request->validate(['title' => 'required|string|min:3']);
        
        // Use the logic we discussed to create the code
        $code = $this->couponService->generateUniqueCode($request->title);

        return response()->json(['code' => $code]);
    }
}
