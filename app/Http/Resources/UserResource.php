<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email' => $this->email,
            'type' => $this->type,
            'lang' => $this->lang,
            'mode' => $this->mode,
            'plan' => $this->plan,
            'is_active' => $this->is_active,
            'is_enable_login' => $this->is_enable_login,
            // Computed human-readable status
            'status' => $this->is_active
                ? 'Active'
                : ($this->is_enable_login ? 'Inactive' : 'Suspended'),
            'contact' => $this->contact ?? null,
            'tax_number' => $this->tax_number ?? null,
            'website' => $this->website ?? null,
            'address' => $this->address ?? null,
            'city' => $this->city ?? null,
            'state' => $this->state ?? null,
            'country' => $this->country ?? null,
            'zip' => $this->zip ?? null,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->pluck('name');
            }),
            'current_plan' => $this->whenLoaded('currentPlan'),
        ];
    }
}
