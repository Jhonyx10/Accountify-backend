<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proposal extends Model
{
    use HasFactory;

    // Status constants
    const STATUS_DRAFT    = 0;
    const STATUS_SENT     = 1;
    const STATUS_ACCEPTED = 2;
    const STATUS_DECLINED = 3;

    const STATUS_MAP = [
        self::STATUS_DRAFT    => 'Draft',
        self::STATUS_SENT     => 'Sent',
        self::STATUS_ACCEPTED => 'Accepted',
        self::STATUS_DECLINED => 'Declined',
    ];

    protected $fillable = [
        'proposal_id',
        'customer_id',
        'issue_date',
        'send_date',
        'due_date',
        'category_id',
        'status',
        'discount_apply',
        'notes',
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
            'due_date' => 'date',
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

    /**
     * Get the human-readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_MAP[$this->status] ?? 'Unknown';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductServiceCategory::class, 'category_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(ProposalProduct::class, 'proposal_id');
    }

    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_invoice_id');
    }
}
