<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'period',
        'from',
        'to',
        'income_data',
        'expense_data',
        'created_by',
    ];

    protected $casts = [
        'created_by' => 'integer',
    ];

    /**
     * Budget periods
     */
    public static $period = [
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'half-yearly' => 'Half Yearly',
        'yearly' => 'Yearly',
    ];

    /**
     * Get the creator (user) of the budget
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get income data as array
     */
    public function getIncomeDataAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Set income data as JSON
     */
    public function setIncomeDataAttribute($value)
    {
        $this->attributes['income_data'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Get expense data as array
     */
    public function getExpenseDataAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Set expense data as JSON
     */
    public function setExpenseDataAttribute($value)
    {
        $this->attributes['expense_data'] = is_array($value) ? json_encode($value) : $value;
    }
}

