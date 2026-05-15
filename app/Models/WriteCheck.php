<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WriteCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_account_id',
        'payee_id',
        'payee_type',  // 1 = Vendor, 2 = Customer
        'date',
        'reference',
        'amount',
        'description',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'bank_account_id' => 'integer',
        'payee_id' => 'integer',
        'payee_type' => 'integer',
        'created_by' => 'integer',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    /**
     * Resolve payee name safely without eager loading issues.
     * payee_type: 1 = Vendor, 2 = Customer
     */
    public function getPayeeNameAttribute(): string
    {
        if ($this->payee_type == 1) {
            $vendor = Vender::find($this->payee_id);
            return $vendor?->name ?? 'Unknown Vendor';
        } elseif ($this->payee_type == 2) {
            $customer = Customer::find($this->payee_id);
            return $customer ? ($customer->first_name . ' ' . $customer->last_name) : 'Unknown Customer';
        }
        return '—';
    }

    public function items()
    {
        return $this->hasMany(WriteCheckItem::class, 'write_check_id');
    }
}
