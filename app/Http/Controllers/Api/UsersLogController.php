<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginDetail;
use Illuminate\Http\Request;
use Carbon\Carbon;

class UsersLogController extends Controller
{
    /**
     * Display login activity for users
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = LoginDetail::with('user');

        // Multi-tenancy filter
        if ($user->type !== 'super admin') {
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('created_by', $user->creatorId());
            });
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Date filters
        if ($request->has('month')) {
            $month = Carbon::parse($request->month);
            $query->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year);
        }

        $logs = $query->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Delete user login log
     */
    public function destroy(Request $request, $id)
    {
        $log = LoginDetail::findOrFail($id);

        $user = $request->user();
        if ($user->type !== 'super admin' && $log->user->created_by !== $user->creatorId()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $log->delete();

        return response()->json([
            'success' => true,
            'message' => 'Log deleted successfully'
        ]);
    }
}
