<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class ReferralSetting extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'percentage',
        'per_signup_reward',
        'minimum_threshold_amount',
        'is_enable',
        'guideline',
        'created_by',
    ];
}
