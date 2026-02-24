<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReferralSetting;
use App\Models\ReferralTransaction;
use App\Models\ReferralTransactionOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReferralProgramController extends Controller
{
    /**
     * Get referral program statistics for the current user
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Calculate statistics based on Referral transactions
        $transactions = ReferralTransaction::where('referral_code', $user->referral_code)
            ->with('getUser') // A dummy model relation
            ->get();

        $totalEarnings = $transactions->sum('commission_amount');

        $stats = [
            'referral_code' => $user->referral_code,
            'total_earnings' => $totalEarnings,
            'total_referred' => $transactions->count(),
            'transactions' => $transactions
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get or Update global referral settings (Super Admin)
     */
    public function settings(Request $request)
    {
        $user = $request->user();

        if ($user->type !== 'super admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($request->isMethod('post')) {
            $request->validate([
                'commission_percentage' => 'required|numeric|min:0|max:100',
                'minimum_payout' => 'required|numeric|min:0',
                'is_active' => 'required|boolean'
            ]);

            $setting = ReferralSetting::updateOrCreate(
                ['id' => 1],
                [
                    'percentage' => $request->commission_percentage,
                    'minimum_threshold_amount' => $request->minimum_payout,
                    'is_enable' => $request->is_active ? 1 : 0
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Referral settings updated',
                'data' => $setting
            ]);
        }

        $setting = ReferralSetting::first();
        return response()->json([
            'success' => true,
            'data' => $setting
        ]);
    }
}
