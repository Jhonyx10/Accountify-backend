<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DebitNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'bill'        => $this->bill,
            'vendor'      => $this->vendor,
            'amount'      => (float) $this->amount,
            'date'        => $this->date?->format('Y-m-d'),
            'reference'   => 'DN-' . str_pad($this->id, 4, '0', STR_PAD_LEFT),
            'description' => $this->description,
            'status'      => 'Applied', // No status column in migration; default to Applied
            'bill_data'   => $this->whenLoaded('billRelation', function () {
                return [
                    'id'      => $this->billRelation->id,
                    'bill_id' => $this->billRelation->bill_id,
                ];
            }),
            'vendor_data' => $this->whenLoaded('vendorRelation', function () {
                return [
                    'id'    => $this->vendorRelation->id,
                    'name'  => $this->vendorRelation->name,
                    'email' => $this->vendorRelation->email,
                ];
            }),
            // Convenient flat fields for list view
            'vendor_name' => $this->whenLoaded('vendorRelation', fn() => $this->vendorRelation->name, '—'),
            'bill_number' => $this->whenLoaded('billRelation', fn() => $this->billRelation->bill_id ?? '—', '—'),
            'created_at'  => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
