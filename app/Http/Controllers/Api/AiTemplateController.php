<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiTemplateController extends Controller
{
    /**
     * Generate content using OpenAI API
     */
    public function generate(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string',
            'temperature' => 'nullable|numeric|min:0|max:1',
            'max_tokens' => 'nullable|integer|min:10|max:1000'
        ]);

        $settings = \App\Models\Setting::where('name', 'chatgpt_key')->first();
        if (!$settings || empty($settings->value)) {
            return response()->json([
                'success' => false,
                'message' => 'ChatGPT API key not configured'
            ], 400);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $settings->value,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                        'model' => 'gpt-3.5-turbo',
                        'messages' => [
                            ['role' => 'user', 'content' => $request->prompt]
                        ],
                        'temperature' => $request->temperature ?? 0.7,
                        'max_tokens' => $request->max_tokens ?? 250,
                    ]);

            if ($response->successful()) {
                $result = $response->json();

                return response()->json([
                    'success' => true,
                    'data' => [
                        'content' => $result['choices'][0]['message']['content'] ?? ''
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate content: ' . $response->body()
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'API Request Failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
