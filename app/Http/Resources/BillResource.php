<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillResource extends JsonResource
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
            'vender_id' => $this->vender_id,
            'bill_date' => $this->bill_date?->format('Y-m-d'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'send_date' => $this->send_date?->format('Y-m-d'),
            'order_number' => $this->order_number,
            'category_id' => $this->category_id,
            'status' => $this->status,
            'shipping_display' => $this->shipping_display,
            'discount_apply' => $this->discount_apply,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'vender' => $this->whenLoaded('vender', function () {
                return [
                    'id' => $this->vender->id,
                    'name' => $this->vender->name,
                    'email' => $this->vender->email,
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
        ];
    }
}
