<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'po_number',
        'vender_id',
        'po_date',
        'delivery_date',
        'status',
        'category_id',
        'shipping_display',
        'discount_apply',
        'created_by'
    ];

    public function vender()
    {
        return $this->belongsTo(Vender::class, 'vender_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderProduct::class, 'purchase_order_id');
    }
}
