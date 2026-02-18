<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'order_id' => $this->order_id,
            'name' => $this->name,
            'email' => $this->email,
            'card_number' => $this->card_number,
            'card_exp_month' => $this->card_exp_month,
            'card_exp_year' => $this->card_exp_year,
            'plan_name' => $this->plan_name,
            'plan_id' => $this->plan_id,
            'price' => (float) $this->price,
            'price_currency' => $this->price_currency,
            'txn_id' => $this->txn_id,
            'payment_status' => $this->payment_status,
            'payment_type' => $this->payment_type,
            'receipt' => $this->receipt,
            'user_id' => $this->user_id,
            'is_refund' => $this->is_refund,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'user' => $this->whenLoaded('user'),
            'plan' => $this->whenLoaded('plan'),
        ];
    }
}

