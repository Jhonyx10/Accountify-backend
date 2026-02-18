<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
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
            'customer' => $this->customer,
            'subject' => $this->subject,
            'value' => $this->value,
            'type' => $this->type,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'edit_status' => $this->edit_status,
            'description' => $this->description,
            'notes' => $this->notes,
            'customer_signature' => $this->customer_signature,
            'company_signature' => $this->company_signature,
            'created_by' => $this->created_by,
            'customer_data' => $this->whenLoaded('customerRelation', function () {
                return [
                    'id' => $this->customerRelation->id,
                    'customer_id' => $this->customerRelation->customer_id,
                    'name' => $this->customerRelation->name,
                    'email' => $this->customerRelation->email,
                ];
            }),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

