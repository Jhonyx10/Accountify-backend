<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BenefitPaymentController extends Controller
{
    /**
     * Start Invoice payment via BenefitPay 
     */
    public function invoicePayWithBenefit(Request $request, $invoiceId)
    {
        // This acts as a placeholder for the legacy BenefitPay logic

        $request->validate([
            'amount' => 'required|numeric|min:0'
        ]);

        return response()->json([
            'success' => false,
            'message' => 'BenefitPay integration pending'
        ], 501);
    }

    /**
     * Start Retainer payment via BenefitPay
     */
    public function retainerPayWithBenefit(Request $request, $retainerId)
    {
        // This acts as a placeholder for the legacy BenefitPay logic

        $request->validate([
            'amount' => 'required|numeric|min:0'
        ]);

        return response()->json([
            'success' => false,
            'message' => 'BenefitPay integration pending'
        ], 501);
    }

    /**
     * BenefitPay generic callback handler
     */
    public function call_back(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'BenefitPay integration callback pending'
        ], 501);
    }
}
