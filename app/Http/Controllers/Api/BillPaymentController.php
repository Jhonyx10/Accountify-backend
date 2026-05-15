<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BillPaymentResource;
use App\Models\Bill;
use App\Models\BillPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BillPaymentController extends Controller
{
    public function index(Request $request, string $billId)
    {
        $payments = BillPayment::where('bill_id', $billId)
            ->with(['account', 'bill'])
            ->latest()
            ->get();

        return BillPaymentResource::collection($payments);
    }

    public function store(Request $request, string $billId)
    {
        $bill = Bill::findOrFail($billId);

        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'account_id' => 'required|exists:bank_accounts,id',
            'payment_method' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $payment = BillPayment::create([
            'bill_id' => $bill->id,
            'date' => $request->date,
            'amount' => $request->amount,
            'account_id' => $request->account_id,
            'payment_method' => $request->payment_method,
            'reference' => $request->reference ?? '',
            'description' => $request->description ?? '',
            'add_receipt' => $request->add_receipt ?? '',
        ]);

        // Auto-update bill status
        $this->updateBillStatus($bill);

        return (new BillPaymentResource($payment->load('account')))
            ->additional(['message' => 'Payment added successfully', 'bill_status' => $bill->status])
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(string $billId, string $id)
    {
        $bill = Bill::findOrFail($billId);
        $payment = BillPayment::where('bill_id', $billId)->findOrFail($id);

        $payment->delete();

        // Auto-update bill status
        $this->updateBillStatus($bill);

        return response()->json(['message' => 'Payment deleted successfully', 'bill_status' => $bill->status]);
    }

    protected function updateBillStatus(Bill $bill)
    {
        $totalPaid = BillPayment::where('bill_id', $bill->id)->sum('amount');
        $totalDue = $bill->total_amount; // Use accessor

        if ($totalPaid >= $totalDue && $totalDue > 0) {
            $bill->status = 3; // Paid
        } elseif ($totalPaid > 0) {
            $bill->status = 2; // Partially Paid
        } elseif (now()->startOfDay()->greaterThan(\Carbon\Carbon::parse($bill->due_date))) {
            $bill->status = 4; // Overdue
        } else {
            $bill->status = 1; // Open (Assuming draft was already opened)
        }

        $bill->save();
    }
}
