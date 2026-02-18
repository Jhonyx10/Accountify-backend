<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GoalResource;
use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GoalController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Goal::query();

        if ($user->type != 'super admin') {
            $query->where('created_by', $user->creatorId());
        }

        if ($request->has('search')) {
            $query->where('name', 'LIKE', "%{$request->search}%");
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_display')) {
            $query->where('is_display', $request->is_display);
        }

        $query->with('creator');
        $perPage = $request->input('per_page', 15);
        $goals = $query->latest()->paginate($perPage);

        return GoalResource::collection($goals);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'from' => 'required|string|max:255',
            'to' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'is_display' => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $goal = Goal::create([
            'name' => $request->name,
            'type' => $request->type,
            'from' => $request->from,
            'to' => $request->to,
            'amount' => $request->amount,
            'is_display' => $request->is_display ?? 1,
            'created_by' => Auth::user()->creatorId(),
        ]);

        $goal->load('creator');

        return response()->json([
            'success' => true,
            'message' => 'Goal created successfully',
            'data' => new GoalResource($goal)
        ], 201);
    }

    public function show(string $id)
    {
        $user = Auth::user();
        $goal = Goal::with('creator')->find($id);

        if (!$goal) {
            return response()->json(['success' => false, 'message' => 'Goal not found'], 404);
        }

        if ($user->type != 'super admin' && $goal->created_by != $user->creatorId()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        return response()->json(['success' => true, 'data' => new GoalResource($goal)]);
    }

    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $goal = Goal::find($id);

        if (!$goal) {
            return response()->json(['success' => false, 'message' => 'Goal not found'], 404);
        }

        if ($user->type != 'super admin' && $goal->created_by != $user->creatorId()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|max:255',
            'from' => 'sometimes|required|string|max:255',
            'to' => 'sometimes|required|string|max:255',
            'amount' => 'sometimes|required|numeric|min:0',
            'is_display' => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $goal->update($request->only(['name', 'type', 'from', 'to', 'amount', 'is_display']));
        $goal->load('creator');

        return response()->json([
            'success' => true,
            'message' => 'Goal updated successfully',
            'data' => new GoalResource($goal)
        ]);
    }

    public function destroy(string $id)
    {
        $user = Auth::user();
        $goal = Goal::find($id);

        if (!$goal) {
            return response()->json(['success' => false, 'message' => 'Goal not found'], 404);
        }

        if ($user->type != 'super admin' && $goal->created_by != $user->creatorId()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $goal->delete();

        return response()->json(['success' => true, 'message' => 'Goal deleted successfully']);
    }
}
