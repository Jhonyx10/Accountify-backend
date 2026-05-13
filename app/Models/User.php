<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Plan;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Vendor; // Check spelling (Vender vs Vendor)
use App\Models\Invoice;
use App\Models\Bill;
use App\Models\ProductService;
use App\Models\ChartOfAccount;
use App\Models\Order;

use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $guard_name = 'web';

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
        'tax_number',
        'contact',
        'website',
        'address',
        'city',
        'state',
        'country',
        'zip',
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
        // Access the raw attributes directly to avoid triggering logic
        $type = $this->getRawOriginal('type') ?? $this->type;
        
        if ($type === 'company' || $type === 'super admin') {
            return $this->id;
        }
        
        return $this->created_by;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Inside your User.php model
    /**
 * Override roles relationship to bypass global scopes
 */
    public function roles(): BelongsToMany
    {
        return $this->morphToMany(
            config('permission.models.role'),
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key'),
            'role_id'
        )->withoutGlobalScopes();
    }

    /**
     * Override permissions relationship to bypass global scopes
     */
    public function permissions(): BelongsToMany
    {
        return $this->morphToMany(
            config('permission.models.permission'),
            'model',
            config('permission.table_names.model_has_permissions'),
            config('permission.column_names.model_morph_key'),
            'permission_id'
        )->withoutGlobalScopes();
    }

}
