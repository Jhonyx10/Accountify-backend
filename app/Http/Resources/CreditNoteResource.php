<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditNoteResource extends JsonResource
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
            'invoice' => $this->invoice,
            'customer' => $this->customer,
            'amount' => $this->amount,
            'date' => $this->date?->format('Y-m-d'),
            'description' => $this->description,
            'invoice_data' => $this->whenLoaded('invoiceRelation', function () {
                return [
                    'id' => $this->invoiceRelation->id,
                    'invoice_id' => $this->invoiceRelation->invoice_id,
                ];
            }),
            'customer_data' => $this->whenLoaded('customerRelation', function () {
                return [
                    'id' => $this->customerRelation->id,
                    'name' => $this->customerRelation->name,
                    'email' => $this->customerRelation->email,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

