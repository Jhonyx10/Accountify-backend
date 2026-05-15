<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralTransaction extends Model
{
    protected $fillable = [
        'company_id',
        'plan_id',
        'plan_price',
        'commission',
        'referral_code',
    ];
    
    // As observed in ReferralProgramController: ->with('getUser')
    public function getUser()
    {
        return $this->belongsTo(User::class, 'company_id'); // Just an assumption based on `company_id` being the referred user?
    }
}
