<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;

class Coupon extends Model
{
    use HasFactory;
    use BelongsToCompany;

    protected $fillable = [
        'name',
        'code',
        'discount',
        'limit',
        'description',
        'is_active',
    ];

    protected $casts = [
        'discount' => 'decimal:2',
        'limit' => 'integer',
        'is_active' => 'integer',
    ];

    /**
     * Get the user coupons associated with this coupon
     */
    public function userCoupons()
    {
        return $this->hasMany(UserCoupon::class, 'coupon');
    }

    /**
     * Check if coupon is still valid (not exceeded limit)
     */
    public function isValid()
    {
        if ($this->is_active == 0) {
            return false;
        }

        $usedCount = $this->userCoupons()->count();

        if ($this->limit > 0 && $usedCount >= $this->limit) {
            return false;
        }

        return true;
    }
}

