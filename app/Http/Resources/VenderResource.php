<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VenderResource extends JsonResource
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
            'vender_id' => $this->vender_id,
            'name' => $this->name,
            'email' => $this->email,
            'contact' => $this->contact,
            'tax_number' => $this->tax_number,
            'avatar' => $this->avatar,
            'is_active' => $this->is_active,
            'is_enable_login' => $this->is_enable_login,
            'balance' => $this->balance,
            'billing' => [
                'name' => $this->billing_name,
                'country' => $this->billing_country,
                'state' => $this->billing_state,
                'city' => $this->billing_city,
                'phone' => $this->billing_phone,
                'zip' => $this->billing_zip,
                'address' => $this->billing_address,
            ],
            'shipping' => [
                'name' => $this->shipping_name,
                'country' => $this->shipping_country,
                'state' => $this->shipping_state,
                'city' => $this->shipping_city,
                'phone' => $this->shipping_phone,
                'zip' => $this->shipping_zip,
                'address' => $this->shipping_address,
            ],
            'lang' => $this->lang,
            'last_login_at' => $this->last_login_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            'bills_count' => $this->whenCounted('bills'),
            'bills' => BillResource::collection($this->whenLoaded('bills')),
        ];
    }
}
