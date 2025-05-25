<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\Plan;
use App\Domain\Auth\Models\User;
use Laravel\Sanctum\Sanctum;

abstract class BaseApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a plan
        $this->plan = Plan::factory()->create([
            'name' => 'Test Plan',
            'code' => 'test',
            'price_monthly' => 0,
            'is_active' => true,
        ]);

        // Create a company
        $this->company = Company::factory()->create([
            'plan_id' => $this->plan->id,
        ]);

        // Create a user
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
    }

    /**
     * Authenticate the test user.
     */
    protected function actingAsUser(?User $user = null): self
    {
        $user = $user ?? $this->user;
        
        Sanctum::actingAs($user, ['*']);
        
        return $this;
    }

    /**
     * Create an additional user for the same company.
     */
    protected function createUserForCompany(?Company $company = null): User
    {
        $company = $company ?? $this->company;
        
        return User::factory()->create([
            'company_id' => $company->id,
        ]);
    }

    /**
     * Create a user for a different company.
     */
    protected function createUserForDifferentCompany(): User
    {
        $plan = Plan::factory()->create();
        $company = Company::factory()->create(['plan_id' => $plan->id]);
        
        return User::factory()->create([
            'company_id' => $company->id,
        ]);
    }

    /**
     * Get API headers.
     */
    protected function getApiHeaders(array $additional = []): array
    {
        return array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], $additional);
    }

    /**
     * Make a GET request to the API.
     */
    public function getJson($uri, array $headers = []): \Illuminate\Testing\TestResponse
    {
        return parent::getJson($uri, $this->getApiHeaders($headers));
    }

    /**
     * Make a POST request to the API.
     */
    public function postJson($uri, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        return parent::postJson($uri, $data, $this->getApiHeaders($headers));
    }

    /**
     * Make a PUT request to the API.
     */
    protected function putJson($uri, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        return parent::putJson($uri, $data, $this->getApiHeaders($headers));
    }

    /**
     * Make a PATCH request to the API.
     */
    protected function patchJson($uri, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        return parent::patchJson($uri, $data, $this->getApiHeaders($headers));
    }

    /**
     * Make a DELETE request to the API.
     */
    protected function deleteJson($uri, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        return parent::deleteJson($uri, $data, $this->getApiHeaders($headers));
    }

    /**
     * Assert the response has the standard API success structure.
     */
    protected function assertApiSuccess(\Illuminate\Testing\TestResponse $response, int $status = 200): void
    {
        $response->assertStatus($status)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Assert the response has the standard API error structure.
     */
    protected function assertApiError(\Illuminate\Testing\TestResponse $response, int $status = 400): void
    {
        $response->assertStatus($status)
            ->assertJsonStructure([
                'success',
                'message',
            ])
            ->assertJson([
                'success' => false,
            ]);
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

    /**
     * Assert the response requires authentication.
     */
    protected function assertRequiresAuthentication(\Illuminate\Testing\TestResponse $response): void
    {
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
                'error' => 'authentication_required',
            ]);
    }

    /**
     * Assert the response is forbidden (company access).
     */
    protected function assertForbiddenCompanyAccess(\Illuminate\Testing\TestResponse $response): void
    {
        $response->assertStatus(403);
    }

    /**
     * Assert validation errors.
     */
    protected function assertValidationErrors(\Illuminate\Testing\TestResponse $response, array $fields = []): void
    {
        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'error',
                'errors',
            ])
            ->assertJson([
                'error' => 'validation_failed',
            ]);

        if (!empty($fields)) {
            foreach ($fields as $field) {
                $response->assertJsonValidationErrors($field);
            }
        }
    }
}
