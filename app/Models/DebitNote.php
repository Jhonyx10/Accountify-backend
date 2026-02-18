<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebitNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill',
        'vendor',
        'amount',
        'date',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
            'bill' => 'integer',
            'vendor' => 'integer',
        ];
    }

    public function billRelation(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'bill');
    }

    public function vendorRelation(): BelongsTo
    {
        return $this->belongsTo(Vender::class, 'vendor');
    }
}
