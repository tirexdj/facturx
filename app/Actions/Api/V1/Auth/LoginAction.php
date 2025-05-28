<?php

namespace App\Actions\Api\V1\Auth;

use App\Domain\Auth\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginAction
{
    /**
     * Execute the login action.
     *
     * @param array $data
     * @return array
     * @throws AuthenticationException
     */
    public function execute(array $data): array
    {
        // Extract credentials
        $credentials = [
            'email' => $data['email'],
            'password' => $data['password'],
        ];

        // Find the user by email
        $user = User::where('email', $credentials['email'])->first();

        // Verify user exists and password is correct
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw new AuthenticationException('The provided credentials are incorrect.');
        }

        // Check if user is active
        if (!$user->is_active) {
            throw new AuthenticationException('Your account has been deactivated.');
        }

        // Check if company is active (if user belongs to a company)
        if ($user->company && !$user->company->is_active) {
            throw new AuthenticationException('Your company account has been deactivated.');
        }

        // Update last login timestamp
        $user->update(['last_login_at' => now()]);

        // Create API token
        $tokenName = $data['device_name'] ?? 'api-token';
        $token = $user->createToken($tokenName);

        // Prepare response data
        return [
            'user' => $user->load(['company', 'role']),
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => config('sanctum.expiration') ? 
                now()->addMinutes(config('sanctum.expiration'))->toISOString() : null,
        ];
    }
}
