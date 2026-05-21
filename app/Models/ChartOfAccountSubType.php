<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccountSubType extends Model
{
    protected $fillable = [
        'name',
        'type',
        'created_by',
    ];

    protected $casts = [
        'type' => 'integer',
        'created_by' => 'integer',
    ];

    public function typeRelation()
    {
        return $this->belongsTo(ChartOfAccountType::class, 'type');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function chartOfAccounts()
    {
        return $this->hasMany(ChartOfAccount::class, 'sub_type');
    }
}
