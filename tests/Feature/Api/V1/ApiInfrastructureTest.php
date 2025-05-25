<?php

namespace Tests\Feature\Api\V1;

class ApiInfrastructureTest extends BaseApiTest
{
    public function test_health_check_endpoint_works(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'version',
            ])
            ->assertJson([
                'status' => 'ok',
            ]);
    }

    public function test_api_returns_json_for_invalid_routes(): void
    {
        $response = $this->getJson('/api/v1/non-existent-route');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Resource not found.',
                'error' => 'not_found',
            ]);
    }

    public function test_api_requires_authentication_for_protected_routes(): void
    {
        $response = $this->getJson('/api/v1/company');

        $this->assertRequiresAuthentication($response);
    }

    public function test_api_versioning_header_is_set(): void
    {
        $response = $this->getJson('/api/v1/non-existent-route');

        $response->assertHeader('API-Version', 'v1');
    }

    public function test_api_accepts_json_content_type(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        // Should not fail due to content type issues
        $response->assertStatus(422); // Validation error, not content type error
    }

    public function test_api_returns_validation_errors_in_correct_format(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'error',
                'errors',
            ])
            ->assertJson([
                'error' => 'validation_failed',
            ]);
    }

    public function test_cors_headers_are_present(): void
    {
        $response = $this->getJson('/api/health');

        // Basic CORS headers should be present
        $response->assertHeader('Access-Control-Allow-Origin');
    }
}
