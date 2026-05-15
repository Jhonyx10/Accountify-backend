<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $subtotal = 0;
        $totalTax = 0;

        if ($this->relationLoaded('products')) {
            foreach ($this->products as $product) {
                $itemSubtotal = $product->quantity * $product->price;
                $subtotal += $itemSubtotal;
                $totalTax += $itemSubtotal * ((float)$product->tax / 100);
            }
        }

        $grandTotal = $subtotal + $totalTax;

        $totalPaid = 0;
        if ($this->relationLoaded('payments')) {
            $totalPaid = $this->payments->sum('amount');
        }

        $totalCredits = 0;
        if ($this->relationLoaded('creditNotes')) {
            $totalCredits = $this->creditNotes->sum('amount');
        }
        
        $balanceDue = max(0, $grandTotal - $totalPaid - $totalCredits);

        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'customer_id' => $this->customer_id,
            'issue_date' => $this->issue_date?->format('Y-m-d'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'send_date' => $this->send_date?->format('Y-m-d'),
            'category_id' => $this->category_id,
            'ref_number' => $this->ref_number,
            'status' => $this->status,
            'notes' => $this->notes,
            'shipping_display' => $this->shipping_display,
            'discount_apply' => $this->discount_apply,
            'subtotal' => $subtotal,
            'total_tax' => $totalTax,
            'grand_total' => $grandTotal,
            'total_paid' => $totalPaid,
            'total_credits' => $totalCredits,
            'balance_due' => $balanceDue,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'email' => $this->customer->email,
                ];
            }),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
            'products' => $this->whenLoaded('products'),
            'payments' => $this->whenLoaded('payments'),
            'credit_notes' => $this->whenLoaded('creditNotes'),
        ];
    }
}
