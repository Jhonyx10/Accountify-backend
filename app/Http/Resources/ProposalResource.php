<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProposalResource extends JsonResource
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
            'proposal_id' => $this->proposal_id,
            'customer_id' => $this->customer_id,
            'issue_date' => $this->issue_date?->format('Y-m-d'),
            'send_date' => $this->send_date?->format('Y-m-d'),
            'category_id' => $this->category_id,
            'status' => $this->status,
            'discount_apply' => $this->discount_apply,
            'is_convert' => $this->is_convert,
            'converted_invoice_id' => $this->converted_invoice_id,
            'converted_retainer_id' => $this->converted_retainer_id,
            'created_by' => $this->created_by,
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'customer_id' => $this->customer->customer_id,
                    'name' => $this->customer->name,
                    'email' => $this->customer->email,
                ];
            }),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            'products' => $this->whenLoaded('products'),
            'products_count' => $this->whenCounted('products'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

