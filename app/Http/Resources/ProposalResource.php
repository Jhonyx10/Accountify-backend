<?php

namespace App\Http\Resources;

use App\Models\Proposal;
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
        // Calculate totals from products
        $products = $this->whenLoaded('products');
        $subtotal = 0;
        $totalTax = 0;

        if ($this->relationLoaded('products')) {
            foreach ($this->products as $product) {
                $lineTotal = $product->quantity * $product->price;
                $subtotal += $lineTotal;

                // Parse tax (stored as comma-separated tax IDs or percentage string)
                if ($product->tax) {
                    $taxRate = is_numeric($product->tax) ? floatval($product->tax) : 0;
                    $totalTax += $lineTotal * ($taxRate / 100);
                }
            }
        }

        return [
            'id' => $this->id,
            'proposal_id' => $this->proposal_id,
            'customer_id' => $this->customer_id,
            'issue_date' => $this->issue_date?->format('Y-m-d'),
            'send_date' => $this->send_date?->format('Y-m-d'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'category_id' => $this->category_id,
            'status' => $this->status,
            'status_label' => Proposal::STATUS_MAP[$this->status] ?? 'Unknown',
            'discount_apply' => $this->discount_apply,
            'notes' => $this->notes,
            'is_convert' => $this->is_convert,
            'converted_invoice_id' => $this->converted_invoice_id,
            'converted_retainer_id' => $this->converted_retainer_id,
            'created_by' => $this->created_by,

            // Computed totals
            'subtotal' => round($subtotal, 2),
            'total_tax' => round($totalTax, 2),
            'grand_total' => round($subtotal + $totalTax, 2),

            // Relations
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'customer_id' => $this->customer->customer_id ?? null,
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
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),
            'converted_invoice' => $this->whenLoaded('convertedInvoice', function () {
                return [
                    'id' => $this->convertedInvoice->id,
                    'invoice_id' => $this->convertedInvoice->invoice_id,
                ];
            }),
            'products' => $this->whenLoaded('products', function () {
                return $this->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'product_id' => $product->product_id,
                        'quantity' => floatval($product->quantity),
                        'price' => floatval($product->price),
                        'tax' => $product->tax,
                        'discount' => floatval($product->discount),
                        'description' => $product->description,
                        'line_total' => round($product->quantity * $product->price, 2),
                        'product' => $product->relationLoaded('product') && $product->product ? [
                            'id' => $product->product->id,
                            'name' => $product->product->name,
                        ] : null,
                    ];
                });
            }),
            'products_count' => $this->whenCounted('products'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
