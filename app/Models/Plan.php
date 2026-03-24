<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'duration',
        'max_users',
        'max_customers',
        'max_venders',
        'storage_limit',
        'description',
        'image',
        'enable_chatgpt',
        'trial',
        'trial_days',
        'is_disable',
        'max_invoices',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'max_users' => 'integer',
        'max_customers' => 'integer',
        'max_venders' => 'integer',
        'storage_limit' => 'float',
        'trial' => 'integer',
        'is_disable' => 'integer',
        'max_invoices' => 'integer',
    ];

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class, 'plan');
    }

    public function requestedByUsers()
    {
        return $this->hasMany(User::class, 'requested_plan');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'plan_id');
    }

    public function planRequests()
    {
        return $this->hasMany(PlanRequest::class, 'plan_id');
    }
}
