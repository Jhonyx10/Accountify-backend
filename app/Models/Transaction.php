<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_type',
        'account',
        'type',
        'amount',
        'description',
        'date',
        'created_by',
        'payment_id',
        'category',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'account' => 'integer',
        'amount' => 'decimal:2',
        'created_by' => 'integer',
        'payment_id' => 'integer',
        'date' => 'date',
    ];

    /**
     * Get the creator (user) of the transaction
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the bank account associated with the transaction
     */
    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'account');
    }

    /**
     * Get the user associated with the transaction
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the payment associated with the transaction
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}

