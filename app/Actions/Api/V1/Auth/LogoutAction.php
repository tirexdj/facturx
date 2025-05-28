<?php

namespace App\Actions\Api\V1\Auth;

use App\Domain\Auth\Models\User;
use Illuminate\Support\Facades\DB;

class LogoutAction
{
    /**
     * Execute the logout action.
     *
     * @param User $user
     * @param string|null $currentToken
     * @return bool
     */
    public function execute(User $user, ?string $currentToken = null): bool
    {
        try {
            DB::beginTransaction();

            if ($currentToken) {
                // Delete only the current token
                $user->tokens()->where('id', $currentToken)->delete();
            } else {
                // Delete all tokens for the user
                $user->tokens()->delete();
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
