<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillAccount extends Model
{
    protected $fillable = [
        'chart_account_id',
        'price',
        'description',
        'type',
        'ref_id',
    ];

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_account_id');
    }
}
