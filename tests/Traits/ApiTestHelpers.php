<?php

namespace Tests\Traits;

use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\Plan;
use Laravel\Sanctum\Sanctum;

/**
 * Trait providing helper methods for API tests.
 * This trait does NOT manage database transactions or setup.
 * Use this with RefreshDatabase or in classes that extend BaseApiTest.
 */
trait ApiTestHelpers
{
    /**
     * Create a plan for testing.
     */
    protected function createTestPlan(array $attributes = []): Plan
    {
        return Plan::factory()->create(array_merge([
            'name' => 'Test Plan',
            'code' => 'test_plan',
            'price_monthly' => 0,
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Create a role for testing.
     */
    protected function createTestRole(array $attributes = []): Role
    {
        return Role::factory()->create(array_merge([
            'name' => 'Test Role',
            'slug' => 'test_role',
            'permissions' => ['*'],
        ], $attributes));
    }

    /**
     * Create a company for testing.
     */
    protected function createTestCompany(array $attributes = [], ?Plan $plan = null): Company
    {
        $plan = $plan ?? $this->createTestPlan();
        
        return Company::factory()->create(array_merge([
            'plan_id' => $plan->id,
            'name' => 'Test Company',
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Create a user for testing.
     */
    protected function createTestUser(array $attributes = [], ?Company $company = null, ?Role $role = null): User
    {
        $company = $company ?? $this->createTestCompany();
        $role = $role ?? $this->createTestRole();
        
        return User::factory()->create(array_merge([
            'company_id' => $company->id,
            'role_id' => $role->id,
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Create an inactive user for testing.
     */
    protected function createInactiveUser(array $attributes = [], ?Company $company = null, ?Role $role = null): User
    {
        return $this->createTestUser(array_merge(['is_active' => false], $attributes), $company, $role);
    }

    /**
     * Create a user with an inactive company.
     */
    protected function createUserWithInactiveCompany(array $userAttributes = [], array $companyAttributes = []): User
    {
        $plan = $this->createTestPlan();
        $role = $this->createTestRole();
        $company = $this->createTestCompany(array_merge(['is_active' => false], $companyAttributes), $plan);
        
        return $this->createTestUser($userAttributes, $company, $role);
    }

    /**
     * Create a user from a different company.
     */
    protected function createUserFromDifferentCompany(array $userAttributes = [], array $companyAttributes = []): User
    {
        $plan = $this->createTestPlan();
        $role = $this->createTestRole();
        $company = $this->createTestCompany($companyAttributes, $plan);
        
        return $this->createTestUser($userAttributes, $company, $role);
    }

    /**
     * Authenticate as a specific user using Sanctum.
     */
    protected function actingAsUser(User $user, array $abilities = ['*']): static
    {
        Sanctum::actingAs($user, $abilities);
        return $this;
    }

    /**
     * Get default API headers.
     */
    protected function getApiHeaders(array $additional = []): array
    {
        return array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], $additional);
    }

    /**
     * Make a JSON request with API headers.
     */
    protected function apiRequest(string $method, string $uri, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->json($method, $uri, $data, $this->getApiHeaders($headers));
    }

    /**
     * Make a GET request to the API.
     */
    protected function apiGet(string $uri, array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->apiRequest('GET', $uri, [], $headers);
    }

    /**
     * Make a POST request to the API.
     */
    protected function apiPost(string $uri, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->apiRequest('POST', $uri, $data, $headers);
    }

    /**
     * Make a PUT request to the API.
     */
    protected function apiPut(string $uri, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->apiRequest('PUT', $uri, $data, $headers);
    }

    /**
     * Make a PATCH request to the API.
     */
    protected function apiPatch(string $uri, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->apiRequest('PATCH', $uri, $data, $headers);
    }

    /**
     * Make a DELETE request to the API.
     */
    protected function apiDelete(string $uri, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->apiRequest('DELETE', $uri, $data, $headers);
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

    /**
     * Assert the response requires authentication.
     */
    protected function assertRequiresAuthentication(\Illuminate\Testing\TestResponse $response): void
    {
        $response->assertStatus(401);
    }

    /**
     * Assert the response is forbidden (company access).
     */
    protected function assertForbiddenCompanyAccess(\Illuminate\Testing\TestResponse $response): void
    {
        $response->assertStatus(403);
    }

    /**
     * Assert the response has pagination structure.
     */
    protected function assertApiPagination(\Illuminate\Testing\TestResponse $response): void
    {
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
                'from',
                'to',
            ],
            'links' => [
                'first',
                'last',
                'prev',
                'next',
            ],
        ]);
    }
}
