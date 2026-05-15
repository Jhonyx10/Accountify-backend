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
    public function index(Request $request)
    {
        $user = $request->user();

        // Customer specific dashboard view
        if ($user instanceof \App\Models\Customer) {
            $referrals = \App\Models\Customer::where('used_referral_code', $user->referral_code)->get();
            $setting = ReferralSetting::first();
            $effectiveRewardAmount = $setting ? $setting->minimum_threshold_amount : 15;

            $referralData = $referrals->map(function ($refCust) use ($effectiveRewardAmount) {
                return [
                    'id' => $refCust->id,
                    'referrerName' => $refCust->name,
                    'referralCode' => $refCust->referral_code,
                    'referralLink' => $refCust->referral_link,
                    'signedUpCount' => 0, // Direct referrals for a customer don't typically have nested signups tracked here
                    'commissionEarned' => $effectiveRewardAmount,
                    'status' => $refCust->is_active ? 'Active' : 'Inactive',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'referral_code' => $user->referral_code,
                    'total_earnings' => $referrals->count() * $effectiveRewardAmount,
                    'total_referred' => $referrals->count(),
                    'saveRewardAmount' => $effectiveRewardAmount,
                    'transactions' => $referralData
                ]
            ]);
        }

        // Get customers created by the current company
        $customers = \App\Models\Customer::where('created_by', $user->creatorId())->get();
        $setting = ReferralSetting::first();
        $savedRewardAmount = $setting ? $setting->minimum_threshold_amount : null;
        $effectiveRewardAmount = $savedRewardAmount ?? 15;

        $referralData = $customers->map(function ($customer) use ($effectiveRewardAmount) {
            $signedUpCount = \App\Models\Customer::where('used_referral_code', $customer->referral_code)->count();
            $userSignedUpCount = User::where('used_referral_code', $customer->referral_code)->count();
            $totalSignedUp = $signedUpCount + $userSignedUpCount;
            
            return [
                'id' => $customer->id,
                'referrerName' => $customer->name,
                'referralCode' => $customer->referral_code,
                'referralLink' => $customer->referral_link,
                'signedUpCount' => $totalSignedUp,
                'commissionEarned' => $totalSignedUp * $effectiveRewardAmount,
                'status' => $customer->is_active ? 'Active' : 'Inactive',
            ];
        });

        $totalEarnings = $referralData->sum('commissionEarned');
        $totalReferred = $referralData->sum('signedUpCount');

        $stats = [
            'referral_code' => $user->referral_code,
            'total_earnings' => $totalEarnings,
            'total_referred' => $totalReferred,
            'saveRewardAmount' => $savedRewardAmount,
            'transactions' => $referralData,
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

        if ($request->isMethod('post') || $request->isMethod('put')) {
            $request->validate([
                'saveRewardAmount' => 'required|numeric|min:0',
            ]);

            // Scopes automatically via BelongsToCompany trait, but define explicitly to be safe
            $setting = ReferralSetting::firstOrNew(['created_by' => $user->creatorId()]);
            if (!$setting->exists) {
                $setting->percentage = 0;
                $setting->guideline = '';
            }
            $setting->minimum_threshold_amount = $request->saveRewardAmount;
            $setting->is_enable = $request->is_active ?? 1;
            $setting->save();

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
