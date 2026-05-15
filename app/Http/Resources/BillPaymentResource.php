<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillPaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bill_id' => $this->bill_id,
            'date' => $this->date ? $this->date->format('Y-m-d') : null,
            'amount' => (float) $this->amount,
            'account_id' => $this->account_id,
            'payment_method' => $this->payment_method,
            'reference' => $this->reference,
            'add_receipt' => $this->add_receipt,
            'description' => $this->description,
            'account' => new BankAccountResource($this->whenLoaded('account')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
