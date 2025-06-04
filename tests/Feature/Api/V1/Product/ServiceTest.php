<?php

namespace Tests\Feature\Api\V1\Product;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domain\Company\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\Product\Models\Service;
use App\Domain\Product\Models\Category;
use Laravel\Sanctum\Sanctum;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->for($this->company)->create();
        $this->category = Category::factory()->for($this->company)->create([
            'type' => 'service'
        ]);
        
        Sanctum::actingAs($this->user);
    }

    public function test_can_list_services(): void
    {
        Service::factory()->count(3)->for($this->company)->create();

        $response = $this->getJson('/api/v1/services');

        $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'unit_price',
                            'vat_rate',
                            'unit',
                            'is_active',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'meta',
                    'links'
                ]);
    }

    public function test_can_create_service(): void
    {
        $serviceData = [
            'name' => 'Web Development',
            'description' => 'Custom web development service',
            'category_id' => $this->category->id,
            'unit_price' => 85.00,
            'cost_price' => 45.00,
            'vat_rate' => 20,
            'unit' => 'hour',
            'duration' => 8,
            'is_recurring' => true,
            'recurring_period' => 'month',
            'setup_fee' => 200.00,
            'options' => [
                ['name' => 'Rush delivery', 'price' => 50, 'vat_rate' => 20],
                ['name' => 'Extra revisions', 'price' => 25, 'vat_rate' => 20]
            ]
        ];

        $response = $this->postJson('/api/v1/services', $serviceData);

        $response->assertCreated()
                ->assertJsonStructure([
                    'id',
                    'name',
                    'description',
                    'unit_price',
                    'cost_price',
                    'vat_rate',
                    'unit',
                    'duration',
                    'is_recurring',
                    'recurring_period',
                    'setup_fee',
                    'options',
                    'margin',
                    'margin_percentage',
                    'total_price_with_setup',
                    'category',
                    'created_at',
                    'updated_at'
                ])
                ->assertJson([
                    'name' => 'Web Development',
                    'unit_price' => 85.00,
                    'unit' => 'hour',
                    'is_recurring' => true,
                    'recurring_period' => 'month',
                    'margin' => 40.00,
                    'total_price_with_setup' => 285.00
                ]);

        $this->assertDatabaseHas('services', [
            'company_id' => $this->company->id,
            'name' => 'Web Development',
            'unit_price' => 85.00,
            'unit' => 'hour'
        ]);
    }

    public function test_can_show_service(): void
    {
        $service = Service::factory()->for($this->company)->for($this->category)->create();

        $response = $this->getJson("/api/v1/services/{$service->id}");

        $response->assertOk()
                ->assertJsonStructure([
                    'id',
                    'name',
                    'description',
                    'unit_price',
                    'vat_rate',
                    'unit',
                    'category',
                    'created_at',
                    'updated_at'
                ])
                ->assertJson([
                    'id' => $service->id,
                    'name' => $service->name
                ]);
    }

    public function test_can_update_service(): void
    {
        $service = Service::factory()->for($this->company)->create();
        
        $updateData = [
            'name' => 'Updated Service Name',
            'unit_price' => 95.00,
            'unit' => 'day'
        ];

        $response = $this->putJson("/api/v1/services/{$service->id}", $updateData);

        $response->assertOk()
                ->assertJson([
                    'id' => $service->id,
                    'name' => 'Updated Service Name',
                    'unit_price' => 95.00,
                    'unit' => 'day'
                ]);

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Updated Service Name',
            'unit_price' => 95.00
        ]);
    }

    public function test_can_delete_service(): void
    {
        $service = Service::factory()->for($this->company)->create();

        $response = $this->deleteJson("/api/v1/services/{$service->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_can_filter_services_by_unit(): void
    {
        Service::factory()->for($this->company)->create(['unit' => 'hour']);
        Service::factory()->for($this->company)->create(['unit' => 'day']);

        $response = $this->getJson('/api/v1/services?filter[unit]=hour');

        $response->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.unit', 'hour');
    }

    public function test_can_search_services(): void
    {
        Service::factory()->for($this->company)->create([
            'name' => 'Web Development',
            'description' => 'Custom website creation'
        ]);
        Service::factory()->for($this->company)->create([
            'name' => 'Mobile App',
            'description' => 'Android and iOS development'
        ]);

        $response = $this->getJson('/api/v1/services?search=website');

        $response->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.name', 'Web Development');
    }

    public function test_unauthorized_access_returns_401(): void
    {
        Sanctum::actingAs(null);

        $response = $this->getJson('/api/v1/services');

        $response->assertUnauthorized();
    }

    public function test_forbidden_access_returns_403(): void
    {
        $otherCompany = Company::factory()->create();
        $service = Service::factory()->for($otherCompany)->create();

        $response = $this->getJson("/api/v1/services/{$service->id}");

        $response->assertForbidden();
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/v1/services', []);

        $response->assertUnprocessable()
                ->assertJsonValidationErrors(['name', 'unit_price', 'vat_rate', 'unit']);
    }

    public function test_invalid_unit_validation(): void
    {
        $response = $this->postJson('/api/v1/services', [
            'name' => 'Test Service',
            'unit_price' => 50.00,
            'vat_rate' => 20,
            'unit' => 'invalid_unit'
        ]);

        $response->assertUnprocessable()
                ->assertJsonValidationErrors(['unit']);
    }

    public function test_recurring_service_validation(): void
    {
        $response = $this->postJson('/api/v1/services', [
            'name' => 'Test Service',
            'unit_price' => 50.00,
            'vat_rate' => 20,
            'unit' => 'month',
            'is_recurring' => true
            // Missing recurring_period
        ]);

        $response->assertUnprocessable()
                ->assertJsonValidationErrors(['recurring_period']);
    }

    public function test_margin_calculations(): void
    {
        $serviceData = [
            'name' => 'Consulting',
            'unit_price' => 150,
            'cost_price' => 90,
            'vat_rate' => 20,
            'unit' => 'hour'
        ];

        $response = $this->postJson('/api/v1/services', $serviceData);

        $response->assertCreated()
                ->assertJson([
                    'margin' => 60,
                    'margin_percentage' => 66.67
                ]);
    }

    public function test_total_price_with_setup_calculation(): void
    {
        $serviceData = [
            'name' => 'Website Setup',
            'unit_price' => 100,
            'vat_rate' => 20,
            'unit' => 'fixed',
            'setup_fee' => 250
        ];

        $response = $this->postJson('/api/v1/services', $serviceData);

        $response->assertCreated()
                ->assertJson([
                    'total_price_with_setup' => 350
                ]);
    }

    public function test_can_include_category_relation(): void
    {
        $service = Service::factory()->for($this->company)->for($this->category)->create();

        $response = $this->getJson("/api/v1/services/{$service->id}?include=category");

        $response->assertOk()
                ->assertJsonStructure([
                    'category' => [
                        'id',
                        'name',
                        'type'
                    ]
                ]);
    }
}
