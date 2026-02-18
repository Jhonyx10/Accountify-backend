<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'user_id' => $this->user_id,
            'user_type' => $this->user_type,
            'account' => $this->account,
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'description' => $this->description,
            'date' => $this->date?->format('Y-m-d'),
            'created_by' => $this->created_by,
            'payment_id' => $this->payment_id,
            'category' => $this->category,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'creator' => $this->whenLoaded('creator'),
            'bank_account' => $this->whenLoaded('bankAccount'),
            'user' => $this->whenLoaded('user'),
            'payment' => $this->whenLoaded('payment'),
        ];
    }
}

