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
            3 => 'Canceled',
        ];

        $items = $this->whenLoaded('products');

        return [
            'id' => $this->id,
            'po_number' => 'PO-' . str_pad($this->po_number, 4, '0', STR_PAD_LEFT),
            'vender_id' => $this->vender_id,
            'vendor' => $this->whenLoaded('vender', fn() => $this->vender?->name ?? 'Unknown Vendor'),
            'vender' => $this->whenLoaded('vender', function () {
                return [
                    'id' => $this->vender?->id,
                    'name' => $this->vender?->name,
                    'email' => $this->vender?->email,
                ];
            }),
            'po_date' => $this->po_date?->format('Y-m-d'),
            'delivery_date' => $this->delivery_date?->format('Y-m-d'),
            'status' => $statusMap[$this->status ?? 0] ?? 'Draft',
            'shipping_display' => $this->shipping_display,
            'discount_apply' => $this->discount_apply,
            'notes' => $this->notes,
            'category_id' => $this->category_id,
            'amount' => $this->whenLoaded('products', function () {
                return $this->products->sum(function ($item) {
                    $sub = $item->quantity * $item->price;
                    $taxAmt = $sub * (($item->tax ?? 0) / 100);
                    return $sub + $taxAmt - ($item->discount ?? 0);
                });
            }, 0),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'items' => $items,
        ];
    }
}
