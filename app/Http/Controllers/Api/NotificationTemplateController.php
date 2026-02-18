<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationTemplateResource;
use App\Models\NotificationTemplate;
use App\Models\NotificationTemplateLang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class NotificationTemplateController extends Controller
{
    /**
     * Display a listing of notification templates
     */
    public function index(Request $request)
    {
        $query = NotificationTemplate::with('languages');

        // Search by name or slug
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $templates = $query->latest()->paginate($perPage);

        return NotificationTemplateResource::collection($templates);
    }

    /**
     * Store a newly created notification template
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:notification_templates,slug',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $slug = $request->slug ?? Str::slug($request->name);

        $template = NotificationTemplate::create([
            'name' => $request->name,
            'slug' => $slug,
        ]);

        return (new NotificationTemplateResource($template))
            ->additional(['message' => 'Notification template created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified notification template
     */
    public function show(string $id)
    {
        $template = NotificationTemplate::with('languages')->findOrFail($id);

        return new NotificationTemplateResource($template);
    }

    /**
     * Get notification template by slug
     */
    public function getBySlug(string $slug)
    {
        $template = NotificationTemplate::with('languages')
            ->where('slug', $slug)
            ->firstOrFail();

        return new NotificationTemplateResource($template);
    }

    /**
     * Update the specified notification template
     */
    public function update(Request $request, string $id)
    {
        $template = NotificationTemplate::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:notification_templates,slug,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $template->update($request->all());

        return (new NotificationTemplateResource($template->load('languages')))
            ->additional(['message' => 'Notification template updated successfully']);
    }

    /**
     * Remove the specified notification template
     */
    public function destroy(string $id)
    {
        $template = NotificationTemplate::findOrFail($id);

        // Delete all associated language versions
        NotificationTemplateLang::where('parent_id', $id)->delete();

        $template->delete();

        return response()->json([
            'message' => 'Notification template deleted successfully'
        ]);
    }

    /**
     * Update notification template content for a specific language
     */
    public function updateLanguage(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'lang' => 'required|string|max:10',
            'content' => 'required|string',
            'variables' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $template = NotificationTemplate::findOrFail($id);
        $creatorId = $request->user()->creatorId();

        $templateLang = NotificationTemplateLang::updateOrCreate(
            [
                'parent_id' => $id,
                'lang' => $request->lang,
            ],
            [
                'content' => $request->content,
                'variables' => $request->variables,
                'created_by' => $creatorId,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification template language updated successfully',
            'data' => $templateLang,
        ]);
    }
}

