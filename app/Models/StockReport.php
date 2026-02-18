<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity',
        'type',
        'type_id',
        'description',
        'created_by',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'quantity' => 'integer',
        'type_id' => 'integer',
        'created_by' => 'integer',
    ];

    /**
     * Get the creator (user) of the stock report
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the product associated with the stock report
     */
    public function product()
    {
        return $this->belongsTo(ProductService::class, 'product_id');
    }
}

