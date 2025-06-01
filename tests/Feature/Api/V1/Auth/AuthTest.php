<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Domain\Auth\Models\User;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\Plan;
use App\Domain\Auth\Models\Role;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\V1\BaseApiTest;

class AuthTest extends BaseApiTest
{
    protected function setUp(): void
    {
        parent::setUp();

        // Update test user password for login tests
        $this->user->update([
            'password' => Hash::make('password123')
        ]);
    }

    #[Test]
    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->apiPost('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'password123',
            'device_name' => 'test-device',
        ]);

        $this->assertAuthenticatedResponse($response);

        $response->assertJson([
            'message' => 'Login successful',
            'data' => [
                'token_type' => 'Bearer',
            ],
        ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'name' => 'test-device',
        ]);
    }

    #[Test]
    public function test_user_cannot_login_with_invalid_password(): void
    {
        $response = $this->apiPost('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertApiError($response, 401);
        $response->assertJson([
            'message' => 'Authentication failed',
        ]);
    }

    #[Test]
    public function test_user_cannot_login_with_invalid_email(): void
    {
        $response = $this->apiPost('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $this->assertApiError($response, 401);
        $response->assertJson([
            'message' => 'Authentication failed',
        ]);
    }

    #[Test]
    public function test_inactive_user_cannot_login(): void
    {
        $inactiveUser = $this->createInactiveUser();
        $inactiveUser->update(['password' => Hash::make('password123')]);

        $response = $this->apiPost('/api/v1/auth/login', [
            'email' => $inactiveUser->email,
            'password' => 'password123',
        ]);

        $this->assertApiError($response, 401);
        $response->assertJson([
            'message' => 'Authentication failed',
        ]);
    }

    #[Test]
    public function test_user_with_inactive_company_cannot_login(): void
    {
        $userWithInactiveCompany = $this->createUserWithInactiveCompany();
        $userWithInactiveCompany->update(['password' => Hash::make('password123')]);

        $response = $this->apiPost('/api/v1/auth/login', [
            'email' => $userWithInactiveCompany->email,
            'password' => 'password123',
        ]);

        $this->assertApiError($response, 401);
        $response->assertJson([
            'message' => 'Authentication failed',
        ]);
    }

    #[Test]
    public function test_login_requires_valid_email_format(): void
    {
        $response = $this->apiPost('/api/v1/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $this->assertApiValidationError($response, ['email']);
    }

    #[Test]
    public function test_login_requires_password(): void
    {
        $response = $this->apiPost('/api/v1/auth/login', [
            'email' => $this->user->email,
        ]);

        $this->assertApiValidationError($response, ['password']);
    }

    #[Test]
    public function test_authenticated_user_can_logout(): void
    {
        $this->actingAsUser($this->user);

        $response = $this->apiPost('/api/v1/auth/logout');

        $this->assertApiSuccess($response, 200);
        $response->assertJson([
            'message' => 'Logout successful',
        ]);

        // Token should be deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->apiPost('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    #[Test]
    public function test_authenticated_user_can_get_profile(): void
    {
        $this->actingAsUser($this->user);

        $response = $this->apiGet('/api/v1/auth/me');

        $this->assertApiSuccess($response, 200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'first_name',
                'last_name',
                'email',
                'company',
                'created_at',
                'updated_at',
            ],
        ])
        ->assertJson([
            'data' => [
                'id' => $this->user->id,
                'email' => $this->user->email,
            ],
        ]);
    }

    #[Test]
    public function test_unauthenticated_user_cannot_get_profile(): void
    {
        $response = $this->apiGet('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    #[Test]
    public function test_user_can_register_successfully(): void
    {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company_name' => 'Test Company',
            'siren' => '123456789',
            'siret' => '12345678901234',
            'job_title' => 'CEO',
            'device_name' => 'test-device',
        ];

        $response = $this->apiPost('/api/v1/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'company',
                    ],
                    'token',
                    'token_type',
                    'expires_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'token_type' => 'Bearer',
                ],
            ]);

        // Check if user was created
        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        // Check if company was created
        $this->assertDatabaseHas('companies', [
            'name' => 'Test Company',
            'siren' => '123456789',
            'siret' => '12345678901234',
        ]);
    }

    #[Test]
    public function test_registration_requires_all_mandatory_fields(): void
    {
        $response = $this->apiPost('/api/v1/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'first_name',
                'last_name',
                'email',
                'password',
                'company_name',
            ]);
    }

    #[Test]
    public function test_registration_requires_unique_email(): void
    {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => $this->user->email, // Using existing user's email
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company_name' => 'Test Company',
        ];

        $response = $this->apiPost('/api/v1/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function test_registration_validates_siren_format(): void
    {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company_name' => 'Test Company',
            'siren' => '12345', // Invalid SIREN (must be 9 digits)
        ];

        $response = $this->apiPost('/api/v1/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['siren']);
    }

    #[Test]
    public function test_registration_validates_siret_format(): void
    {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company_name' => 'Test Company',
            'siret' => '12345', // Invalid SIRET (must be 14 digits)
        ];

        $response = $this->apiPost('/api/v1/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['siret']);
    }

    #[Test]
    public function test_registration_validates_siret_starts_with_siren(): void
    {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company_name' => 'Test Company',
            'siren' => '123456789',
            'siret' => '98765432101234', // SIRET doesn't start with SIREN
        ];

        $response = $this->apiPost('/api/v1/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['siret']);
    }

    #[Test]
    public function test_user_can_update_profile(): void
    {
        $this->actingAsUser($this->user);

        $updateData = [
            'first_name' => 'Updated First Name',
            'last_name' => 'Updated Last Name',
            'job_title' => 'Updated Job Title',
            'locale' => 'en',
            'timezone' => 'America/New_York',
        ];

        $response = $this->apiPut('/api/v1/auth/profile', $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'first_name' => 'Updated First Name',
                    'last_name' => 'Updated Last Name',
                    'job_title' => 'Updated Job Title',
                    'locale' => 'en',
                    'timezone' => 'America/New_York',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'first_name' => 'Updated First Name',
            'last_name' => 'Updated Last Name',
            'job_title' => 'Updated Job Title',
        ]);
    }

    #[Test]
    public function test_user_can_update_password(): void
    {
        $this->actingAsUser($this->user);

        $updateData = [
            'current_password' => 'password123',
            'new_password' => 'newpassword456',
            'new_password_confirmation' => 'newpassword456',
        ];

        $response = $this->apiPut('/api/v1/auth/password', $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password updated successfully',
            ]);

        // Verify password was updated by trying to login with new password
        $loginResponse = $this->apiPost('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'newpassword456',
        ]);

        $loginResponse->assertStatus(200);
    }

    #[Test]
    public function test_user_cannot_update_password_with_wrong_current_password(): void
    {
        $this->actingAsUser($this->user);

        $updateData = [
            'current_password' => 'wrongpassword',
            'new_password' => 'newpassword456',
            'new_password_confirmation' => 'newpassword456',
        ];

        $response = $this->apiPut('/api/v1/auth/password', $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    #[Test]
    public function test_user_can_request_password_reset(): void
    {
        $response = $this->apiPost('/api/v1/auth/forgot-password', [
            'email' => $this->user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    #[Test]
    public function test_password_reset_request_validates_email_exists(): void
    {
        $response = $this->apiPost('/api/v1/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function test_password_reset_validates_required_fields(): void
    {
        $response = $this->apiPost('/api/v1/auth/reset-password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token', 'email', 'password']);
    }
}
