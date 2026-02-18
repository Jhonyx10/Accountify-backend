<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Get the notification template languages associated with this template
     */
    public function languages()
    {
        return $this->hasMany(NotificationTemplateLang::class, 'parent_id');
    }
}

