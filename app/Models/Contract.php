<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer',
        'subject',
        'value',
        'type',
        'start_date',
        'end_date',
        'edit_status',
        'description',
        'notes',
        'customer_signature',
        'company_signature',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'value' => 'decimal:2',
            'customer' => 'integer',
            'type' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customerRelation(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer');
    }
}

