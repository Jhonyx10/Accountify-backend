<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
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
            'date' => $this->date?->format('Y-m-d'),
            'amount' => $this->amount,
            'account_id' => $this->account_id,
            'vender_id' => $this->vender_id,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'payment_method' => $this->payment_method,
            'reference' => $this->reference,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'vender' => $this->whenLoaded('vender', function () {
                return [
                    'id' => $this->vender->id,
                    'name' => $this->vender->name,
                ];
            }),
            'account' => $this->whenLoaded('account'),
            'category' => $this->whenLoaded('category'),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
        ];
    }
}
