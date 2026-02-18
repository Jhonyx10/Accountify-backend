<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartOfAccountType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'created_by',
    ];

    protected $casts = [
        'created_by' => 'integer',
    ];

    /**
     * Get the creator (user) of the chart of account type
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the chart of accounts associated with this type
     */
    public function chartOfAccounts()
    {
        return $this->hasMany(ChartOfAccount::class, 'type');
    }
}
