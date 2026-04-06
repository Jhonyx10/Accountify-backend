<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCustomFields;

class Bill extends Model
{
    use HasFactory, HasCustomFields;

    protected $fillable = [
        'bill_id',
        'vender_id',
        'bill_date',
        'due_date',
        'order_number',
        'status',
        'shipping_display',
        'send_date',
        'discount_apply',
        'category_id',
        'created_by',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date' => 'date',
        'send_date' => 'date',
        'status' => 'integer',
        'shipping_display' => 'integer',
        'discount_apply' => 'integer',
    ];

    // Relationships
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
        return $this->hasMany(BillProduct::class, 'bill_id');
    }

    public function payments()
    {
        return $this->hasMany(BillPayment::class, 'bill_id');
    }

    public function accounts()
    {
        return $this->hasMany(BillAccount::class, 'ref_id')->where('type', 'Bill');
    }
}
