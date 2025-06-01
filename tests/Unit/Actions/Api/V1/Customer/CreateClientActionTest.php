<?php

namespace Tests\Unit\Actions\Api\V1\Customer;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Actions\Api\V1\Customer\CreateClientAction;
use App\Domain\Company\Models\Company;
use App\Domain\Customer\Models\Client;
use App\Domain\Customer\Models\ClientAddress;
use App\Services\SirenValidationService;
use App\Services\PPFApiService;
use Mockery;

class CreateClientActionTest extends TestCase
{
    use RefreshDatabase;

    private CreateClientAction $action;
    private SirenValidationService $sirenService;
    private PPFApiService $ppfApiService;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->ppfApiService = Mockery::mock(PPFApiService::class);
        $this->sirenService = Mockery::mock(SirenValidationService::class);
        $this->action = new CreateClientAction($this->sirenService);
    }

    /** @test */
    public function it_creates_individual_client_successfully()
    {
        $data = [
            'type' => 'individual',
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean.dupont@example.com',
            'phone' => '0123456789',
            'company_id' => $this->company->id,
            'addresses' => [
                [
                    'type' => 'billing',
                    'line1' => '123 Rue de la Paix',
                    'city' => 'Paris',
                    'postal_code' => '75001',
                    'country' => 'FR',
                    'is_default' => true,
                ]
            ]
        ];

        $client = $this->action->execute($data);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals('individual', $client->type);
        $this->assertEquals('Jean', $client->first_name);
        $this->assertEquals('Dupont', $client->last_name);
        $this->assertEquals('jean.dupont@example.com', $client->email);
        $this->assertEquals($this->company->id, $client->company_id);

        // Verify address was created
        $this->assertCount(1, $client->addresses);
        $address = $client->addresses->first();
        $this->assertEquals('billing', $address->type);
        $this->assertEquals('123 Rue de la Paix', $address->line1);
        $this->assertTrue($address->is_default);
    }

    /** @test */
    public function it_creates_company_client_successfully()
    {
        $data = [
            'type' => 'company',
            'name' => 'ACME Corporation',
            'siren' => '732829320',
            'siret' => '73282932000074',
            'vat_number' => 'FR32829320',
            'email' => 'contact@acme.com',
            'phone' => '0123456789',
            'website' => 'https://www.acme.com',
            'company_id' => $this->company->id,
        ];

        // Mock SIREN validation
        $this->sirenService->shouldReceive('validateSirenSiret')
            ->once()
            ->with('732829320', '73282932000074')
            ->andReturn([
                'valid' => true,
                'data' => [
                    'siren' => '732829320',
                    'raisonSociale' => 'LA POSTE',
                ]
            ]);

        $client = $this->action->execute($data);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals('company', $client->type);
        $this->assertEquals('ACME Corporation', $client->name);
        $this->assertEquals('732829320', $client->siren);
        $this->assertEquals('73282932000074', $client->siret);
        $this->assertEquals('FR32829320', $client->vat_number);
        $this->assertEquals($this->company->id, $client->company_id);
    }

    /** @test */
    public function it_creates_client_with_multiple_addresses()
    {
        $data = [
            'type' => 'individual',
            'first_name' => 'Marie',
            'last_name' => 'Martin',
            'email' => 'marie.martin@example.com',
            'company_id' => $this->company->id,
            'addresses' => [
                [
                    'type' => 'billing',
                    'line1' => '123 Rue de Facturation',
                    'city' => 'Paris',
                    'postal_code' => '75001',
                    'country' => 'FR',
                    'is_default' => true,
                ],
                [
                    'type' => 'shipping',
                    'line1' => '456 Rue de Livraison',
                    'city' => 'Lyon',
                    'postal_code' => '69000',
                    'country' => 'FR',
                    'is_default' => false,
                ]
            ]
        ];

        $client = $this->action->execute($data);

        $this->assertCount(2, $client->addresses);
        
        $billingAddress = $client->addresses->where('type', 'billing')->first();
        $this->assertEquals('123 Rue de Facturation', $billingAddress->line1);
        $this->assertTrue($billingAddress->is_default);

        $shippingAddress = $client->addresses->where('type', 'shipping')->first();
        $this->assertEquals('456 Rue de Livraison', $shippingAddress->line1);
        $this->assertFalse($shippingAddress->is_default);
    }

    /** @test */
    public function it_sets_first_address_as_default_when_none_specified()
    {
        $data = [
            'type' => 'individual',
            'first_name' => 'Pierre',
            'last_name' => 'Durand',
            'email' => 'pierre.durand@example.com',
            'company_id' => $this->company->id,
            'addresses' => [
                [
                    'type' => 'billing',
                    'line1' => '789 Rue Test',
                    'city' => 'Marseille',
                    'postal_code' => '13000',
                    'country' => 'FR',
                    // is_default not specified
                ]
            ]
        ];

        $client = $this->action->execute($data);

        $this->assertCount(1, $client->addresses);
        $address = $client->addresses->first();
        $this->assertTrue($address->is_default);
    }

    /** @test */
    public function it_ensures_only_one_default_address_per_type()
    {
        $data = [
            'type' => 'individual',
            'first_name' => 'Sophie',
            'last_name' => 'Bernard',
            'email' => 'sophie.bernard@example.com',
            'company_id' => $this->company->id,
            'addresses' => [
                [
                    'type' => 'billing',
                    'line1' => '111 Rue A',
                    'city' => 'Paris',
                    'postal_code' => '75001',
                    'country' => 'FR',
                    'is_default' => true,
                ],
                [
                    'type' => 'billing',
                    'line1' => '222 Rue B',
                    'city' => 'Paris',
                    'postal_code' => '75002',
                    'country' => 'FR',
                    'is_default' => true, // Both set as default
                ]
            ]
        ];

        $client = $this->action->execute($data);

        $billingAddresses = $client->addresses->where('type', 'billing');
        $defaultAddresses = $billingAddresses->where('is_default', true);
        
        // Only one should be default
        $this->assertCount(1, $defaultAddresses);
        // Last one should be the default
        $this->assertEquals('222 Rue B', $defaultAddresses->first()->line1);
    }

    /** @test */
    public function it_validates_siren_siret_for_company_clients()
    {
        $data = [
            'type' => 'company',
            'name' => 'Test Company',
            'siren' => '732829320',
            'siret' => '73282932000074',
            'company_id' => $this->company->id,
        ];

        $this->sirenService->shouldReceive('validateSirenSiret')
            ->once()
            ->with('732829320', '73282932000074')
            ->andReturn([
                'valid' => true,
                'data' => [
                    'siren' => '732829320',
                    'raisonSociale' => 'LA POSTE',
                ]
            ]);

        $client = $this->action->execute($data);

        $this->assertEquals('732829320', $client->siren);
        $this->assertEquals('73282932000074', $client->siret);
    }

    /** @test */
    public function it_throws_exception_for_invalid_siren_siret()
    {
        $data = [
            'type' => 'company',
            'name' => 'Test Company',
            'siren' => '123456789',
            'siret' => '12345678901234',
            'company_id' => $this->company->id,
        ];

        $this->sirenService->shouldReceive('validateSirenSiret')
            ->once()
            ->with('123456789', '12345678901234')
            ->andReturn([
                'valid' => false,
                'message' => 'SIREN et SIRET incohérents'
            ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SIREN et SIRET incohérents');

        $this->action->execute($data);
    }

    /** @test */
    public function it_handles_siren_validation_only()
    {
        $data = [
            'type' => 'company',
            'name' => 'Test Company',
            'siren' => '732829320',
            'company_id' => $this->company->id,
        ];

        $this->sirenService->shouldReceive('validateSirenSiret')
            ->once()
            ->with('732829320', null)
            ->andReturn([
                'valid' => true,
                'data' => [
                    'siren' => '732829320',
                    'raisonSociale' => 'LA POSTE',
                ]
            ]);

        $client = $this->action->execute($data);

        $this->assertEquals('732829320', $client->siren);
        $this->assertNull($client->siret);
    }

    /** @test */
    public function it_generates_unique_client_number()
    {
        // Create an existing client to test uniqueness
        Client::factory()->for($this->company)->create(['client_number' => 'CLI-001']);

        $data = [
            'type' => 'individual',
            'first_name' => 'Test',
            'last_name' => 'User',
            'company_id' => $this->company->id,
        ];

        $client = $this->action->execute($data);

        $this->assertNotNull($client->client_number);
        $this->assertStringStartsWith('CLI-', $client->client_number);
        $this->assertNotEquals('CLI-001', $client->client_number);
    }

    /** @test */
    public function it_sets_proper_defaults_for_individual_client()
    {
        $data = [
            'type' => 'individual',
            'first_name' => 'Default',
            'last_name' => 'Test',
            'company_id' => $this->company->id,
        ];

        $client = $this->action->execute($data);

        $this->assertEquals('individual', $client->type);
        $this->assertTrue($client->is_active);
        $this->assertNotNull($client->created_at);
    }

    /** @test */
    public function it_sets_proper_defaults_for_company_client()
    {
        $data = [
            'type' => 'company',
            'name' => 'Default Company',
            'company_id' => $this->company->id,
        ];

        $client = $this->action->execute($data);

        $this->assertEquals('company', $client->type);
        $this->assertTrue($client->is_active);
        $this->assertNotNull($client->created_at);
    }

    /** @test */
    public function it_handles_notes_and_tags()
    {
        $data = [
            'type' => 'individual',
            'first_name' => 'Tagged',
            'last_name' => 'Client',
            'notes' => 'Important client - handle with care',
            'tags' => ['VIP', 'Premium'],
            'company_id' => $this->company->id,
        ];

        $client = $this->action->execute($data);

        $this->assertEquals('Important client - handle with care', $client->notes);
        $this->assertEquals(['VIP', 'Premium'], $client->tags);
    }

    /** @test */
    public function it_validates_required_fields_for_individual()
    {
        $data = [
            'type' => 'individual',
            'company_id' => $this->company->id,
            // Missing first_name and last_name
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Prénom et nom requis pour un client particulier');

        $this->action->execute($data);
    }

    /** @test */
    public function it_validates_required_fields_for_company()
    {
        $data = [
            'type' => 'company',
            'company_id' => $this->company->id,
            // Missing name
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Nom requis pour un client entreprise');

        $this->action->execute($data);
    }

    /** @test */
    public function it_cleans_phone_numbers()
    {
        $data = [
            'type' => 'individual',
            'first_name' => 'Phone',
            'last_name' => 'Test',
            'phone' => '01 23 45 67 89',
            'mobile' => '06-78-90-12-34',
            'company_id' => $this->company->id,
        ];

        $client = $this->action->execute($data);

        $this->assertEquals('0123456789', $client->phone);
        $this->assertEquals('0678901234', $client->mobile);
    }

    /** @test */
    public function it_normalizes_email()
    {
        $data = [
            'type' => 'individual',
            'first_name' => 'Email',
            'last_name' => 'Test',
            'email' => '  TEST@EXAMPLE.COM  ',
            'company_id' => $this->company->id,
        ];

        $client = $this->action->execute($data);

        $this->assertEquals('test@example.com', $client->email);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
