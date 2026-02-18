<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        $query = Plan::withCount('users');

        if ($request->has('is_disable')) {
            $query->where('is_disable', $request->is_disable);
        }

        if ($request->has('trial')) {
            $query->where('trial', $request->trial);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $perPage = $request->input('per_page', 15);
        $plans = $query->latest()->paginate($perPage);

        return PlanResource::collection($plans);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|string',
            'max_users' => 'nullable|integer|min:0',
            'max_customers' => 'nullable|integer|min:0',
            'max_venders' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $plan = Plan::create([
            'name' => $request->name,
            'price' => $request->price,
            'duration' => $request->duration,
            'max_users' => $request->max_users ?? 0,
            'max_customers' => $request->max_customers ?? 0,
            'max_venders' => $request->max_venders ?? 0,
            'storage_limit' => $request->storage_limit ?? 0,
            'description' => $request->description,
            'image' => $request->image,
            'enable_chatgpt' => $request->enable_chatgpt ?? 'off',
            'trial' => $request->trial ?? 'off',
            'trial_days' => $request->trial_days ?? 0,
            'is_disable' => $request->is_disable ?? 0,
        ]);

        return (new PlanResource($plan))
            ->additional(['message' => 'Plan created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $id)
    {
        $plan = Plan::withCount('users')->findOrFail($id);

        return new PlanResource($plan);
    }

    public function update(Request $request, string $id)
    {
        $plan = Plan::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'duration' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $plan->update($request->all());

        return (new PlanResource($plan))
            ->additional(['message' => 'Plan updated successfully']);
    }

    public function destroy(string $id)
    {
        $plan = Plan::findOrFail($id);
        $plan->delete();

        return response()->json(['message' => 'Plan deleted successfully']);
    }
}
