<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use App\Domain\Auth\Models\User;
use Illuminate\Http\UploadedFile;
use App\Domain\Company\Models\Company;
use App\Domain\Customer\Models\Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClientImportExportTest extends TestCase
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
    public function it_can_import_clients_from_csv()
    {
        Storage::fake('local');

        $csvContent = "name,email,phone,type\n" .
                     "John Doe Company,john@example.com,0123456789,client\n" .
                     "Jane Smith Ltd,jane@example.com,0987654321,prospect\n" .
                     "Bob Johnson Inc,bob@example.com,0555666777,client";

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csvContent);

        $response = $this->postJson('/api/v1/clients/import', [
            'file' => $file,
            'skip_header' => true,
            'delimiter' => ',',
            'encoding' => 'UTF-8',
            'mapping' => [
                'name' => 0,
                'email' => 1,
                'phone' => 2,
                'type' => 3,
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'imported',
                    'skipped',
                    'errors',
                ]
            ]);

        $responseData = $response->json('data');
        $this->assertEquals(3, $responseData['imported']);
        $this->assertEquals(0, $responseData['skipped']);
        $this->assertEmpty($responseData['errors']);

        // Vérifier que les clients ont été créés
        $this->assertDatabaseHas('clients', [
            'name' => 'John Doe Company',
            'email' => 'john@example.com',
            'company_id' => $this->company->id,
        ]);

        $this->assertDatabaseHas('clients', [
            'name' => 'Jane Smith Ltd',
            'email' => 'jane@example.com',
            'type' => 'prospect',
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function it_can_import_clients_with_addresses()
    {
        Storage::fake('local');

        $csvContent = "name,email,address_line1,address_postal_code,address_city,address_country\n" .
                     "John Doe Company,john@example.com,123 Main St,75001,Paris,FR\n" .
                     "Jane Smith Ltd,jane@example.com,456 Oak Ave,69001,Lyon,FR";

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csvContent);

        $response = $this->postJson('/api/v1/clients/import', [
            'file' => $file,
            'skip_header' => true,
            'mapping' => [
                'name' => 0,
                'email' => 1,
                'address_line1' => 2,
                'address_postal_code' => 3,
                'address_city' => 4,
                'address_country' => 5,
            ]
        ]);

        $response->assertStatus(200);

        // Vérifier que les adresses ont été créées
        $client = Client::where('name', 'John Doe Company')->first();
        $this->assertNotNull($client);
        $this->assertCount(1, $client->addresses);
        $this->assertEquals('123 Main St', $client->addresses->first()->line1);
    }

    /** @test */
    public function it_validates_import_file_format()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('clients.txt', 100, 'text/plain');

        $response = $this->postJson('/api/v1/clients/import', [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function it_validates_import_file_size()
    {
        Storage::fake('local');

        // Créer un fichier de plus de 5MB
        $file = UploadedFile::fake()->create('clients.csv', 6000, 'text/csv');

        $response = $this->postJson('/api/v1/clients/import', [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function it_handles_invalid_data_during_import()
    {
        Storage::fake('local');

        $csvContent = "name,email,phone\n" .
                     "Valid Company,valid@example.com,0123456789\n" .
                     ",invalid@example.com,0123456789\n" . // Missing name
                     "Another Company,not-an-email,0123456789"; // Invalid email

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csvContent);

        $response = $this->postJson('/api/v1/clients/import', [
            'file' => $file,
            'skip_header' => true,
            'mapping' => [
                'name' => 0,
                'email' => 1,
                'phone' => 2,
            ]
        ]);

        $response->assertStatus(200);

        $responseData = $response->json('data');
        $this->assertEquals(1, $responseData['imported']); // Only the valid one
        $this->assertEquals(2, $responseData['skipped']); // Two invalid rows
        $this->assertCount(2, $responseData['errors']);
    }

    /** @test */
    public function it_can_update_existing_clients_during_import()
    {
        // Créer un client existant
        $existingClient = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Existing Company',
            'email' => 'old@example.com',
        ]);

        Storage::fake('local');

        $csvContent = "name,email,phone\n" .
                     "Existing Company,new@example.com,0123456789";

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csvContent);

        $response = $this->postJson('/api/v1/clients/import', [
            'file' => $file,
            'skip_header' => true,
            'update_existing' => true,
            'mapping' => [
                'name' => 0,
                'email' => 1,
                'phone' => 2,
            ]
        ]);

        $response->assertStatus(200);

        // Vérifier que le client existant a été mis à jour
        $existingClient->refresh();
        $this->assertEquals('new@example.com', $existingClient->email);
        $this->assertEquals('0123456789', $existingClient->phone);
    }

    /** @test */
    public function it_can_export_clients_to_csv()
    {
        // Créer quelques clients
        Client::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->getJson('/api/v1/clients/export?format=csv');

        $response->assertStatus(200)
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertHeader('content-disposition');

        // Vérifier que le contenu contient des données CSV
        $content = $response->getContent();
        $this->assertStringContainsString('name,email,phone', $content);
    }

    /** @test */
    public function it_can_export_clients_to_excel()
    {
        // Créer quelques clients
        Client::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->getJson('/api/v1/clients/export?format=xlsx');

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('content-disposition');
    }

    /** @test */
    public function it_can_export_filtered_clients()
    {
        // Créer des clients de différents types
        Client::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'client',
            'name' => 'Client Company',
        ]);

        Client::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'prospect',
            'name' => 'Prospect Company',
        ]);

        $response = $this->getJson('/api/v1/clients/export?format=csv&type=client');

        $response->assertStatus(200);

        $content = $response->getContent();
        $this->assertStringContainsString('Client Company', $content);
        $this->assertStringNotContainsString('Prospect Company', $content);
    }

    /** @test */
    public function it_requires_file_for_import()
    {
        $response = $this->postJson('/api/v1/clients/import', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function it_validates_delimiter_format()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->createWithContent('clients.csv', 'test');

        $response = $this->postJson('/api/v1/clients/import', [
            'file' => $file,
            'delimiter' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['delimiter']);
    }

    /** @test */
    public function it_validates_encoding_format()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->createWithContent('clients.csv', 'test');

        $response = $this->postJson('/api/v1/clients/import', [
            'file' => $file,
            'encoding' => 'invalid-encoding',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['encoding']);
    }

    /** @test */
    public function it_handles_empty_csv_file()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->createWithContent('clients.csv', '');

        $response = $this->postJson('/api/v1/clients/import', [
            'file' => $file,
        ]);

        $response->assertStatus(200);

        $responseData = $response->json('data');
        $this->assertEquals(0, $responseData['imported']);
        $this->assertEquals(0, $responseData['skipped']);
    }

    /** @test */
    public function it_respects_client_limit_during_import()
    {
        // Créer 49 clients (proche de la limite de 50 pour les comptes gratuits)
        Client::factory()->count(49)->create([
            'company_id' => $this->company->id,
        ]);

        Storage::fake('local');

        $csvContent = "name,email\n" .
                     "Client 1,client1@example.com\n" .
                     "Client 2,client2@example.com\n" .
                     "Client 3,client3@example.com";

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csvContent);

        $response = $this->postJson('/api/v1/clients/import', [
            'file' => $file,
            'skip_header' => true,
            'mapping' => [
                'name' => 0,
                'email' => 1,
            ]
        ]);

        $response->assertStatus(200);

        $responseData = $response->json('data');
        $this->assertEquals(1, $responseData['imported']); // Only one more allowed
        $this->assertEquals(2, $responseData['skipped']); // Two over the limit
    }
}
