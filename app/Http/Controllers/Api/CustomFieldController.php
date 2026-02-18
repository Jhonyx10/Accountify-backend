<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomFieldResource;
use App\Models\CustomField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CustomFieldController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = CustomField::query();

        if ($user->type != 'super admin') {
            $query->where('created_by', $user->creatorId());
        }

        if ($request->has('search')) {
            $query->where('name', 'LIKE', "%{$request->search}%");
        }

        if ($request->has('module')) {
            $query->where('module', $request->module);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $query->with('creator');
        $perPage = $request->input('per_page', 15);
        $customFields = $query->latest()->paginate($perPage);

        return CustomFieldResource::collection($customFields);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'module' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $customField = CustomField::create([
            'name' => $request->name,
            'type' => $request->type,
            'module' => $request->module,
            'created_by' => Auth::user()->creatorId(),
        ]);

        $customField->load('creator');

        return response()->json([
            'success' => true,
            'message' => 'Custom field created successfully',
            'data' => new CustomFieldResource($customField)
        ], 201);
    }

    public function show(string $id)
    {
        $user = Auth::user();
        $customField = CustomField::with('creator')->find($id);

        if (!$customField) {
            return response()->json(['success' => false, 'message' => 'Custom field not found'], 404);
        }

        if ($user->type != 'super admin' && $customField->created_by != $user->creatorId()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        return response()->json(['success' => true, 'data' => new CustomFieldResource($customField)]);
    }

    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $customField = CustomField::find($id);

        if (!$customField) {
            return response()->json(['success' => false, 'message' => 'Custom field not found'], 404);
        }

        if ($user->type != 'super admin' && $customField->created_by != $user->creatorId()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|max:255',
            'module' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $customField->update($request->only(['name', 'type', 'module']));
        $customField->load('creator');

        return response()->json([
            'success' => true,
            'message' => 'Custom field updated successfully',
            'data' => new CustomFieldResource($customField)
        ]);
    }

    public function destroy(string $id)
    {
        $user = Auth::user();
        $customField = CustomField::find($id);

        if (!$customField) {
            return response()->json(['success' => false, 'message' => 'Custom field not found'], 404);
        }

        if ($user->type != 'super admin' && $customField->created_by != $user->creatorId()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $customField->delete();

        return response()->json(['success' => true, 'message' => 'Custom field deleted successfully']);
    }
}
