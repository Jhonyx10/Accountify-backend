<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'duration' => $this->duration,
            'max_users' => $this->max_users,
            'max_customers' => $this->max_customers,
            'max_venders' => $this->max_venders,
            'storage_limit' => $this->storage_limit,
            'description' => $this->description,
            'image' => $this->image,
            'enable_chatgpt' => $this->enable_chatgpt,
            'trial' => $this->trial,
            'trial_days' => $this->trial_days,
            'is_disable' => $this->is_disable,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'users_count' => $this->whenCounted('users'),
        ];
    }
}
