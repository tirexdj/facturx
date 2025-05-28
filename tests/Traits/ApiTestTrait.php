<?php

namespace Tests\Traits;

use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\Plan;
use Laravel\Sanctum\Sanctum;

trait ApiTestTrait
{
    protected Company $testCompany;
    protected User $testUser;
    protected Plan $testPlan;
    protected Role $testRole;

    /**
     * Set up basic test data.
     */
    protected function setUpApiTest(): void
    {
        // Create a test plan
        // $this->testPlan = Plan::factory()->free()->create();
        $this->testPlan = Plan::where('code', 'free')->first() ?? Plan::factory()->free()->create();

        // Create a test role
        $this->testRole = Role::factory()->admin()->create();

        // Create a test company
        $this->testCompany = Company::factory()->create([
            'plan_id' => $this->testPlan->id,
        ]);

        // Create a test user
        $this->testUser = User::factory()->create([
            'company_id' => $this->testCompany->id,
            'role_id' => $this->testRole->id,
            'is_active' => true,
        ]);
    }

    /**
     * Create an authenticated user for testing.
     */
    protected function createAuthenticatedUser(array $userAttributes = [], array $companyAttributes = []): User
    {
        $plan = Plan::where('code', 'free')->first() ?? Plan::factory()->free()->create();
        $role = Role::factory()->admin()->create();

        $company = Company::factory()->create(array_merge([
            'plan_id' => $plan->id,
        ], $companyAttributes));

        $user = User::factory()->create(array_merge([
            'company_id' => $company->id,
            'role_id' => $role->id,
            'is_active' => true,
        ], $userAttributes));

        return $user;
    }

    /**
     * Authenticate as a specific user using Sanctum.
     */
    protected function actingAsUser(User $user): self
    {
        Sanctum::actingAs($user);
        return $this;
    }

    /**
     * Create and authenticate as a new user.
     */
    protected function createAndActAsUser(array $userAttributes = [], array $companyAttributes = []): User
    {
        $user = $this->createAuthenticatedUser($userAttributes, $companyAttributes);
        $this->actingAsUser($user);
        return $user;
    }

    /**
     * Create an inactive user for testing.
     */
    protected function createInactiveUser(): User
    {
        return $this->createAuthenticatedUser(['is_active' => false]);
    }

    /**
     * Create a user with an inactive company.
     */
    protected function createUserWithInactiveCompany(): User
    {
        return $this->createAuthenticatedUser([], ['is_active' => false]);
    }

    /**
     * Get default API headers.
     */
    protected function getApiHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Make a JSON request with API headers.
     */
    protected function apiRequest(string $method, string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->json($method, $uri, $data, $this->getApiHeaders());
    }

    /**
     * Make a GET request to the API.
     */
    protected function apiGet(string $uri): \Illuminate\Testing\TestResponse
    {
        return $this->apiRequest('GET', $uri);
    }

    /**
     * Make a POST request to the API.
     */
    protected function apiPost(string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->apiRequest('POST', $uri, $data);
    }

    /**
     * Make a PUT request to the API.
     */
    protected function apiPut(string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->apiRequest('PUT', $uri, $data);
    }

    /**
     * Make a PATCH request to the API.
     */
    protected function apiPatch(string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->apiRequest('PATCH', $uri, $data);
    }

    /**
     * Make a DELETE request to the API.
     */
    protected function apiDelete(string $uri): \Illuminate\Testing\TestResponse
    {
        return $this->apiRequest('DELETE', $uri);
    }

    /**
     * Assert API response structure for success.
     */
    protected function assertApiSuccess(\Illuminate\Testing\TestResponse $response, int $statusCode = 200): void
    {
        $response->assertStatus($statusCode)
            ->assertJsonStructure([
                'success',
                'message',
            ])
            ->assertJson(['success' => true]);
    }

    /**
     * Assert API response structure for error.
     */
    protected function assertApiError(\Illuminate\Testing\TestResponse $response, int $statusCode = 400): void
    {
        $response->assertStatus($statusCode)
            ->assertJsonStructure([
                'success',
                'message',
            ])
            ->assertJson(['success' => false]);
    }

    /**
     * Assert API validation error response.
     */
    protected function assertApiValidationError(\Illuminate\Testing\TestResponse $response, array $fields = []): void
    {
        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors',
            ]);

        if (!empty($fields)) {
            $response->assertJsonValidationErrors($fields);
        }
    }

    /**
     * Assert authenticated response structure.
     */
    protected function assertAuthenticatedResponse(\Illuminate\Testing\TestResponse $response): void
    {
        $this->assertApiSuccess($response, 200);

        $response->assertJsonStructure([
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
        ]);
    }
}
