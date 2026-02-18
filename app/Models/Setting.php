<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'value',
        'created_by',
    ];

    protected $casts = [
        'created_by' => 'integer',
    ];

    /**
     * Get the creator (user) of the setting
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get a setting value by name for a specific user
     */
    public static function getSetting($name, $createdBy)
    {
        $setting = self::where('name', $name)
            ->where('created_by', $createdBy)
            ->first();

        return $setting ? $setting->value : null;
    }

    /**
     * Set a setting value
     */
    public static function setSetting($name, $value, $createdBy)
    {
        return self::updateOrCreate(
            ['name' => $name, 'created_by' => $createdBy],
            ['value' => $value]
        );
    }
}

