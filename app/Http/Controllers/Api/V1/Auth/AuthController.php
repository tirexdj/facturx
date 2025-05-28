<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Api\V1\Auth\LoginAction;
use App\Actions\Api\V1\Auth\LogoutAction;
use App\Actions\Api\V1\Auth\RegisterAction;
use App\Actions\Api\V1\Auth\UpdatePasswordAction;
use App\Actions\Api\V1\Auth\UpdateProfileAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\UpdatePasswordRequest;
use App\Http\Requests\Api\V1\Auth\UpdateProfileRequest;
use App\Http\Resources\Api\V1\Auth\AuthResource;
use App\Http\Resources\Api\V1\Auth\UserResource;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseApiController
{
    public function __construct(
        private LoginAction $loginAction,
        private LogoutAction $logoutAction,
        private RegisterAction $registerAction,
        private UpdatePasswordAction $updatePasswordAction,
        private UpdateProfileAction $updateProfileAction
    ) {}

    /**
     * Authenticate user and return token.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->loginAction->execute($request->validated());

            Log::info('User logged in successfully', [
                'user_id' => $result['user']->id,
                'email' => $result['user']->email,
                'company_id' => $result['user']->company_id,
            ]);

            return $this->successResponse(
                new AuthResource($result),
                'Login successful',
                200
            );
        } catch (AuthenticationException $e) {
            Log::warning('Failed login attempt', [
                'email' => $request->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $this->errorResponse(
                'Authentication failed',
                ['email' => [$e->getMessage()]],
                401
            );
        } catch (\Exception $e) {
            Log::error('Login error', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'An error occurred during login',
                null,
                500
            );
        }
    }

    /**
     * Register a new user and company.
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->registerAction->execute($request->validated());

            Log::info('User registered successfully', [
                'user_id' => $result['user']->id,
                'email' => $result['user']->email,
                'company_id' => $result['company']->id,
                'company_name' => $result['company']->name,
            ]);

            return $this->successResponse(
                new AuthResource($result),
                'Registration successful',
                201
            );
        } catch (ValidationException $e) {
            return $this->errorResponse(
                'Validation failed',
                $e->errors(),
                422
            );
        } catch (\Exception $e) {
            Log::error('Registration error', [
                'email' => $request->email,
                'company_name' => $request->company_name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'An error occurred during registration',
                null,
                500
            );
        }
    }

    /**
     * Logout user and revoke token(s).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $currentTokenId = $request->user()->currentAccessToken()?->id;
            
            $this->logoutAction->execute($user, $currentTokenId);

            Log::info('User logged out successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return $this->successResponse(
                null,
                'Logout successful',
                200
            );
        } catch (\Exception $e) {
            Log::error('Logout error', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'An error occurred during logout',
                null,
                500
            );
        }
    }

    /**
     * Get current authenticated user information.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load(['company.plan', 'role']);

            return $this->successResponse(
                new UserResource($user),
                'User information retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Me endpoint error', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'An error occurred while retrieving user information',
                null,
                500
            );
        }
    }

    /**
     * Update user profile information.
     *
     * @param UpdateProfileRequest $request
     * @return JsonResponse
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = $this->updateProfileAction->execute(
                $request->user(),
                $request->validated()
            );

            Log::info('User profile updated successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'updated_fields' => array_keys($request->validated()),
            ]);

            return $this->successResponse(
                new UserResource($user),
                'Profile updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->errorResponse(
                'Validation failed',
                $e->errors(),
                422
            );
        } catch (\Exception $e) {
            Log::error('Profile update error', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'An error occurred while updating profile',
                null,
                500
            );
        }
    }

    /**
     * Update user password.
     *
     * @param UpdatePasswordRequest $request
     * @return JsonResponse
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        try {
            $user = $this->updatePasswordAction->execute(
                $request->user(),
                $request->validated()
            );

            Log::info('User password updated successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'revoked_other_tokens' => $request->boolean('revoke_other_tokens'),
            ]);

            return $this->successResponse(
                null,
                'Password updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->errorResponse(
                'Validation failed',
                $e->errors(),
                422
            );
        } catch (\Exception $e) {
            Log::error('Password update error', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'An error occurred while updating password',
                null,
                500
            );
        }
    }

    /**
     * Send password reset link (placeholder).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        // TODO: Implement password reset functionality
        // This would typically involve:
        // 1. Validating the email
        // 2. Generating a reset token
        // 3. Sending an email with the reset link
        
        $request->validate([
            'email' => ['required', 'string', 'email', 'exists:users,email'],
        ], [
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'L\'adresse e-mail doit être valide.',
            'email.exists' => 'Aucun compte n\'est associé à cette adresse e-mail.',
        ]);

        Log::info('Password reset requested', [
            'email' => $request->email,
            'ip' => $request->ip(),
        ]);

        return $this->successResponse(
            null,
            'If an account with that email exists, we have sent a password reset link.'
        );
    }

    /**
     * Reset password using token (placeholder).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        // TODO: Implement password reset functionality
        // This would typically involve:
        // 1. Validating the token
        // 2. Validating the new password
        // 3. Updating the user's password
        // 4. Invalidating the reset token
        
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ], [
            'token.required' => 'Le token de réinitialisation est obligatoire.',
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'L\'adresse e-mail doit être valide.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ]);

        Log::info('Password reset attempted', [
            'email' => $request->email,
            'ip' => $request->ip(),
        ]);

        return $this->successResponse(
            null,
            'Password has been reset successfully.'
        );
    }
}
