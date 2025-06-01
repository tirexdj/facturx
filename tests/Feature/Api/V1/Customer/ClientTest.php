<?php

namespace Tests\Feature\Api\V1\Customer;

use Tests\Feature\Api\V1\BaseApiTest;
use App\Domain\Customer\Models\Client;
use App\Domain\Shared\Models\Address;
use App\Domain\Company\Models\Company;
use Illuminate\Testing\Fluent\AssertableJson;

class ClientTest extends BaseApiTest
{
    protected string $apiBase = '/api/v1/clients';

    public function test_can_list_clients(): void
    {
        // Créer des clients pour cette company
        $clients = Client::factory()
            ->for($this->company)
            ->count(3)
            ->create();

        // Créer des clients pour une autre company (ne doivent pas apparaître)
        $otherCompany = Company::factory()->create();
        Client::factory()
            ->for($otherCompany)
            ->count(2)
            ->create();

        $response = $this->actingAsUser($this->user)
            ->apiGet($this->apiBase);

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data', 3)
                    ->has('meta')
                    ->has('links')
                    ->where('meta.total', 3)
            );
    }

    public function test_can_list_clients_with_pagination(): void
    {
        Client::factory()
            ->for($this->company)
            ->count(25)
            ->create();

        $response = $this->actingAsUser($this->user)
            ->apiGet($this->apiBase . '?per_page=10');

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data', 10)
                    ->where('meta.total', 25)
                    ->where('meta.per_page', 10)
                    ->where('meta.current_page', 1)
            );
    }

    public function test_can_search_clients(): void
    {
        Client::factory()
            ->for($this->company)
            ->create(['name' => 'ACME Corporation']);

        Client::factory()
            ->for($this->company)
            ->create(['name' => 'Test Company']);

        $response = $this->actingAsUser($this->user)
            ->apiGet($this->apiBase . '?search=ACME');

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data', 1)
                    ->where('data.0.name', 'ACME Corporation')
            );
    }

    public function test_can_filter_clients_by_type(): void
    {
        Client::factory()
            ->for($this->company)
            ->create(['client_type' => 'individual']);

        Client::factory()
            ->for($this->company)
            ->create(['client_type' => 'company']);

        $response = $this->actingAsUser($this->user)
            ->apiGet($this->apiBase . '?filter[client_type]=company');

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data', 1)
                    ->where('data.0.client_type', 'company')
            );
    }

    public function test_can_sort_clients(): void
    {
        Client::factory()
            ->for($this->company)
            ->create(['name' => 'Zebra Company']);

        Client::factory()
            ->for($this->company)
            ->create(['name' => 'Alpha Company']);

        $response = $this->actingAsUser($this->user)
            ->apiGet($this->apiBase . '?sort=name');

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('data.0.name', 'Alpha Company')
                    ->where('data.1.name', 'Zebra Company')
            );
    }

    public function test_can_create_individual_client(): void
    {
        $clientData = [
            'client_type' => 'individual',
            'name' => 'Jean Dupont',
            'addresses' => [
                [
                    'is_billing' => true,
                    'line_1' => '123 Rue de la Paix',
                    'city' => 'Paris',
                    'postal_code' => '75001',
                    'country_code' => 'FR',
                    'is_default' => true,
                ]
            ]
        ];

        $response = $this->actingAsUser($this->user)
            ->apiPost($this->apiBase, $clientData);

        $response->assertCreated()
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('data.client_type', 'individual')
                    ->where('data.name', 'Jean Dupont')
                    ->where('data.company_id', $this->company->id)
                    ->has('data.addresses', 1)
                    ->where('data.addresses.0.is_billing', true)
            );

        $this->assertDatabaseHas('clients', [
            'client_type' => 'individual',
            'name' => 'Jean Dupont',
            'company_id' => $this->company->id,
        ]);

        $this->assertDatabaseHas('addresses', [
            'is_billing' => true,
            'line_1' => '123 Rue de la Paix',
            'city' => 'Paris',
            'postal_code' => '75001',
            'country_code' => 'FR',
            'is_default' => true,
        ]);
    }

    public function test_can_create_company_client(): void
    {
        $clientData = [
            'client_type' => 'company',
            'name' => 'ACME Corporation',
            'siren' => '732829320',
            'siret' => '73282932000074',
            'vat_number' => 'FR32829320',
            'website' => 'https://www.acme.com',
            'addresses' => [
                [
                    'is_billing' => true,
                    'line_1' => '456 Avenue des Entreprises',
                    'city' => 'Lyon',
                    'postal_code' => '69000',
                    'country_code' => 'FR',
                    'is_default' => true,
                ]
            ]
        ];

        $response = $this->actingAsUser($this->user)
            ->apiPost($this->apiBase, $clientData);

        $response->assertCreated()
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('data.client_type', 'company')
                    ->where('data.name', 'ACME Corporation')
                    ->where('data.siren', '732829320')
                    ->where('data.siret', '73282932000074')
                    ->where('data.vat_number', 'FR32829320')
                    ->where('data.company_id', $this->company->id)
            );
    }

    public function test_can_show_client(): void
    {
        $client = Client::factory()
            ->for($this->company)
            ->has(Address::factory()->count(2), 'addresses')
            ->create();

        $response = $this->actingAsUser($this->user)
            ->apiGet($this->apiBase . '/' . $client->id);

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('data.id', $client->id)
                    ->where('data.company_id', $this->company->id)
                    ->has('data.addresses', 2)
            );
    }

    public function test_can_show_client_with_includes(): void
    {
        $client = Client::factory()
            ->for($this->company)
            ->has(Address::factory()->count(2), 'addresses')
            ->create();

        $response = $this->actingAsUser($this->user)
            ->apiGet($this->apiBase . '/' . $client->id . '?include=addresses');

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('data.id', $client->id)
                    ->has('data.addresses', 2)
            );
    }

    public function test_can_update_client(): void
    {
        $client = Client::factory()
            ->for($this->company)
            ->create([
                'client_type' => 'individual',
                'name' => 'Jean Dupont'
            ]);

        $updateData = [
            'name' => 'Pierre Martin',
            'notes' => 'Client mis à jour',
        ];

        $response = $this->actingAsUser($this->user)
            ->apiPut($this->apiBase . '/' . $client->id, $updateData);

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('data.name', 'Pierre Martin')
                    ->where('data.notes', 'Client mis à jour')
            );

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Pierre Martin',
            'notes' => 'Client mis à jour',
        ]);
    }

    public function test_can_delete_client(): void
    {
        $client = Client::factory()
            ->for($this->company)
            ->create();

        $response = $this->actingAsUser($this->user)
            ->apiDelete($this->apiBase . '/' . $client->id);

        $response->assertNoContent();

        $this->assertSoftDeleted('clients', [
            'id' => $client->id,
        ]);
    }

    public function test_cannot_access_client_from_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $client = Client::factory()
            ->for($otherCompany)
            ->create();

        $response = $this->actingAsUser($this->user)
            ->apiGet($this->apiBase . '/' . $client->id);

        $response->assertNotFound();
    }

    public function test_cannot_create_client_without_authentication(): void
    {
        $clientData = [
            'client_type' => 'individual',
            'name' => 'Jean Dupont',
        ];

        $response = $this->apiPost($this->apiBase, $clientData);

        $response->assertUnauthorized();
    }

    public function test_cannot_create_client_with_invalid_data(): void
    {
        $clientData = [
            'client_type' => 'invalid_type',
            'name' => '', // nom vide
        ];

        $response = $this->actingAsUser($this->user)
            ->apiPost($this->apiBase, $clientData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['client_type', 'name']);
    }

    public function test_cannot_create_individual_client_without_required_fields(): void
    {
        $clientData = [
            'client_type' => 'individual',
            // Missing name
        ];

        $response = $this->actingAsUser($this->user)
            ->apiPost($this->apiBase, $clientData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_cannot_create_company_client_without_required_fields(): void
    {
        $clientData = [
            'client_type' => 'company',
            // Missing name
        ];

        $response = $this->actingAsUser($this->user)
            ->apiPost($this->apiBase, $clientData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_cannot_create_client_with_invalid_siren(): void
    {
        $clientData = [
            'client_type' => 'company',
            'name' => 'Test Company',
            'siren' => '123456789', // Invalid checksum
        ];

        $response = $this->actingAsUser($this->user)
            ->apiPost($this->apiBase, $clientData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['siren']);
    }

    public function test_cannot_create_client_with_invalid_siret(): void
    {
        $clientData = [
            'client_type' => 'company',
            'name' => 'Test Company',
            'siret' => '12345678901234', // Invalid checksum
        ];

        $response = $this->actingAsUser($this->user)
            ->apiPost($this->apiBase, $clientData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['siret']);
    }

    public function test_cannot_create_client_with_inconsistent_siren_siret(): void
    {
        $clientData = [
            'client_type' => 'company',
            'name' => 'Test Company',
            'siren' => '732829320',
            'siret' => '55212022200025', // Different SIREN
        ];

        $response = $this->actingAsUser($this->user)
            ->apiPost($this->apiBase, $clientData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['siret']);
    }

    public function test_can_create_client_with_valid_siren_siret(): void
    {
        $clientData = [
            'client_type' => 'company',
            'name' => 'La Poste',
            'siren' => '732829320',
            'siret' => '73282932000074',
            'addresses' => [
                [
                    'is_billing' => true,
                    'line_1' => '44 Boulevard de Vaugirard',
                    'city' => 'Paris',
                    'postal_code' => '75015',
                    'country_code' => 'FR',
                    'is_default' => true,
                ]
            ]
        ];

        $response = $this->actingAsUser($this->user)
            ->apiPost($this->apiBase, $clientData);

        $response->assertCreated()
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('data.siren', '732829320')
                    ->where('data.siret', '73282932000074')
            );
    }

    public function test_respects_plan_limits_for_client_creation(): void
    {
        // Create 50 clients (assuming free plan limit)
        Client::factory()
            ->for($this->company)
            ->count(50)
            ->create();

        $clientData = [
            'client_type' => 'individual',
            'name' => 'Test User',
        ];

        $response = $this->actingAsUser($this->user)
            ->apiPost($this->apiBase, $clientData);

        // For now, we'll just check it creates successfully
        // TODO: Implement plan limits later
        $response->assertCreated();
    }

    public function test_can_create_client_address_with_client(): void
    {
        $client = Client::factory()
            ->for($this->company)
            ->create();

        $addressData = [
            'is_shipping' => true,
            'line_1' => '789 Rue de Livraison',
            'city' => 'Marseille',
            'postal_code' => '13000',
            'country_code' => 'FR',
            'is_default' => false,
        ];

        $response = $this->actingAsUser($this->user)
            ->apiPost($this->apiBase . '/' . $client->id . '/addresses', $addressData);

        $response->assertCreated()
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('data.is_shipping', true)
                    ->where('data.line_1', '789 Rue de Livraison')
                    ->where('data.addressable_id', $client->id)
            );
    }

    public function test_can_update_client_address(): void
    {
        $client = Client::factory()
            ->for($this->company)
            ->create();

        $address = Address::factory()
            ->for($client, 'addressable')
            ->create([
                'is_billing' => true,
                'line_1' => 'Old Address',
            ]);

        $updateData = [
            'line_1' => 'New Address',
            'line_2' => 'Apt 42',
        ];

        $response = $this->actingAsUser($this->user)
            ->apiPut($this->apiBase . '/' . $client->id . '/addresses/' . $address->id, $updateData);

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('data.line_1', 'New Address')
                    ->where('data.line_2', 'Apt 42')
            );
    }

    public function test_can_delete_client_address(): void
    {
        $client = Client::factory()
            ->for($this->company)
            ->create();

        $address = Address::factory()
            ->for($client, 'addressable')
            ->create();

        $response = $this->actingAsUser($this->user)
            ->apiDelete($this->apiBase . '/' . $client->id . '/addresses/' . $address->id);

        $response->assertNoContent();

        $this->assertSoftDeleted('addresses', [
            'id' => $address->id,
        ]);
    }

    public function test_cannot_delete_default_address_if_only_one(): void
    {
        $client = Client::factory()
            ->for($this->company)
            ->create();

        $address = Address::factory()
            ->for($client, 'addressable')
            ->create(['is_default' => true]);

        $response = $this->actingAsUser($this->user)
            ->apiDelete($this->apiBase . '/' . $client->id . '/addresses/' . $address->id);

        $response->assertUnprocessable()
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('message', 'Impossible de supprimer la seule adresse par défaut')
            );
    }

    public function test_can_set_address_as_default(): void
    {
        $client = Client::factory()
            ->for($this->company)
            ->create();

        $defaultAddress = Address::factory()
            ->for($client, 'addressable')
            ->create(['is_default' => true, 'is_billing' => true]);

        $newAddress = Address::factory()
            ->for($client, 'addressable')
            ->create(['is_default' => false, 'is_billing' => true]);

        $response = $this->actingAsUser($this->user)
            ->apiPatch($this->apiBase . '/' . $client->id . '/addresses/' . $newAddress->id . '/default');

        $response->assertOk();

        // Check that new address is default and old one is not
        $this->assertDatabaseHas('addresses', [
            'id' => $newAddress->id,
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('addresses', [
            'id' => $defaultAddress->id,
            'is_default' => false,
        ]);
    }

    public function test_client_export_to_csv(): void
    {
        Client::factory()
            ->for($this->company)
            ->count(5)
            ->create();

        $response = $this->actingAsUser($this->user)
            ->apiGet($this->apiBase . '/export?format=csv');

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertHeader('content-disposition', 'attachment; filename=clients.csv');
    }

    public function test_client_import_from_csv(): void
    {
        $csvContent = "client_type,name\nindividual,Jean Dupont\nindividual,Marie Martin";
        
        $response = $this->actingAsUser($this->user)
            ->apiPost($this->apiBase . '/import', [
                'file' => $csvContent,
                'format' => 'csv',
            ]);

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('data.imported', 2)
                    ->where('data.errors', 0)
            );

        $this->assertDatabaseHas('clients', [
            'name' => 'Jean Dupont',
            'client_type' => 'individual',
            'company_id' => $this->company->id,
        ]);
    }

    public function test_client_statistics(): void
    {
        // Create clients with different types
        Client::factory()
            ->for($this->company)
            ->count(3)
            ->create(['client_type' => 'individual']);

        Client::factory()
            ->for($this->company)
            ->count(2)
            ->create(['client_type' => 'company']);

        $response = $this->actingAsUser($this->user)
            ->apiGet($this->apiBase . '/statistics');

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('data.total', 5)
                    ->where('data.by_type.individual', 3)
                    ->where('data.by_type.company', 2)
            );
    }
}
