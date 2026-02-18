<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Retainer extends Model
{
    use HasFactory;

    protected $fillable = [
        'retainer_id',
        'customer_id',
        'issue_date',
        'due_date',
        'send_date',
        'category_id',
        'status',
        'discount_apply',
        'converted_invoice_id',
        'is_convert',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'send_date' => 'date',
            'retainer_id' => 'integer',
            'customer_id' => 'integer',
            'category_id' => 'integer',
            'status' => 'integer',
            'discount_apply' => 'integer',
            'converted_invoice_id' => 'integer',
            'is_convert' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(RetainerProduct::class, 'retainer_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RetainerPayment::class, 'retainer_id');
    }
}
