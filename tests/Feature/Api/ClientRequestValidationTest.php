<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use App\Domain\Auth\Models\User;
use App\Domain\Company\Models\Company;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClientRequestValidationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create([
            'current_company_id' => $this->company->id,
        ]);

        $this->actingAs($this->user, 'sanctum');
    }

    /** @test */
    public function it_validates_required_fields_for_store_request()
    {
        $response = $this->postJson('/api/clients', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'type',
                'name',
            ]);
    }

    /** @test */
    public function it_validates_type_field()
    {
        $response = $this->postJson('/api/clients', [
            'type' => 'invalid_type',
            'name' => 'Test Company',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function it_validates_email_format()
    {
        $response = $this->postJson('/api/clients', [
            'type' => 'client',
            'name' => 'Test Company',
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_validates_siren_format()
    {
        $invalidSirens = [
            'abc123456', // Contains letters
            '12345',     // Too short
            '1234567890', // Too long
            '',          // Empty
        ];

        foreach ($invalidSirens as $siren) {
            $response = $this->postJson('/api/clients', [
                'type' => 'client',
                'name' => 'Test Company',
                'siren' => $siren,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['siren']);
        }
    }

    /** @test */
    public function it_validates_siret_format()
    {
        $invalidSirets = [
            'abc12345678901', // Contains letters
            '12345',          // Too short
            '123456789012345', // Too long
            '',               // Empty
        ];

        foreach ($invalidSirets as $siret) {
            $response = $this->postJson('/api/clients', [
                'type' => 'client',
                'name' => 'Test Company',
                'siret' => $siret,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['siret']);
        }
    }

    /** @test */
    public function it_validates_category_exists()
    {
        $response = $this->postJson('/api/clients', [
            'type' => 'client',
            'name' => 'Test Company',
            'category_id' => 9999, // Non-existent category
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    /** @test */
    public function it_validates_addresses_structure()
    {
        $response = $this->postJson('/api/clients', [
            'type' => 'client',
            'name' => 'Test Company',
            'addresses' => [
                [
                    // Missing required fields
                    'type' => 'billing',
                ]
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'addresses.0.line1',
                'addresses.0.postal_code',
                'addresses.0.city',
                'addresses.0.country',
            ]);
    }

    /** @test */
    public function it_validates_addresses_limit()
    {
        $addresses = [];
        for ($i = 0; $i < 6; $i++) {
            $addresses[] = [
                'type' => 'billing',
                'line1' => '123 Main St',
                'postal_code' => '75001',
                'city' => 'Paris',
                'country' => 'FR',
            ];
        }

        $response = $this->postJson('/api/clients', [
            'type' => 'client',
            'name' => 'Test Company',
            'addresses' => $addresses,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['addresses']);
    }

    /** @test */
    public function it_validates_contacts_structure()
    {
        $response = $this->postJson('/api/clients', [
            'type' => 'client',
            'name' => 'Test Company',
            'contacts' => [
                [
                    // Missing required fields
                    'email' => 'test@example.com',
                ]
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'contacts.0.first_name',
                'contacts.0.last_name',
            ]);
    }

    /** @test */
    public function it_validates_contacts_limit()
    {
        $contacts = [];
        for ($i = 0; $i < 11; $i++) {
            $contacts[] = [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => "john{$i}@example.com",
            ];
        }

        $response = $this->postJson('/api/clients', [
            'type' => 'client',
            'name' => 'Test Company',
            'contacts' => $contacts,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['contacts']);
    }

    /** @test */
    public function it_validates_payment_terms_range()
    {
        $response = $this->postJson('/api/clients', [
            'type' => 'client',
            'name' => 'Test Company',
            'payment_terms' => 400, // Over maximum
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_terms']);

        $response = $this->postJson('/api/clients', [
            'type' => 'client',
            'name' => 'Test Company',
            'payment_terms' => -10, // Negative
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_terms']);
    }

    /** @test */
    public function it_validates_discount_rate_range()
    {
        $response = $this->postJson('/api/clients', [
            'type' => 'client',
            'name' => 'Test Company',
            'discount_rate' => 150, // Over 100%
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['discount_rate']);

        $response = $this->postJson('/api/clients', [
            'type' => 'client',
            'name' => 'Test Company',
            'discount_rate' => -10, // Negative
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['discount_rate']);
    }

    /** @test */
    public function it_validates_website_url_format()
    {
        $response = $this->postJson('/api/clients', [
            'type' => 'client',
            'name' => 'Test Company',
            'website' => 'not-a-valid-url',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['website']);
    }

    /** @test */
    public function it_cleans_siren_siret_numbers()
    {
        $response = $this->postJson('/api/clients', [
            'type' => 'client',
            'name' => 'Test Company',
            'siren' => '123 456 789', // With spaces
            'siret' => '12345678901234', // Clean
        ]);

        // Should pass validation after cleaning
        $response->assertStatus(201);
    }

    /** @test */
    public function it_sets_default_address_when_none_specified()
    {
        $response = $this->postJson('/api/clients', [
            'type' => 'client',
            'name' => 'Test Company',
            'addresses' => [
                [
                    'type' => 'billing',
                    'line1' => '123 Main St',
                    'postal_code' => '75001',
                    'city' => 'Paris',
                    'country' => 'FR',
                    // No is_default specified
                ],
                [
                    'type' => 'delivery',
                    'line1' => '456 Other St',
                    'postal_code' => '75002',
                    'city' => 'Paris',
                    'country' => 'FR',
                ]
            ],
        ]);

        $response->assertStatus(201);

        // First address should be set as default
        $client = $response->json('data');
        $this->assertTrue($client['addresses'][0]['is_default']);
    }

    /** @test */
    public function it_sets_primary_contact_when_none_specified()
    {
        $response = $this->postJson('/api/clients', [
            'type' => 'client',
            'name' => 'Test Company',
            'contacts' => [
                [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'john@example.com',
                    // No is_primary specified
                ],
                [
                    'first_name' => 'Jane',
                    'last_name' => 'Smith',
                    'email' => 'jane@example.com',
                ]
            ],
        ]);

        $response->assertStatus(201);

        // First contact should be set as primary
        $client = $response->json('data');
        $this->assertTrue($client['contacts'][0]['is_primary']);
    }
}
