<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['vender', 'account', 'creator', 'bill']);

        if ($request->user()) {
            $query->where('created_by', $request->user()->id);
        }

        if ($request->has('vender_id')) {
            $query->where('vender_id', $request->vender_id);
        }

        if ($request->has('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $payments = $query->latest('date')->paginate($perPage);

        return PaymentResource::collection($payments);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'vender_id' => 'nullable|exists:venders,id',
            'account_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $payment = Payment::create([
            'date' => $request->date,
            'amount' => $request->amount,
            'account_id' => $request->account_id,
            'vender_id' => $request->vender_id,
            'bill_id' => $request->bill_id,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'recurring' => $request->recurring,
            'payment_method' => $request->payment_method,
            'reference' => $request->reference,
            'add_receipt' => $request->add_receipt,
            'created_by' => $request->user()->id,
        ]);

        if ($request->filled('bill_id')) {
            $bill = Bill::find($request->bill_id);
            if ($bill) {
                BillPayment::create([
                    'bill_id' => $bill->id,
                    'date' => $request->date,
                    'amount' => $request->amount,
                    'account_id' => $request->account_id,
                    'payment_method' => $request->payment_method,
                    'reference' => $request->reference ?? '',
                    'description' => $request->description ?? '',
                    'add_receipt' => $request->add_receipt ?? '',
                ]);
                
                $totalPaid = BillPayment::where('bill_id', $bill->id)->sum('amount');
                $totalDue = $bill->total_amount ?? 0;
                if ($totalPaid >= $totalDue && $totalDue > 0) {
                    $bill->status = 3;
                } elseif ($totalPaid > 0) {
                    $bill->status = 2;
                } else {
                    $bill->status = 1;
                }
                $bill->save();
            }
        }

        Transaction::create([
            'user_id' => $request->vender_id ?? 0,
            'user_type' => 'Vender',
            'account' => $request->account_id ?? 0,
            'type' => 'Payment',
            'amount' => $request->amount,
            'description' => $request->description ?? 'Payment',
            'date' => $request->date,
            'created_by' => $request->user()->id ?? 0,
            'payment_id' => $payment->id,
            'category' => $request->category_id ?? 'Payment',
        ]);

        return (new PaymentResource($payment->load(['vender', 'account', 'bill'])))
            ->additional(['message' => 'Payment created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $id)
    {
        $payment = Payment::with(['vender', 'account', 'creator', 'bill'])->findOrFail($id);

        return new PaymentResource($payment);
    }

    public function update(Request $request, string $id)
    {
        $payment = Payment::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'date' => 'sometimes|required|date',
            'amount' => 'sometimes|required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $payment->update($request->except(['created_by']));

        return (new PaymentResource($payment->load(['vender', 'account'])))
            ->additional(['message' => 'Payment updated successfully']);
    }

    public function destroy(string $id)
    {
        $payment = Payment::findOrFail($id);
        $payment->delete();

        return response()->json(['message' => 'Payment deleted successfully']);
    }
}
