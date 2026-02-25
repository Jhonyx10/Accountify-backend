<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WriteCheck extends Model
{
    protected $fillable = [
        'bank_account_id',
        'payee_id',
        'payee_type',
        'date',
        'reference',
        'amount',
        'description',
        'created_by'
    ];
}
