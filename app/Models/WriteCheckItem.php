<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WriteCheckItem extends Model
{
    protected $fillable = [
        'write_check_id',
        'chart_of_account_id',
        'product_id',
        'description',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'chart_of_account_id' => 'integer',
        'product_id' => 'integer',
        'write_check_id' => 'integer',
    ];

    public function writeCheck()
    {
        return $this->belongsTo(WriteCheck::class, 'write_check_id');
    }

    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }
}
