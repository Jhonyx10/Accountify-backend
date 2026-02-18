<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DebitNoteResource extends JsonResource
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
            'bill' => $this->bill,
            'vendor' => $this->vendor,
            'amount' => $this->amount,
            'date' => $this->date?->format('Y-m-d'),
            'description' => $this->description,
            'bill_data' => $this->whenLoaded('billRelation', function () {
                return [
                    'id' => $this->billRelation->id,
                    'bill_id' => $this->billRelation->bill_id,
                ];
            }),
            'vendor_data' => $this->whenLoaded('vendorRelation', function () {
                return [
                    'id' => $this->vendorRelation->id,
                    'name' => $this->vendorRelation->name,
                    'email' => $this->vendorRelation->email,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

