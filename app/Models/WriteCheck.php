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

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function payee()
    {
        if ($this->payee_type === 'vendor' || $this->payee_type == 0) {
            return $this->belongsTo(Vender::class, 'payee_id');
        } elseif ($this->payee_type === 'customer' || $this->payee_type == 1) {
            return $this->belongsTo(Customer::class, 'payee_id');
        }
        return null;
    }

    public function items()
    {
        return $this->hasMany(WriteCheckItem::class, 'write_check_id');
    }
}
