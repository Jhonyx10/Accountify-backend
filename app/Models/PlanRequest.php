<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'duration',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'plan_id' => 'integer',
    ];

    /**
     * Get the user that requested the plan
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the plan that was requested
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }
}
