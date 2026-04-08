<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

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


}
