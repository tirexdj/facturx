<?php

namespace Tests\Unit\Actions\Api\V1\Customer;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Actions\Api\V1\Customer\UpdateClientAction;
use App\Domain\Company\Models\Company;
use App\Domain\Customer\Models\Client;
use App\Domain\Customer\Models\ClientAddress;
use App\Services\SirenValidationService;
use Mockery;

class UpdateClientActionTest extends TestCase
{
    use RefreshDatabase;

    private UpdateClientAction $action;
    private SirenValidationService $sirenService;
    private Company $company;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->sirenService = Mockery::mock(SirenValidationService::class);
        $this->action = new UpdateClientAction($this->sirenService);
        
        $this->client = Client::factory()
            ->for($this->company)
            ->create([
                'type' => 'individual',
                'first_name' => 'Jean',
                'last_name' => 'Dupont',
                'email' => 'jean.dupont@example.com'
            ]);
    }

    /** @test */
    public function it_updates_individual_client_successfully()
    {
        $data = [
            'first_name' => 'Pierre',
            'last_name' => 'Martin',
            'email' => 'pierre.martin@example.com',
            'phone' => '0987654321',
        ];

        $updatedClient = $this->action->execute($this->client, $data);

        $this->assertEquals('Pierre', $updatedClient->first_name);
        $this->assertEquals('Martin', $updatedClient->last_name);
        $this->assertEquals('pierre.martin@example.com', $updatedClient->email);
        $this->assertEquals('0987654321', $updatedClient->phone);
        
        // Ensure type didn't change
        $this->assertEquals('individual', $updatedClient->type);
    }

    /** @test */
    public function it_updates_company_client_successfully()
    {
        $companyClient = Client::factory()
            ->for($this->company)
            ->create([
                'type' => 'company',
                'name' => 'Old Company Name',
                'siren' => '732829320',
                'siret' => '73282932000074'
            ]);

        $data = [
            'name' => 'New Company Name',
            'email' => 'new@company.com',
            'website' => 'https://newcompany.com',
        ];

        $updatedClient = $this->action->execute($companyClient, $data);

        $this->assertEquals('New Company Name', $updatedClient->name);
        $this->assertEquals('new@company.com', $updatedClient->email);
        $this->assertEquals('https://newcompany.com', $updatedClient->website);
        
        // SIREN/SIRET should remain unchanged when not in update data
        $this->assertEquals('732829320', $updatedClient->siren);
        $this->assertEquals('73282932000074', $updatedClient->siret);
    }

    /** @test */
    public function it_updates_siren_siret_with_validation()
    {
        $companyClient = Client::factory()
            ->for($this->company)
            ->create([
                'type' => 'company',
                'name' => 'Test Company',
                'siren' => '732829320'
            ]);

        $data = [
            'siren' => '552120222',
            'siret' => '55212022200025',
        ];

        $this->sirenService->shouldReceive('validateSirenSiret')
            ->once()
            ->with('552120222', '55212022200025')
            ->andReturn([
                'valid' => true,
                'data' => [
                    'siren' => '552120222',
                    'raisonSociale' => 'SNCF',
                ]
            ]);

        $updatedClient = $this->action->execute($companyClient, $data);

        $this->assertEquals('552120222', $updatedClient->siren);
        $this->assertEquals('55212022200025', $updatedClient->siret);
    }

    /** @test */
    public function it_throws_exception_for_invalid_siren_siret_update()
    {
        $companyClient = Client::factory()
            ->for($this->company)
            ->create([
                'type' => 'company',
                'name' => 'Test Company'
            ]);

        $data = [
            'siren' => '123456789',
            'siret' => '98765432101234',
        ];

        $this->sirenService->shouldReceive('validateSirenSiret')
            ->once()
            ->with('123456789', '98765432101234')
            ->andReturn([
                'valid' => false,
                'message' => 'SIREN et SIRET incohérents'
            ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SIREN et SIRET incohérents');

        $this->action->execute($companyClient, $data);
    }

    /** @test */
    public function it_updates_only_siren_without_siret()
    {
        $companyClient = Client::factory()
            ->for($this->company)
            ->create([
                'type' => 'company',
                'name' => 'Test Company'
            ]);

        $data = [
            'siren' => '732829320',
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

        $updatedClient = $this->action->execute($companyClient, $data);

        $this->assertEquals('732829320', $updatedClient->siren);
        $this->assertNull($updatedClient->siret);
    }

    /** @test */
    public function it_updates_client_addresses()
    {
        $address = ClientAddress::factory()
            ->for($this->client)
            ->create([
                'type' => 'billing',
                'line1' => 'Old Address',
                'city' => 'Old City'
            ]);

        $data = [
            'first_name' => 'Updated Jean',
            'addresses' => [
                [
                    'id' => $address->id,
                    'line1' => 'New Address',
                    'city' => 'New City',
                    'postal_code' => '12345',
                    'country' => 'FR',
                    'type' => 'billing',
                    'is_default' => true,
                ]
            ]
        ];

        $updatedClient = $this->action->execute($this->client, $data);

        $this->assertEquals('Updated Jean', $updatedClient->first_name);
        
        $updatedAddress = $updatedClient->addresses->first();
        $this->assertEquals('New Address', $updatedAddress->line1);
        $this->assertEquals('New City', $updatedAddress->city);
        $this->assertEquals('12345', $updatedAddress->postal_code);
    }

    /** @test */
    public function it_adds_new_addresses()
    {
        $data = [
            'addresses' => [
                [
                    'type' => 'shipping',
                    'line1' => 'New Shipping Address',
                    'city' => 'Shipping City',
                    'postal_code' => '54321',
                    'country' => 'FR',
                    'is_default' => false,
                ]
            ]
        ];

        $updatedClient = $this->action->execute($this->client, $data);

        $this->assertCount(1, $updatedClient->addresses);
        $newAddress = $updatedClient->addresses->first();
        $this->assertEquals('shipping', $newAddress->type);
        $this->assertEquals('New Shipping Address', $newAddress->line1);
    }

    /** @test */
    public function it_removes_addresses_not_in_update()
    {
        $keepAddress = ClientAddress::factory()
            ->for($this->client)
            ->create(['type' => 'billing']);

        $removeAddress = ClientAddress::factory()
            ->for($this->client)
            ->create(['type' => 'shipping']);

        $data = [
            'addresses' => [
                [
                    'id' => $keepAddress->id,
                    'type' => 'billing',
                    'line1' => 'Updated Address',
                    'city' => 'Updated City',
                    'postal_code' => '11111',
                    'country' => 'FR',
                    'is_default' => true,
                ]
                // removeAddress not included, should be soft deleted
            ]
        ];

        $updatedClient = $this->action->execute($this->client, $data);

        $this->assertCount(1, $updatedClient->addresses);
        $this->assertTrue($updatedClient->addresses->contains($keepAddress));
        
        // Check that removed address is soft deleted
        $this->assertSoftDeleted('client_addresses', [
            'id' => $removeAddress->id
        ]);
    }

    /** @test */
    public function it_ensures_one_default_address_per_type()
    {
        $data = [
            'addresses' => [
                [
                    'type' => 'billing',
                    'line1' => 'Address 1',
                    'city' => 'City 1',
                    'postal_code' => '11111',
                    'country' => 'FR',
                    'is_default' => true,
                ],
                [
                    'type' => 'billing',
                    'line1' => 'Address 2',
                    'city' => 'City 2',
                    'postal_code' => '22222',
                    'country' => 'FR',
                    'is_default' => true,
                ]
            ]
        ];

        $updatedClient = $this->action->execute($this->client, $data);

        $billingAddresses = $updatedClient->addresses->where('type', 'billing');
        $defaultAddresses = $billingAddresses->where('is_default', true);
        
        // Only one should be default (the last one processed)
        $this->assertCount(1, $defaultAddresses);
        $this->assertEquals('Address 2', $defaultAddresses->first()->line1);
    }

    /** @test */
    public function it_preserves_existing_data_when_not_updated()
    {
        $originalNotes = 'Important client notes';
        $originalTags = ['VIP', 'Premium'];
        
        $this->client->update([
            'notes' => $originalNotes,
            'tags' => $originalTags,
        ]);

        $data = [
            'first_name' => 'Updated Name',
            // notes and tags not included in update
        ];

        $updatedClient = $this->action->execute($this->client, $data);

        $this->assertEquals('Updated Name', $updatedClient->first_name);
        $this->assertEquals($originalNotes, $updatedClient->notes);
        $this->assertEquals($originalTags, $updatedClient->tags);
    }

    /** @test */
    public function it_updates_tags_and_notes()
    {
        $data = [
            'notes' => 'Updated notes',
            'tags' => ['Updated', 'Tags'],
        ];

        $updatedClient = $this->action->execute($this->client, $data);

        $this->assertEquals('Updated notes', $updatedClient->notes);
        $this->assertEquals(['Updated', 'Tags'], $updatedClient->tags);
    }

    /** @test */
    public function it_cleans_and_normalizes_input_data()
    {
        $data = [
            'first_name' => '  Jean  ',
            'email' => '  JEAN@EXAMPLE.COM  ',
            'phone' => '01 23 45 67 89',
            'mobile' => '06-78-90-12-34',
        ];

        $updatedClient = $this->action->execute($this->client, $data);

        $this->assertEquals('Jean', $updatedClient->first_name);
        $this->assertEquals('jean@example.com', $updatedClient->email);
        $this->assertEquals('0123456789', $updatedClient->phone);
        $this->assertEquals('0678901234', $updatedClient->mobile);
    }

    /** @test */
    public function it_validates_email_uniqueness_within_company()
    {
        // Create another client with same email in same company
        Client::factory()
            ->for($this->company)
            ->create(['email' => 'existing@example.com']);

        $data = [
            'email' => 'existing@example.com',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cette adresse email est déjà utilisée');

        $this->action->execute($this->client, $data);
    }

    /** @test */
    public function it_allows_same_email_for_client_being_updated()
    {
        $data = [
            'email' => $this->client->email, // Same email as current client
            'first_name' => 'Updated Name',
        ];

        $updatedClient = $this->action->execute($this->client, $data);

        $this->assertEquals($this->client->email, $updatedClient->email);
        $this->assertEquals('Updated Name', $updatedClient->first_name);
    }

    /** @test */
    public function it_allows_same_email_in_different_company()
    {
        $otherCompany = Company::factory()->create();
        Client::factory()
            ->for($otherCompany)
            ->create(['email' => 'same@example.com']);

        $data = [
            'email' => 'same@example.com',
        ];

        $updatedClient = $this->action->execute($this->client, $data);

        $this->assertEquals('same@example.com', $updatedClient->email);
    }

    /** @test */
    public function it_updates_is_active_status()
    {
        $data = [
            'is_active' => false,
        ];

        $updatedClient = $this->action->execute($this->client, $data);

        $this->assertFalse($updatedClient->is_active);
    }

    /** @test */
    public function it_updates_payment_terms()
    {
        $data = [
            'payment_terms' => 45,
            'payment_method' => 'bank_transfer',
        ];

        $updatedClient = $this->action->execute($this->client, $data);

        $this->assertEquals(45, $updatedClient->payment_terms);
        $this->assertEquals('bank_transfer', $updatedClient->payment_method);
    }

    /** @test */
    public function it_updates_timestamps()
    {
        $originalUpdatedAt = $this->client->updated_at;
        
        // Wait a bit to ensure timestamp difference
        sleep(1);

        $data = [
            'first_name' => 'Updated Name',
        ];

        $updatedClient = $this->action->execute($this->client, $data);

        $this->assertNotEquals($originalUpdatedAt, $updatedClient->updated_at);
    }

    /** @test */
    public function it_handles_empty_update_data_gracefully()
    {
        $originalClient = $this->client->toArray();
        
        $data = [];

        $updatedClient = $this->action->execute($this->client, $data);

        // All data should remain the same except updated_at
        $this->assertEquals($originalClient['first_name'], $updatedClient->first_name);
        $this->assertEquals($originalClient['last_name'], $updatedClient->last_name);
        $this->assertEquals($originalClient['email'], $updatedClient->email);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
