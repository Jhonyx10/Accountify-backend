<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;

class Category extends Model
{
    use HasFactory;
    use BelongsToCompany;

    protected $fillable = [
        'name',
        'type',
        'description',
        'color',
        'created_by',
    ];

    protected $casts = [
        'created_by' => 'integer',
    ];

    public function products()
    {
        return $this->hasMany(ProductService::class);
    }
}
