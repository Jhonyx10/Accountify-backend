<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceProduct extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'quantity',
        'tax',
        'discount',
        'price',
        'description',
    ];

    protected $casts = [
        'invoice_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'decimal:2',
        'discount' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductService::class, 'product_id');
    }
}
