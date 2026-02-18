<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Vender extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'vender_id',
        'name',
        'email',
        'tax_number',
        'password',
        'contact',
        'avatar',
        'created_by',
        'is_active',
        'is_enable_login',
        'billing_name',
        'billing_country',
        'billing_state',
        'billing_city',
        'billing_phone',
        'billing_zip',
        'billing_address',
        'shipping_name',
        'shipping_country',
        'shipping_state',
        'shipping_city',
        'shipping_phone',
        'shipping_zip',
        'shipping_address',
        'lang',
        'balance',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'balance' => 'decimal:2',
        'is_active' => 'integer',
        'is_enable_login' => 'integer',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bills()
    {
        return $this->hasMany(Bill::class, 'vender_id');
    }

    public function debitNotes()
    {
        return $this->hasMany(DebitNote::class, 'vender');
    }
}
