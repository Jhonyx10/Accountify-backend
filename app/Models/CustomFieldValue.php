<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomFieldValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'record_id',
        'field_id',
        'value',
    ];

    public function field(): BelongsTo
    {
        return $this->belongsTo(CustomField::class, 'field_id');
    }
}
