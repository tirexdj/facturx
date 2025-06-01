<?php

namespace Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Company;
use App\Models\Client;
use App\Models\ClientCategory;
use App\Services\ClientExportService;

class ClientExportServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private ClientExportService $exportService;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exportService = app(ClientExportService::class);
        $this->company = Company::factory()->create();
    }

    /** @test */
    public function it_can_export_clients_to_csv()
    {
        // Créer des clients de test
        $category = ClientCategory::factory()->create(['company_id' => $this->company->id]);
        
        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Company 1',
            'email' => 'test1@example.com',
            'phone' => '0123456789',
            'type' => 'client',
            'category_id' => $category->id,
        ]);

        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Company 2',
            'email' => 'test2@example.com',
            'phone' => '0987654321',
            'type' => 'prospect',
        ]);

        $result = $this->exportService->exportClients($this->company->id, [], 'csv');

        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('content_type', $result);
        $this->assertArrayHasKey('filename', $result);

        $this->assertEquals('text/csv; charset=UTF-8', $result['content_type']);
        $this->assertStringEndsWith('.csv', $result['filename']);

        // Vérifier le contenu CSV
        $lines = explode("\n", $result['content']);
        $this->assertStringContainsString('name,email,phone', $lines[0]); // Header
        $this->assertStringContainsString('Test Company 1', $result['content']);
        $this->assertStringContainsString('Test Company 2', $result['content']);
    }

    /** @test */
    public function it_can_export_clients_to_excel()
    {
        Client::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);

        $result = $this->exportService->exportClients($this->company->id, [], 'xlsx');

        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('content_type', $result);
        $this->assertArrayHasKey('filename', $result);

        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $result['content_type']);
        $this->assertStringEndsWith('.xlsx', $result['filename']);
    }

    /** @test */
    public function it_can_filter_exported_clients_by_type()
    {
        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Client Company',
            'type' => 'client',
        ]);

        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Prospect Company',
            'type' => 'prospect',
        ]);

        // Export seulement les clients
        $result = $this->exportService->exportClients($this->company->id, ['type' => 'client'], 'csv');

        $this->assertStringContainsString('Client Company', $result['content']);
        $this->assertStringNotContainsString('Prospect Company', $result['content']);
    }

    /** @test */
    public function it_can_filter_exported_clients_by_status()
    {
        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Active Company',
            'status' => 'active',
        ]);

        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Inactive Company',
            'status' => 'inactive',
        ]);

        // Export seulement les clients actifs
        $result = $this->exportService->exportClients($this->company->id, ['status' => 'active'], 'csv');

        $this->assertStringContainsString('Active Company', $result['content']);
        $this->assertStringNotContainsString('Inactive Company', $result['content']);
    }

    /** @test */
    public function it_can_filter_exported_clients_by_category()
    {
        $category1 = ClientCategory::factory()->create(['company_id' => $this->company->id]);
        $category2 = ClientCategory::factory()->create(['company_id' => $this->company->id]);

        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Category 1 Client',
            'category_id' => $category1->id,
        ]);

        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Category 2 Client',
            'category_id' => $category2->id,
        ]);

        // Export seulement les clients de la catégorie 1
        $result = $this->exportService->exportClients($this->company->id, ['category_id' => $category1->id], 'csv');

        $this->assertStringContainsString('Category 1 Client', $result['content']);
        $this->assertStringNotContainsString('Category 2 Client', $result['content']);
    }

    /** @test */
    public function it_can_filter_exported_clients_by_search()
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

        // Export seulement les clients correspondant à la recherche
        $result = $this->exportService->exportClients($this->company->id, ['search' => 'John'], 'csv');

        $this->assertStringContainsString('John Doe Company', $result['content']);
        $this->assertStringNotContainsString('Jane Smith Ltd', $result['content']);
    }

    /** @test */
    public function it_includes_all_relevant_client_data_in_export()
    {
        $category = ClientCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Category',
        ]);

        $client = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Complete Client',
            'email' => 'complete@example.com',
            'phone' => '0123456789',
            'mobile' => '0987654321',
            'website' => 'https://example.com',
            'siren' => '123456789',
            'siret' => '12345678901234',
            'vat_number' => 'FR12345678901',
            'type' => 'client',
            'status' => 'active',
            'category_id' => $category->id,
            'payment_terms' => 45,
            'payment_method' => 'check',
            'notes' => 'Important client',
        ]);

        $result = $this->exportService->exportClients($this->company->id, [], 'csv');

        // Vérifier que toutes les données importantes sont présentes
        $content = $result['content'];
        $this->assertStringContainsString('Complete Client', $content);
        $this->assertStringContainsString('complete@example.com', $content);
        $this->assertStringContainsString('0123456789', $content);
        $this->assertStringContainsString('123456789', $content);
        $this->assertStringContainsString('client', $content);
        $this->assertStringContainsString('active', $content);
    }

    /** @test */
    public function it_includes_addresses_in_export()
    {
        $client = Client::factory()->hasAddresses(1, [
            'type' => 'billing',
            'line1' => '123 Main Street',
            'city' => 'Paris',
            'postal_code' => '75001',
            'country' => 'FR',
        ])->create([
            'company_id' => $this->company->id,
        ]);

        $result = $this->exportService->exportClients($this->company->id, [], 'csv');

        $this->assertStringContainsString('123 Main Street', $result['content']);
        $this->assertStringContainsString('Paris', $result['content']);
        $this->assertStringContainsString('75001', $result['content']);
    }

    /** @test */
    public function it_includes_contacts_in_export()
    {
        $client = Client::factory()->hasContacts(1, [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '0111111111',
            'position' => 'Manager',
        ])->create([
            'company_id' => $this->company->id,
        ]);

        $result = $this->exportService->exportClients($this->company->id, [], 'csv');

        $this->assertStringContainsString('John Doe', $result['content']);
        $this->assertStringContainsString('john.doe@example.com', $result['content']);
        $this->assertStringContainsString('Manager', $result['content']);
    }

    /** @test */
    public function it_generates_appropriate_filename()
    {
        Client::factory()->create(['company_id' => $this->company->id]);

        $result = $this->exportService->exportClients($this->company->id, [], 'csv');

        $this->assertStringContainsString('clients_export_', $result['filename']);
        $this->assertStringEndsWith('.csv', $result['filename']);

        // Test Excel filename
        $result = $this->exportService->exportClients($this->company->id, [], 'xlsx');
        $this->assertStringEndsWith('.xlsx', $result['filename']);
    }

    /** @test */
    public function it_handles_empty_client_list()
    {
        $result = $this->exportService->exportClients($this->company->id, [], 'csv');

        $this->assertArrayHasKey('content', $result);
        
        // Should still have headers even with no data
        $lines = explode("\n", $result['content']);
        $this->assertStringContainsString('name,email,phone', $lines[0]);
    }

    /** @test */
    public function it_only_exports_clients_from_specified_company()
    {
        $otherCompany = Company::factory()->create();

        // Client de notre entreprise
        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Our Company Client',
        ]);

        // Client d'une autre entreprise
        Client::factory()->create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Company Client',
        ]);

        $result = $this->exportService->exportClients($this->company->id, [], 'csv');

        $this->assertStringContainsString('Our Company Client', $result['content']);
        $this->assertStringNotContainsString('Other Company Client', $result['content']);
    }

    /** @test */
    public function it_properly_escapes_csv_data()
    {
        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Company, With Comma',
            'notes' => 'Notes with "quotes" and, commas',
        ]);

        $result = $this->exportService->exportClients($this->company->id, [], 'csv');

        // Les données avec des virgules et guillemets devraient être correctement échappées
        $this->assertStringContainsString('"Company, With Comma"', $result['content']);
        $this->assertStringContainsString('"Notes with ""quotes"" and, commas"', $result['content']);
    }

    /** @test */
    public function it_handles_clients_with_special_characters()
    {
        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Société Française',
            'notes' => 'Notes avec des accents: àáâãäåæçèéêë',
        ]);

        $result = $this->exportService->exportClients($this->company->id, [], 'csv');

        $this->assertStringContainsString('Société Française', $result['content']);
        $this->assertStringContainsString('àáâãäåæçèéêë', $result['content']);
    }

    /** @test */
    public function it_uses_utf8_encoding_for_csv()
    {
        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test UTF-8: 中文',
        ]);

        $result = $this->exportService->exportClients($this->company->id, [], 'csv');

        $this->assertEquals('text/csv; charset=UTF-8', $result['content_type']);
        $this->assertStringContainsString('中文', $result['content']);
    }
}
