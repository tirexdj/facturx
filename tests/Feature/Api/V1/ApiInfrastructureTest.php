<?php

namespace Tests\Feature\Api\V1;

class ApiInfrastructureTest extends BaseApiTest
{
    public function test_health_check_endpoint_works(): void
    {
        $response = $this->apiGet('/api/health');

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
        $response = $this->apiGet('/api/v1/non-existent-route');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Resource not found.',
                'error' => 'not_found',
            ]);
    }

    public function test_api_versioning_header_is_set(): void
    {
        $response = $this->apiGet('/api/v1/non-existent-route');

        $response->assertHeader('API-Version', 'v1');
    }

    public function test_cors_headers_are_present(): void
    {
        $response = $this->apiGet('/api/health');

        // Basic CORS headers should be present
        $response->assertHeader('Access-Control-Allow-Origin');
    }
}
