<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalProduct extends Model
{
    protected $fillable = [
        'proposal_id',
        'product_id',
        'quantity',
        'tax',
        'discount',
        'price',
        'description',
    ];

    protected $casts = [
        'proposal_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'decimal:2',
        'discount' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class, 'proposal_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductService::class, 'product_id');
    }
}
