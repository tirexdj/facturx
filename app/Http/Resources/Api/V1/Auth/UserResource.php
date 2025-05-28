<?php

namespace App\Http\Resources\Api\V1\Auth;

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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'job_title' => $this->job_title,
            'locale' => $this->locale,
            'timezone' => $this->timezone,
            'is_active' => $this->is_active,
            'last_login_at' => $this->last_login_at?->toISOString(),
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'two_factor_enabled' => $this->two_factor_enabled,
            'profile_picture_url' => $this->getFirstMediaUrl('profile_picture'),
            
            // Company information
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'name' => $this->company->name,
                    'siren' => $this->company->siren,
                    'siret' => $this->company->siret,
                    'is_active' => $this->company->is_active,
                    'plan' => $this->company->plan ? [
                        'id' => $this->company->plan->id,
                        'name' => $this->company->plan->name,
                        'slug' => $this->company->plan->slug,
                    ] : null,
                    'trial_ends_at' => $this->company->trial_ends_at?->toISOString(),
                ];
            }),

            // Role information
            'role' => $this->whenLoaded('role', function () {
                return $this->role ? [
                    'id' => $this->role->id,
                    'name' => $this->role->name,
                    // TODO: Implement permissions relationship
                    // 'permissions' => $this->role->permissions ?? [],
                ] : null;
            }),

            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
