<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanRequestResource;
use App\Models\PlanRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PlanRequestController extends Controller
{
    /**
     * Display a listing of plan requests
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = PlanRequest::with(['user', 'plan']);

        // Multi-tenancy filtering
        if ($user->type != 'super admin') {
            $query->where('user_id', $user->id);
        }

        // Filter by plan_id
        if ($request->has('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        // Filter by duration
        if ($request->has('duration')) {
            $query->where('duration', $request->duration);
        }

        $perPage = $request->input('per_page', 15);
        $planRequests = $query->latest()->paginate($perPage);

        return PlanRequestResource::collection($planRequests);
    }

    /**
     * Store a newly created plan request
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'required|exists:plans,id',
            'duration' => 'required|string|in:monthly,yearly',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $planRequest = PlanRequest::create([
            'user_id' => $request->user_id,
            'plan_id' => $request->plan_id,
            'duration' => $request->duration,
        ]);

        return (new PlanRequestResource($planRequest->load(['user', 'plan'])))
            ->additional(['message' => 'Plan request created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified plan request
     */
    public function show(string $id)
    {
        $user = Auth::user();

        $query = PlanRequest::with(['user', 'plan']);

        if ($user->type != 'super admin') {
            $query->where('user_id', $user->id);
        }

        $planRequest = $query->findOrFail($id);

        return new PlanRequestResource($planRequest);
    }

    /**
     * Update the specified plan request
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();

        $query = PlanRequest::query();

        if ($user->type != 'super admin') {
            $query->where('user_id', $user->id);
        }

        $planRequest = $query->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|required|exists:users,id',
            'plan_id' => 'sometimes|required|exists:plans,id',
            'duration' => 'sometimes|required|string|in:monthly,yearly',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $planRequest->update($request->all());

        return (new PlanRequestResource($planRequest->load(['user', 'plan'])))
            ->additional(['message' => 'Plan request updated successfully']);
    }

    /**
     * Remove the specified plan request
     */
    public function destroy(string $id)
    {
        $user = Auth::user();

        $query = PlanRequest::query();

        if ($user->type != 'super admin') {
            $query->where('user_id', $user->id);
        }

        $planRequest = $query->findOrFail($id);
        $planRequest->delete();

        return response()->json([
            'message' => 'Plan request deleted successfully'
        ]);
    }
}

