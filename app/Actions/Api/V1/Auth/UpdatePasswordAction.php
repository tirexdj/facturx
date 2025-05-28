<?php

namespace App\Actions\Api\V1\Auth;

use App\Domain\Auth\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UpdatePasswordAction
{
    /**
     * Execute the password update action.
     *
     * @param User $user
     * @param array $data
     * @return User
     * @throws ValidationException
     */
    public function execute(User $user, array $data): User
    {
        // Verify current password
        if (!Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided current password is incorrect.'],
            ]);
        }

        // Update the password
        $user->update([
            'password' => Hash::make($data['new_password']),
        ]);

        // Optionally, revoke all existing tokens except the current one
        if (isset($data['revoke_other_tokens']) && $data['revoke_other_tokens']) {
            $currentTokenId = $user->currentAccessToken()?->id;
            $user->tokens()->where('id', '!=', $currentTokenId)->delete();
        }

        return $user->fresh();
    }
}
