<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralTransactionOrder extends Model
{
    protected $fillable = [
        'req_amount',
        'req_user_id',
        'status',
        'date',
    ];
}
