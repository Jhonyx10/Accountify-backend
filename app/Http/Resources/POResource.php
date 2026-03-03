<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class POResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $statusMap = [
            0 => 'Draft',
            1 => 'Sent',
            2 => 'Billed',
            3 => 'Canceled'
        ];

        return [
            'id' => $this->id,
            'po_number' => 'PO-' . str_pad($this->po_number, 4, '0', STR_PAD_LEFT),
            'vendor' => $this->vender ? $this->vender->name ?? $this->vender->billing_name : 'Unknown Vendor',
            'po_date' => $this->po_date,
            'delivery_date' => $this->delivery_date,
            'amount' => $this->items ? collect($this->items)->sum(function ($item) {
                $sub = $item->quantity * $item->price;
                $taxAmt = $sub * (($item->tax ?? 0) / 100);
                return $sub + $taxAmt - ($item->discount ?? 0);
            }) : 0,
            'status' => $statusMap[$this->status ?? 0] ?? 'Draft',
            'items' => $this->whenLoaded('items')
        ];
    }
}
