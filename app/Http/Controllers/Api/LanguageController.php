<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class LanguageController extends Controller
{
    /**
     * Get all available languages
     */
    public function index()
    {
        $languages = Language::all();
        return response()->json([
            'success' => true,
            'data' => $languages
        ]);
    }

    /**
     * Get translations for a specific language code
     */
    public function getTranslations(Request $request, $code)
    {
        // Fallback to english if language doesn't exist
        $langPath = base_path('resources/lang/' . $code . '.json');

        if (!File::exists($langPath)) {
            $langPath = base_path('resources/lang/en.json');
        }

        if (File::exists($langPath)) {
            $translations = json_decode(File::get($langPath), true);
            return response()->json([
                'success' => true,
                'data' => $translations
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Translations not found'
        ], 404);
    }

    /**
     * Store a new language (Super Admin)
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:10|unique:languages,code',
            'fullName' => 'required|string|max:100'
        ]);

        $language = Language::create([
            'code' => trim(strtolower($request->code)),
            'fullName' => trim(ucfirst($request->fullName))
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Language created successfully',
            'data' => $language
        ], 201);
    }

    /**
     * Apply a language for the current user
     */
    public function changeLanguage(Request $request)
    {
        $request->validate([
            'lang' => 'required|string|exists:languages,code'
        ]);

        $user = $request->user();
        if ($user) {
            $user->lang = $request->lang;
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Language changed successfully'
        ]);
    }
}
