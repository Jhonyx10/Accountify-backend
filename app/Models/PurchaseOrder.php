<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'vender_id',
        'po_date',
        'delivery_date',
        'status',
        'category_id',
        'shipping_display',
        'discount_apply',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'po_date' => 'date',
        'delivery_date' => 'date',
        'status' => 'integer',
        'shipping_display' => 'integer',
        'discount_apply' => 'integer',
    ];

    public function vender()
    {
        return $this->belongsTo(Vender::class, 'vender_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function category()
    {
        return $this->belongsTo(ProductServiceCategory::class, 'category_id');
    }

    public function products()
    {
        return $this->hasMany(PurchaseOrderProduct::class, 'purchase_order_id');
    }

    public function getTotalAmountAttribute()
    {
        return $this->products->sum(function ($product) {
            return ($product->price * $product->quantity) - $product->discount;
        });
    }
}
