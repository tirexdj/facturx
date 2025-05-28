<?php

namespace App\Actions\Api\V1\Auth;

use App\Domain\Auth\Models\User;
use Illuminate\Validation\ValidationException;

class UpdateProfileAction
{
    /**
     * Execute the profile update action.
     *
     * @param User $user
     * @param array $data
     * @return User
     * @throws ValidationException
     */
    public function execute(User $user, array $data): User
    {
        // Prepare the data for update
        $updateData = [];

        // Update basic information
        if (isset($data['first_name'])) {
            $updateData['first_name'] = $data['first_name'];
        }

        if (isset($data['last_name'])) {
            $updateData['last_name'] = $data['last_name'];
        }

        if (isset($data['job_title'])) {
            $updateData['job_title'] = $data['job_title'];
        }

        if (isset($data['locale'])) {
            $updateData['locale'] = $data['locale'];
        }

        if (isset($data['timezone'])) {
            $updateData['timezone'] = $data['timezone'];
        }

        // Handle email update (requires verification in a real app)
        if (isset($data['email']) && $data['email'] !== $user->email) {
            // Check if email is already taken
            $existingUser = User::where('email', $data['email'])
                ->where('id', '!=', $user->id)
                ->first();

            if ($existingUser) {
                throw ValidationException::withMessages([
                    'email' => ['This email address is already in use.'],
                ]);
            }

            $updateData['email'] = $data['email'];
            // In a real application, you'd set email_verified_at to null
            // and send a verification email
        }

        // Update the user
        $user->update($updateData);

        return $user->fresh(['company', 'role']);
    }
}
