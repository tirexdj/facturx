<?php

namespace Tests\Feature\Api\V1\Company;

use Tests\TestCase;
use Tests\Traits\TestHelpers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\Plan;
use App\Domain\Auth\Models\User;
use App\Domain\Analytics\Models\Feature;
use Laravel\Sanctum\Sanctum;

class CompanyTest extends TestCase
{
    use RefreshDatabase, TestHelpers;

    protected User $user;
    protected Company $company;
    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer un utilisateur avec la feature "manage_companies"
        $this->user = $this->createUserWithFeatures(['manage_companies']);
        $this->company = $this->user->company;
        $this->plan = $this->company->plan;
    }

    public function test_can_list_companies(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/companies');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'legal_name',
                            'siren',
                            'siret',
                            'plan',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'meta' => [
                        'total',
                        'count',
                        'per_page',
                        'current_page',
                        'total_pages'
                    ]
                ],
                'success',
                'message'
            ]);
    }

    public function test_can_create_company(): void
    {
        Sanctum::actingAs($this->user);

        $companyData = [
            'name' => 'Test Company',
            'legal_name' => 'Test Company SARL',
            'siren' => '123456789',
            'siret' => '12345678901234',
            'vat_number' => 'FR12345678901',
            'plan_id' => $this->plan->id,
            'address' => [
                'line_1' => '123 Test Street',
                'city' => 'Test City',
                'postal_code' => '75001',
                'country_code' => 'FR'
            ],
            'email' => [
                'email' => 'test@company.com',
                'type' => 'main'
            ],
            'phone' => [
                'number' => '+33123456789',
                'type' => 'main'
            ]
        ];

        $response = $this->postJson('/api/v1/companies', $companyData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'legal_name',
                    'siren',
                    'siret',
                    'vat_number',
                    'plan',
                    'addresses',
                    'emails',
                    'phone_numbers'
                ],
                'success',
                'message'
            ])
            ->assertJson([
                'data' => [
                    'name' => 'Test Company',
                    'legal_name' => 'Test Company SARL',
                    'siren' => '123456789',
                    'siret' => '12345678901234',
                    'vat_number' => 'FR12345678901'
                ],
                'success' => true
            ]);

        $this->assertDatabaseHas('companies', [
            'name' => 'Test Company',
            'siren' => '123456789',
            'siret' => '12345678901234'
        ]);
    }

    public function test_can_show_company(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/companies/{$this->company->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'legal_name',
                    'siren',
                    'siret',
                    'plan',
                    'addresses',
                    'emails',
                    'phone_numbers',
                    'users'
                ],
                'success',
                'message'
            ])
            ->assertJson([
                'data' => [
                    'id' => $this->company->id,
                    'name' => $this->company->name
                ],
                'success' => true
            ]);
    }

    public function test_can_update_company(): void
    {
        Sanctum::actingAs($this->user);

        $updateData = [
            'name' => 'Updated Company Name',
            'legal_name' => 'Updated Legal Name',
            'website' => 'https://updated-company.com'
        ];

        $response = $this->putJson("/api/v1/companies/{$this->company->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'legal_name',
                    'website'
                ],
                'success',
                'message'
            ])
            ->assertJson([
                'data' => [
                    'name' => 'Updated Company Name',
                    'legal_name' => 'Updated Legal Name',
                    'website' => 'https://updated-company.com'
                ],
                'success' => true
            ]);

        $this->assertDatabaseHas('companies', [
            'id' => $this->company->id,
            'name' => 'Updated Company Name',
            'legal_name' => 'Updated Legal Name'
        ]);
    }

    public function test_can_delete_company(): void
    {
        Sanctum::actingAs($this->user);

        // Créer une nouvelle company sans utilisateurs ni données liées
        $emptyCompany = Company::factory()->for($this->plan)->create();

        $response = $this->deleteJson("/api/v1/companies/{$emptyCompany->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Company deleted successfully'
            ]);

        $this->assertSoftDeleted('companies', [
            'id' => $emptyCompany->id
        ]);
    }

    public function test_cannot_delete_company_with_users(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/v1/companies/{$this->company->id}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company']);
    }

    public function test_unauthorized_access_returns_401(): void
    {
        $response = $this->getJson('/api/v1/companies');

        $response->assertStatus(401);
    }

    public function test_can_search_companies(): void
    {
        Sanctum::actingAs($this->user);

        Company::factory()->for($this->plan)->create(['name' => 'Searchable Company']);
        Company::factory()->for($this->plan)->create(['name' => 'Another Company']);

        $response = $this->getJson('/api/v1/companies?search=Searchable');

        $response->assertStatus(200);
        
        $companies = $response->json('data.data');
        $this->assertCount(1, $companies);
        $this->assertEquals('Searchable Company', $companies[0]['name']);
    }

    public function test_can_filter_companies_by_plan(): void
    {
        Sanctum::actingAs($this->user);

        // Créer un autre utilisateur avec un plan différent mais ayant aussi la feature manage_companies
        $anotherUser = $this->createUserWithFeatures(['manage_companies']);
        $anotherPlan = $anotherUser->company->plan;

        $response = $this->getJson("/api/v1/companies?plan_id={$this->plan->id}");

        $response->assertStatus(200);
        
        $companies = $response->json('data.data');
        foreach ($companies as $company) {
            $this->assertEquals($this->plan->id, $company['plan']['id']);
        }
    }

    public function test_validation_errors_on_invalid_data(): void
    {
        Sanctum::actingAs($this->user);

        $invalidData = [
            'name' => '', // Required
            'siren' => '123', // Wrong format
            'siret' => 'invalid', // Wrong format
            'plan_id' => 'non-existent', // Invalid UUID
            'website' => 'not-a-url' // Invalid URL
        ];

        $response = $this->postJson('/api/v1/companies', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'siren',
                'siret',
                'plan_id',
                'website'
            ]);
    }

    public function test_cannot_create_duplicate_siren(): void
    {
        Sanctum::actingAs($this->user);

        $existingCompany = Company::factory()->for($this->plan)->create([
            'siren' => '987654321'
        ]);

        $duplicateData = [
            'name' => 'Duplicate Company',
            'siren' => '987654321',
            'plan_id' => $this->plan->id
        ];

        $response = $this->postJson('/api/v1/companies', $duplicateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['siren']);
    }

    public function test_can_include_stats(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/companies/{$this->company->id}?include_stats=1");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'stats' => [
                        'users_count',
                        'clients_count',
                        'products_count',
                        'services_count',
                        'quotes_count',
                        'invoices_count'
                    ]
                ]
            ]);
    }
}
