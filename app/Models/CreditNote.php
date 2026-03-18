<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToCompany;

class CreditNote extends Model
{
    use HasFactory;
    use BelongsToCompany;

    protected $fillable = [
        'invoice',
        'customer',
        'amount',
        'date',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
            'invoice' => 'integer',
            'customer' => 'integer',
        ];
    }

    public function invoiceRelation(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice');
    }

    public function customerRelation(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer');
    }
}
