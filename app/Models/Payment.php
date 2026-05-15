<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToCompany;

class Payment extends Model
{
    use HasFactory;
    use BelongsToCompany;

    protected $fillable = [
        'date',
        'amount',
        'account_id',
        'vender_id',
        'bill_id',
        'description',
        'category_id',
        'recurring',
        'payment_method',
        'reference',
        'add_receipt',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
            'account_id' => 'integer',
            'vender_id' => 'integer',
            'bill_id' => 'integer',
            'category_id' => 'integer',
            'payment_method' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function vender(): BelongsTo
    {
        return $this->belongsTo(Vender::class, 'vender_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'account_id');
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }
}
