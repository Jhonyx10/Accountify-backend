<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Traits\BelongsToCompany;

class Customer extends Authenticatable
{
    use HasFactory;
    use BelongsToCompany;

    protected $fillable = [
        'customer_id',
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

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class, 'customer_id');
    }

    public function retainers()
    {
        return $this->hasMany(Retainer::class, 'customer_id');
    }

    public function creditNotes()
    {
        return $this->hasMany(CreditNote::class, 'customer');
    }
}

