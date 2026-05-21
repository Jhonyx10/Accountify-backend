<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Traits\BelongsToCompany;
use Illuminate\Notifications\Notifiable;

use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use BelongsToCompany;
    use Notifiable;
    

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->referral_code)) {
                $customer->referral_code = strtoupper(Str::random(8));
            }
        });
    }

    public function getReferralLinkAttribute()
    {
        return 'http://localhost:5173/register?ref=' . $this->referral_code;
    }
    
    protected $appends = ['referral_link'];

    protected $fillable = [
        'company_id',
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
        'referral_code',
        'used_referral_code',
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

    /**
     * Get the creator ID for multi-tenancy
     */
    public function creatorId()
    {
        return $this->created_by;
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

