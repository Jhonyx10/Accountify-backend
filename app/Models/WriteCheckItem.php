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
        'amount'
    ];
}
