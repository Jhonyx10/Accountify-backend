<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToCompany;

class JournalEntry extends Model
{
    use HasFactory;
    use BelongsToCompany;

    protected $fillable = [
        'date',
        'reference',
        'description',
        'journal_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'journal_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(JournalItem::class, 'journal');
    }

    public function getAmountAttribute()
    {
        return $this->items->sum('debit') ?? 0;
    }

    public function getFormattedJournalIdAttribute()
    {
        return 'JE-' . str_pad($this->journal_id, 4, '0', STR_PAD_LEFT);
    }
}
