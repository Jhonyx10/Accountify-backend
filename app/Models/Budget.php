<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use HasFactory, SoftDeletes;

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
     * Get all category IDs from income_data and expense_data
     */
    public function getAllCategoryIds()
    {
        $ids = [];

        $income = $this->income_data;
        if (is_array($income)) {
            $ids = array_merge($ids, array_keys($income));
        }

        $expense = $this->expense_data;
        if (is_array($expense)) {
            $ids = array_merge($ids, array_keys($expense));
        }

        // Filter out non-numeric keys and convert to int
        return array_unique(array_map('intval', array_filter($ids, 'is_numeric')));
    }

    /**
     * Set income data as JSON object
     */
    public function setIncomeDataAttribute($value)
    {
        $this->attributes['income_data'] = is_array($value) ? json_encode($value, JSON_FORCE_OBJECT) : $value;
    }

    /**
     * Get income data as array
     */
    public function getIncomeDataAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Set expense data as JSON object
     */
    public function setExpenseDataAttribute($value)
    {
        $this->attributes['expense_data'] = is_array($value) ? json_encode($value, JSON_FORCE_OBJECT) : $value;
    }

    /**
     * Get expense data as array
     */
    public function getExpenseDataAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}

