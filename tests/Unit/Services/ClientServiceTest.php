<?php

namespace Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Client;
use App\Models\ClientCategory;
use App\Models\ClientAddress;
use App\Models\ClientContact;
use App\Services\ClientService;
use App\Exceptions\ClientLimitExceededException;
use Mockery;

class ClientServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private ClientService $clientService;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientService = app(ClientService::class);
        $this->company = Company::factory()->create(['subscription_type' => 'free']);
    }

    /** @test */
    public function it_can_get_filtered_clients()
    {
        // Créer des clients de test
        Client::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'type' => 'client',
        ]);

        Client::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'type' => 'prospect',
        ]);

        // Test sans filtres
        $result = $this->clientService->getFilteredClients($this->company->id, []);
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(8, $result->total());

        // Test avec filtre par type
        $result = $this->clientService->getFilteredClients($this->company->id, ['type' => 'client']);
        $this->assertEquals(5, $result->total());

        // Test avec filtre par type prospect
        $result = $this->clientService->getFilteredClients($this->company->id, ['type' => 'prospect']);
        $this->assertEquals(3, $result->total());
    }

    /** @test */
    public function it_can_filter_clients_by_search()
    {
        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'John Doe Company',
            'email' => 'john@example.com',
        ]);

        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Jane Smith Ltd',
            'email' => 'jane@example.com',
        ]);

        // Recherche par nom
        $result = $this->clientService->getFilteredClients($this->company->id, ['search' => 'John']);
        $this->assertEquals(1, $result->total());
        $this->assertEquals('John Doe Company', $result->items()[0]->name);

        // Recherche par email
        $result = $this->clientService->getFilteredClients($this->company->id, ['search' => 'jane@example']);
        $this->assertEquals(1, $result->total());
        $this->assertEquals('Jane Smith Ltd', $result->items()[0]->name);
    }

    /** @test */
    public function it_can_filter_clients_by_category()
    {
        $category = ClientCategory::factory()->create(['company_id' => $this->company->id]);

        Client::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
        ]);

        Client::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => null,
        ]);

        $result = $this->clientService->getFilteredClients($this->company->id, ['category_id' => $category->id]);
        $this->assertEquals(1, $result->total());
    }

    /** @test */
    public function it_can_filter_clients_by_status()
    {
        Client::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);

        Client::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'inactive',
        ]);

        $result = $this->clientService->getFilteredClients($this->company->id, ['status' => 'active']);
        $this->assertEquals(1, $result->total());
        $this->assertEquals('active', $result->items()[0]->status);
    }

    /** @test */
    public function it_can_sort_clients()
    {
        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'B Company',
            'created_at' => now()->subDay(),
        ]);

        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'A Company',
            'created_at' => now(),
        ]);

        // Tri par nom ascendant
        $result = $this->clientService->getFilteredClients($this->company->id, [
            'sort_by' => 'name',
            'sort_direction' => 'asc'
        ]);

        $items = $result->items();
        $this->assertEquals('A Company', $items[0]->name);
        $this->assertEquals('B Company', $items[1]->name);

        // Tri par date descendant
        $result = $this->clientService->getFilteredClients($this->company->id, [
            'sort_by' => 'created_at',
            'sort_direction' => 'desc'
        ]);

        $items = $result->items();
        $this->assertEquals('A Company', $items[0]->name);
        $this->assertEquals('B Company', $items[1]->name);
    }

    /** @test */
    public function it_can_create_client_with_basic_data()
    {
        $clientData = [
            'type' => 'client',
            'name' => 'Test Company',
            'email' => 'test@example.com',
            'phone' => '0123456789',
        ];

        $client = $this->clientService->createClient($this->company->id, $clientData);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals('Test Company', $client->name);
        $this->assertEquals('test@example.com', $client->email);
        $this->assertEquals($this->company->id, $client->company_id);
    }

    /** @test */
    public function it_can_create_client_with_addresses()
    {
        $clientData = [
            'type' => 'client',
            'name' => 'Test Company',
            'addresses' => [
                [
                    'type' => 'billing',
                    'line1' => '123 Main St',
                    'postal_code' => '75001',
                    'city' => 'Paris',
                    'country' => 'FR',
                    'is_default' => true,
                ],
                [
                    'type' => 'delivery',
                    'line1' => '456 Oak Ave',
                    'postal_code' => '69001',
                    'city' => 'Lyon',
                    'country' => 'FR',
                    'is_default' => false,
                ]
            ],
        ];

        $client = $this->clientService->createClient($this->company->id, $clientData);

        $this->assertCount(2, $client->addresses);
        $this->assertEquals('123 Main St', $client->addresses->where('is_default', true)->first()->line1);
    }

    /** @test */
    public function it_can_create_client_with_contacts()
    {
        $clientData = [
            'type' => 'client',
            'name' => 'Test Company',
            'contacts' => [
                [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'john@example.com',
                    'phone' => '0123456789',
                    'is_primary' => true,
                ],
                [
                    'first_name' => 'Jane',
                    'last_name' => 'Smith',
                    'email' => 'jane@example.com',
                    'is_primary' => false,
                ]
            ],
        ];

        $client = $this->clientService->createClient($this->company->id, $clientData);

        $this->assertCount(2, $client->contacts);
        $this->assertEquals('John', $client->contacts->where('is_primary', true)->first()->first_name);
    }

    /** @test */
    public function it_can_update_client_basic_data()
    {
        $client = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $updateData = [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'phone' => '0123456789',
        ];

        $updatedClient = $this->clientService->updateClient($client, $updateData);

        $this->assertEquals('New Name', $updatedClient->name);
        $this->assertEquals('new@example.com', $updatedClient->email);
        $this->assertEquals('0123456789', $updatedClient->phone);
    }

    /** @test */
    public function it_can_update_client_addresses()
    {
        $client = Client::factory()->hasAddresses(2)->create([
            'company_id' => $this->company->id,
        ]);

        $oldAddressCount = $client->addresses->count();

        $updateData = [
            'addresses' => [
                [
                    'type' => 'billing',
                    'line1' => 'New Address Line 1',
                    'postal_code' => '75002',
                    'city' => 'Paris',
                    'country' => 'FR',
                    'is_default' => true,
                ]
            ],
        ];

        $updatedClient = $this->clientService->updateClient($client, $updateData);

        $this->assertCount(1, $updatedClient->addresses);
        $this->assertEquals('New Address Line 1', $updatedClient->addresses->first()->line1);
    }

    /** @test */
    public function it_can_update_existing_addresses()
    {
        $client = Client::factory()->hasAddresses(1)->create([
            'company_id' => $this->company->id,
        ]);

        $existingAddress = $client->addresses->first();

        $updateData = [
            'addresses' => [
                [
                    'id' => $existingAddress->id,
                    'type' => 'billing',
                    'line1' => 'Updated Address',
                    'postal_code' => $existingAddress->postal_code,
                    'city' => $existingAddress->city,
                    'country' => $existingAddress->country,
                    'is_default' => true,
                ]
            ],
        ];

        $updatedClient = $this->clientService->updateClient($client, $updateData);

        $this->assertCount(1, $updatedClient->addresses);
        $this->assertEquals('Updated Address', $updatedClient->addresses->first()->line1);
        $this->assertEquals($existingAddress->id, $updatedClient->addresses->first()->id);
    }

    /** @test */
    public function it_can_convert_prospect_to_client()
    {
        $prospect = Client::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'prospect',
        ]);

        $convertedClient = $this->clientService->convertProspectToClient($prospect);

        $this->assertEquals('client', $convertedClient->type);
        $this->assertNotNull($convertedClient->converted_at);
    }

    /** @test */
    public function it_can_delete_client()
    {
        $client = Client::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->clientService->deleteClient($client);

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }

    /** @test */
    public function it_can_get_client_statistics()
    {
        // Créer des clients de test
        Client::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'type' => 'client',
            'status' => 'active',
        ]);

        Client::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'type' => 'prospect',
            'status' => 'active',
        ]);

        Client::factory()->count(1)->create([
            'company_id' => $this->company->id,
            'type' => 'client',
            'status' => 'inactive',
        ]);

        $stats = $this->clientService->getClientStatistics($this->company->id);

        $this->assertEquals(6, $stats['total']);
        $this->assertEquals(4, $stats['clients']); // 3 actifs + 1 inactif
        $this->assertEquals(2, $stats['prospects']);
        $this->assertEquals(5, $stats['active']); // 3 clients actifs + 2 prospects actifs
        $this->assertEquals(1, $stats['inactive']);
    }

    /** @test */
    public function it_checks_client_limit_for_free_accounts()
    {
        // Créer 50 clients (limite pour les comptes gratuits)
        Client::factory()->count(50)->create([
            'company_id' => $this->company->id,
        ]);

        $this->expectException(ClientLimitExceededException::class);

        $this->clientService->checkClientLimit($this->company);
    }

    /** @test */
    public function it_allows_unlimited_clients_for_premium_accounts()
    {
        $premiumCompany = Company::factory()->create(['subscription_type' => 'premium']);

        // Créer plus de 50 clients
        Client::factory()->count(60)->create([
            'company_id' => $premiumCompany->id,
        ]);

        // Ne devrait pas lancer d'exception
        $this->clientService->checkClientLimit($premiumCompany);

        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    /** @test */
    public function it_sets_default_values_when_creating_client()
    {
        $clientData = [
            'type' => 'client',
            'name' => 'Test Company',
        ];

        $client = $this->clientService->createClient($this->company->id, $clientData);

        $this->assertEquals('active', $client->status);
        $this->assertEquals(30, $client->payment_terms);
        $this->assertEquals('bank_transfer', $client->payment_method);
    }

    /** @test */
    public function it_ensures_single_default_address()
    {
        $clientData = [
            'type' => 'client',
            'name' => 'Test Company',
            'addresses' => [
                [
                    'type' => 'billing',
                    'line1' => '123 Main St',
                    'postal_code' => '75001',
                    'city' => 'Paris',
                    'country' => 'FR',
                    'is_default' => true,
                ],
                [
                    'type' => 'delivery',
                    'line1' => '456 Oak Ave',
                    'postal_code' => '69001',
                    'city' => 'Lyon',
                    'country' => 'FR',
                    'is_default' => true, // Two defaults specified
                ]
            ],
        ];

        $client = $this->clientService->createClient($this->company->id, $clientData);

        // Should only have one default address
        $defaultAddresses = $client->addresses->where('is_default', true);
        $this->assertCount(1, $defaultAddresses);
    }

    /** @test */
    public function it_ensures_single_primary_contact()
    {
        $clientData = [
            'type' => 'client',
            'name' => 'Test Company',
            'contacts' => [
                [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'john@example.com',
                    'is_primary' => true,
                ],
                [
                    'first_name' => 'Jane',
                    'last_name' => 'Smith',
                    'email' => 'jane@example.com',
                    'is_primary' => true, // Two primaries specified
                ]
            ],
        ];

        $client = $this->clientService->createClient($this->company->id, $clientData);

        // Should only have one primary contact
        $primaryContacts = $client->contacts->where('is_primary', true);
        $this->assertCount(1, $primaryContacts);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
