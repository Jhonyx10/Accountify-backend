<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'discount' => (float) $this->discount,
            'limit' => $this->limit,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'expires_at' => $this->expires_at?->format('Y-m-d H:i:s'),
            'is_valid' => $this->isValid(),
            'used_count' => $this->whenCounted('userCoupons'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

