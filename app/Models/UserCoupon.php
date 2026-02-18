<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCoupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'user',
        'coupon',
    ];

    protected $casts = [
        'user' => 'integer',
        'coupon' => 'integer',
    ];

    /**
     * Get the user that used the coupon
     */
    public function userRelation()
    {
        return $this->belongsTo(User::class, 'user');
    }

    /**
     * Get the coupon that was used
     */
    public function couponRelation()
    {
        return $this->belongsTo(Coupon::class, 'coupon');
    }
}

