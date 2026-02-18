<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SystemController extends Controller
{
    /**
     * Display all settings for the authenticated user
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = Setting::with('creator');

        // Multi-tenancy filtering
        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $perPage = $request->input('per_page', 50);
        $settings = $query->latest()->paginate($perPage);

        return SettingResource::collection($settings);
    }

    /**
     * Get all settings as key-value pairs
     */
    public function getSettings(Request $request)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $settings = Setting::where('created_by', $creatorId)->get();

        $settingsArray = [];
        foreach ($settings as $setting) {
            $settingsArray[$setting->name] = $setting->value;
        }

        return response()->json([
            'success' => true,
            'data' => $settingsArray,
        ]);
    }

    /**
     * Get a specific setting by name
     */
    public function getSetting(Request $request, string $name)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $setting = Setting::where('name', $name)
            ->where('created_by', $creatorId)
            ->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found',
            ], 404);
        }

        return new SettingResource($setting);
    }

    /**
     * Store or update a setting
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'value' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $creatorId = $request->user()->creatorId();

        $setting = Setting::updateOrCreate(
            [
                'name' => $request->name,
                'created_by' => $creatorId,
            ],
            [
                'value' => $request->value,
            ]
        );

        return (new SettingResource($setting->load('creator')))
            ->additional(['message' => 'Setting saved successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Bulk update settings
     */
    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.name' => 'required|string|max:255',
            'settings.*.value' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $creatorId = $request->user()->creatorId();

        foreach ($request->settings as $settingData) {
            Setting::updateOrCreate(
                [
                    'name' => $settingData['name'],
                    'created_by' => $creatorId,
                ],
                [
                    'value' => $settingData['value'] ?? null,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
        ]);
    }

    /**
     * Delete a setting
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $query = Setting::query();

        if ($user->type != 'super admin') {
            $query->where('created_by', $creatorId);
        }

        $setting = $query->findOrFail($id);
        $setting->delete();

        return response()->json([
            'message' => 'Setting deleted successfully'
        ]);
    }
}

