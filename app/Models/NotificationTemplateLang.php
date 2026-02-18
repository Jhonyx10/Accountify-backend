<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplateLang extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'lang',
        'content',
        'variables',
        'created_by',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'created_by' => 'integer',
    ];

    /**
     * Get the notification template that owns this language version
     */
    public function template()
    {
        return $this->belongsTo(NotificationTemplate::class, 'parent_id');
    }

    /**
     * Get the creator (user) of the notification template language
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

