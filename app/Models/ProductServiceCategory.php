<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductServiceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'chart_account_id',
        'color',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'chart_account_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_account_id');
    }
}
