<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductServiceResource extends JsonResource
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
            'sku' => $this->sku,
            'sale_price' => $this->sale_price,
            'purchase_price' => $this->purchase_price,
            'quantity' => $this->quantity,
            'tax_id' => $this->tax_id,
            'category_id' => $this->category_id,
            'unit_id' => $this->unit_id,
            'type' => $this->type,
            'sale_chartaccount_id' => $this->sale_chartaccount_id,
            'expense_chartaccount_id' => $this->expense_chartaccount_id,
            'description' => $this->description,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'category' => $this->whenLoaded('category'),
            'unit' => $this->whenLoaded('unit'),
            'sale_account' => $this->whenLoaded('saleChartAccount'),
            'expense_account' => $this->whenLoaded('expenseChartAccount'),
            'custom_fields' => $this->whenLoaded('customFieldValues'),
        ];
    }
}
