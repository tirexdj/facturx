<?php

namespace App\Actions\Api\V1\Auth;

use App\Domain\Auth\Models\User;
use App\Domain\Company\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RegisterAction
{
    /**
     * Execute the registration action.
     *
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function execute(array $data): array
    {
        try {
            DB::beginTransaction();

            // Create the company first
            $company = Company::create([
                'name' => $data['company_name'],
                'siren' => $data['siren'] ?? null,
                'siret' => $data['siret'] ?? null,
                'plan_id' => $this->getDefaultPlanId(),
                'is_active' => true,
                'trial_ends_at' => now()->addDays(30), // 30-day trial
            ]);

            // Create the user
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'company_id' => $company->id,
                'job_title' => $data['job_title'] ?? null,
                'locale' => $data['locale'] ?? 'fr',
                'timezone' => $data['timezone'] ?? 'Europe/Paris',
                'is_active' => true,
                'email_verified_at' => now(), // Auto-verify for now
            ]);

            // Assign default role (usually admin for the first user)
            $defaultRole = $this->getDefaultRoleId();
            if ($defaultRole) {
                $user->update(['role_id' => $defaultRole]);
            }

            // Create API token
            $tokenName = $data['device_name'] ?? 'api-token';
            $token = $user->createToken($tokenName);

            DB::commit();

            // Load relationships for response
            $user->load(['company', 'role']);

            return [
                'user' => $user,
                'company' => $company,
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => config('sanctum.expiration') ? 
                    now()->addMinutes(config('sanctum.expiration'))->toISOString() : null,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get the default plan ID (Free plan).
     *
     * @return string|null
     */
    private function getDefaultPlanId(): ?string
    {
        // Get the free plan by code
        $freePlan = \App\Domain\Company\Models\Plan::where('code', 'free')->first();
        return $freePlan?->id;
    }

    /**
     * Get the default role ID for a new user.
     *
     * @return string|null
     */
    private function getDefaultRoleId(): ?string
    {
        // Get the admin role by name for the first user
        $adminRole = \App\Domain\Auth\Models\Role::where('name', 'Administrateur')->first();
        return $adminRole?->id;
    }
}
