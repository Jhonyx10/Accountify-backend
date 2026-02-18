<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

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
}
