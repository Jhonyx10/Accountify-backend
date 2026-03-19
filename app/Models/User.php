<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'avatar',
        'lang',
        'mode',
        'created_by',
        'plan',
        'plan_expire_date',
        'requested_plan',
        'referral_code',
        'used_referral_code',
        'is_active',
        'is_enable_login',
        'is_trial_done',
        'is_plan_purchased',
        'is_register_trial',
        'interested_plan_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'plan_expire_date' => 'date',
            'password' => 'hashed',
            'is_active' => 'integer',
            'is_enable_login' => 'integer',
            'is_trial_done' => 'integer',
            'is_plan_purchased' => 'integer',
            'is_register_trial' => 'integer',
        ];
    }

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdUsers()
    {
        return $this->hasMany(User::class, 'created_by');
    }

    public function currentPlan()
    {
        return $this->belongsTo(Plan::class, 'plan');
    }

    public function requestedPlan()
    {
        return $this->belongsTo(Plan::class, 'requested_plan');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'created_by');
    }

    public function vendors()
    {
        return $this->hasMany(Vender::class, 'created_by');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'created_by');
    }

    public function bills()
    {
        return $this->hasMany(Bill::class, 'created_by');
    }

    public function products()
    {
        return $this->hasMany(ProductService::class, 'created_by');
    }

    public function chartOfAccounts()
    {
        return $this->hasMany(ChartOfAccount::class, 'created_by');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    /**
     * Get the creator ID for multi-tenancy
     * If user is company or super admin, return their own ID
     * Otherwise return the ID of the user who created them
     */
    public function creatorId()
    {
        if ($this->type == 'company' || $this->type == 'super admin') {
            return $this->id;
        } else {
            return $this->created_by;
        }
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
