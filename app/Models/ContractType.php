<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Traits\BelongsToCompany;

class ContractType extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'name',
        'created_by',
    ];

    protected $casts = [
        'created_by' => 'integer',
    ];

    /**
     * Get the creator (user) of the contract type
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the contracts associated with this type
     */
    public function contracts()
    {
        return $this->hasMany(Contract::class, 'type');
    }
}

