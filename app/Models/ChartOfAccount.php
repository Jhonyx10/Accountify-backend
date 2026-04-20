<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'sub_type',
        'parent',
        'is_enabled',
        'description',
        'created_by',
    ];

    protected $casts = [
        'code' => 'integer',
        'type' => 'integer',
        'sub_type' => 'integer',
        'parent' => 'integer',
        'is_enabled' => 'integer',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function accountType()
    {
        return $this->belongsTo(ChartOfAccountType::class, 'type');
    }

    public function accountSubType()
    {
        return $this->belongsTo(ChartOfAccountSubType::class, 'sub_type');
    }

    public function parentAccount()
    {
        return $this->belongsTo(ChartOfAccountParent::class, 'parent');
    }

    public function journalItems()
    {
        return $this->hasMany(JournalItem::class, 'account');
    }

    public function getBalanceAttribute()
    {
        $items = clone $this->journalItems;
        $debit = $items->sum(function ($item) { return (float) $item->debit; });
        $credit = $items->sum(function ($item) { return (float) $item->credit; });

        $typeName = $this->accountType?->name ?? '';

        if (in_array($typeName, ['Assets', 'Expenses', 'Costs of Goods Sold'])) {
            return $debit - $credit;
        } elseif (in_array($typeName, ['Liabilities', 'Equity', 'Income'])) {
            return $credit - $debit;
        }

        return $debit - $credit; // Default fallback
    }
}
