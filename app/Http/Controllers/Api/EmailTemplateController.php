<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmailTemplateResource;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EmailTemplateController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = EmailTemplate::query();

        if ($user->type != 'super admin') {
            $query->where('created_by', $user->creatorId());
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('slug', 'LIKE', "%{$search}%");
            });
        }

        $query->with('creator');
        $perPage = $request->input('per_page', 15);
        $emailTemplates = $query->latest()->paginate($perPage);

        return EmailTemplateResource::collection($emailTemplates);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'from' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:email_templates,slug',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $emailTemplate = EmailTemplate::create([
            'name' => $request->name,
            'from' => $request->from,
            'slug' => $request->slug ?? Str::slug($request->name),
            'created_by' => Auth::user()->creatorId(),
        ]);

        $emailTemplate->load('creator');

        return response()->json([
            'success' => true,
            'message' => 'Email template created successfully',
            'data' => new EmailTemplateResource($emailTemplate)
        ], 201);
    }

    public function show(string $id)
    {
        $user = Auth::user();
        $emailTemplate = EmailTemplate::with('creator')->find($id);

        if (!$emailTemplate) {
            return response()->json(['success' => false, 'message' => 'Email template not found'], 404);
        }

        if ($user->type != 'super admin' && $emailTemplate->created_by != $user->creatorId()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        return response()->json(['success' => true, 'data' => new EmailTemplateResource($emailTemplate)]);
    }

    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $emailTemplate = EmailTemplate::find($id);

        if (!$emailTemplate) {
            return response()->json(['success' => false, 'message' => 'Email template not found'], 404);
        }

        if ($user->type != 'super admin' && $emailTemplate->created_by != $user->creatorId()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'from' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:email_templates,slug,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $emailTemplate->update($request->only(['name', 'from', 'slug']));
        $emailTemplate->load('creator');

        return response()->json([
            'success' => true,
            'message' => 'Email template updated successfully',
            'data' => new EmailTemplateResource($emailTemplate)
        ]);
    }

    public function destroy(string $id)
    {
        $user = Auth::user();
        $emailTemplate = EmailTemplate::find($id);

        if (!$emailTemplate) {
            return response()->json(['success' => false, 'message' => 'Email template not found'], 404);
        }

        if ($user->type != 'super admin' && $emailTemplate->created_by != $user->creatorId()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $emailTemplate->delete();

        return response()->json(['success' => true, 'message' => 'Email template deleted successfully']);
    }
}
