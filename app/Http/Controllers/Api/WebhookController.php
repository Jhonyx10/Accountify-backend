<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WebhookController extends Controller
{
    /**
     * Get webhook endpoints
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $creatorId = $user->creatorId();

        $webhooks = Webhook::where('created_by', $creatorId)
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $webhooks
        ]);
    }

    /**
     * Store webhook setting
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'module' => 'required|string|max:255',
            'url' => 'required|url',
            'method' => 'required|string|in:GET,POST'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        $webhook = Webhook::create([
            'module' => $request->input('module'),
            'url' => $request->input('url'),
            'method' => $request->input('method'),
            'created_by' => $user->creatorId()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Webhook created successfully',
            'data' => $webhook
        ], 201);
    }

    /**
     * Delete webhook setting
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $webhook = Webhook::where('id', $id)
            ->where('created_by', $user->creatorId())
            ->firstOrFail();

        $webhook->delete();

        return response()->json([
            'success' => true,
            'message' => 'Webhook deleted successfully'
        ]);
    }
}
