<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'name',
        'email',
        'card_number',
        'card_exp_month',
        'card_exp_year',
        'plan_name',
        'plan_id',
        'price',
        'price_currency',
        'txn_id',
        'payment_status',
        'payment_type',
        'receipt',
        'user_id',
        'is_refund',
    ];

    protected $casts = [
        'plan_id' => 'integer',
        'price' => 'decimal:2',
        'user_id' => 'integer',
        'is_refund' => 'integer',
    ];

    /**
     * Get the user that placed the order
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the plan associated with the order
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }
}
