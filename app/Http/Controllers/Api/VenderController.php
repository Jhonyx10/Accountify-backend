<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VenderResource;
use App\Models\Vender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class VenderController extends Controller
{
    public function index(Request $request)
    {
        $query = Vender::with('creator');

        if ($request->user()) {
            $query->where('created_by', $request->user()->id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('contact', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $perPage = $request->input('per_page', 15);
        $venders = $query->latest()->paginate($perPage);

        return VenderResource::collection($venders);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:venders,email',
            'password' => 'nullable|string|min:6',
            'contact' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lastVender = Vender::where('created_by', $request->user()->id)->latest('vender_id')->first();
        $venderId = $lastVender ? $lastVender->vender_id + 1 : 1;

        $vender = Vender::create([
            'vender_id' => $venderId,
            'name' => $request->name,
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password ?? \Illuminate\Support\Str::random(12)),
            'contact' => $request->contact,
            'tax_number' => $request->tax_number,
            'avatar' => '',
            'created_by' => $request->user()->id,
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

        return (new VenderResource($vender))
            ->additional(['message' => 'Vendor created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $id)
    {
        $vender = Vender::with(['creator', 'bills'])->findOrFail($id);

        return new VenderResource($vender);
    }

    public function update(Request $request, string $id)
    {
        $vender = Vender::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:venders,email,' . $id,
            'password' => 'nullable|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->except(['password', 'vender_id', 'created_by']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $vender->update($data);

        return (new VenderResource($vender))
            ->additional(['message' => 'Vendor updated successfully']);
    }

    public function destroy(string $id)
    {
        $vender = Vender::findOrFail($id);
        $vender->delete();

        return response()->json(['message' => 'Vendor deleted successfully']);
    }
}
