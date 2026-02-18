<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposal_id',
        'customer_id',
        'issue_date',
        'send_date',
        'category_id',
        'status',
        'discount_apply',
        'is_convert',
        'converted_invoice_id',
        'converted_retainer_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'send_date' => 'date',
            'proposal_id' => 'integer',
            'customer_id' => 'integer',
            'category_id' => 'integer',
            'status' => 'integer',
            'discount_apply' => 'integer',
            'is_convert' => 'integer',
            'converted_invoice_id' => 'integer',
            'converted_retainer_id' => 'integer',
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
        return $this->hasMany(ProposalProduct::class, 'proposal_id');
    }
}
