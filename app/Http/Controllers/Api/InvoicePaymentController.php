<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Support\Facades\Validator;

class InvoicePaymentController extends Controller
{
    public function index($invoice_id)
    {
        $payments = InvoicePayment::where('invoice_id', $invoice_id)
            ->with(['account'])
            ->latest('date')
            ->get();

        return response()->json([
            'data' => $payments
        ]);
    }

    public function store(Request $request, $invoice_id)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'account_id' => 'required|integer|exists:bank_accounts,id',
            'reference' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $invoice = Invoice::with('products')->findOrFail($invoice_id);

        $payment = InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'date' => $request->date,
            'amount' => $request->amount,
            'account_id' => $request->account_id,
            'payment_method' => 0,
            'reference' => $request->reference,
            'description' => $request->description,
        ]);

        // Calculate if paid
        $totalPaid = $invoice->payments()->sum('amount');
        
        $subtotal = 0;
        $totalTax = 0;
        foreach ($invoice->products as $product) {
            $itemSubtotal = $product->quantity * $product->price;
            $subtotal += $itemSubtotal;
            $totalTax += $itemSubtotal * ((float)$product->tax / 100);
        }
        $grandTotal = $subtotal + $totalTax;

        if ($totalPaid >= $grandTotal) {
            $invoice->status = 2; // Paid
        } else {
            $invoice->status = 3; // Partially Paid
        }
        $invoice->save();

        return response()->json([
            'message' => 'Payment recorded successfully',
            'data' => $payment
        ], 201);
    }
}
