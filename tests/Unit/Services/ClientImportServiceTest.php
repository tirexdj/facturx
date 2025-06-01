<?php

namespace Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\Company;
use App\Models\Client;
use App\Services\ClientImportService;
use App\Services\ClientService;
use Mockery;

class ClientImportServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private ClientImportService $importService;
    private ClientService $clientService;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientService = Mockery::mock(ClientService::class);
        $this->importService = new ClientImportService($this->clientService);
        $this->company = Company::factory()->create();

        Storage::fake('local');
    }

    /** @test */
    public function it_can_import_clients_from_csv()
    {
        $csvContent = "name,email,phone,type\n" .
                     "John Doe Company,john@example.com,0123456789,client\n" .
                     "Jane Smith Ltd,jane@example.com,0987654321,prospect";

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csvContent);

        // Mock the client service
        $this->clientService->shouldReceive('checkClientLimit')->twice();
        $this->clientService->shouldReceive('createClient')
            ->twice()
            ->andReturn(new Client());

        $result = $this->importService->importClients($file, $this->company->id, [
            'skip_header' => true,
            'delimiter' => ',',
            'mapping' => [
                'name' => 0,
                'email' => 1,
                'phone' => 2,
                'type' => 3,
            ]
        ]);

        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertEmpty($result['errors']);
    }

    /** @test */
    public function it_can_import_clients_with_addresses()
    {
        $csvContent = "name,email,address_line1,address_postal_code,address_city,address_country\n" .
                     "Test Company,test@example.com,123 Main St,75001,Paris,FR";

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csvContent);

        $this->clientService->shouldReceive('checkClientLimit')->once();
        $this->clientService->shouldReceive('createClient')
            ->once()
            ->withArgs(function ($companyId, $data) {
                return isset($data['addresses']) && 
                       count($data['addresses']) === 1 &&
                       $data['addresses'][0]['line1'] === '123 Main St' &&
                       $data['addresses'][0]['postal_code'] === '75001';
            })
            ->andReturn(new Client());

        $result = $this->importService->importClients($file, $this->company->id, [
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

        $this->assertEquals(1, $result['imported']);
    }

    /** @test */
    public function it_handles_invalid_data()
    {
        $csvContent = "name,email,phone\n" .
                     "Valid Company,valid@example.com,0123456789\n" .
                     ",invalid@example.com,0123456789\n" . // Missing name
                     "Another Company,not-an-email,0123456789"; // Invalid email

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csvContent);

        $this->clientService->shouldReceive('checkClientLimit')->once();
        $this->clientService->shouldReceive('createClient')
            ->once()
            ->andReturn(new Client());

        $result = $this->importService->importClients($file, $this->company->id, [
            'skip_header' => true,
            'mapping' => [
                'name' => 0,
                'email' => 1,
                'phone' => 2,
            ]
        ]);

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(2, $result['skipped']);
        $this->assertCount(2, $result['errors']);
    }

    /** @test */
    public function it_can_update_existing_clients()
    {
        // Créer un client existant
        $existingClient = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Existing Company',
            'email' => 'old@example.com',
        ]);

        $csvContent = "name,email,phone\n" .
                     "Existing Company,new@example.com,0123456789";

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csvContent);

        $this->clientService->shouldReceive('updateClient')
            ->once()
            ->withArgs(function ($client, $data) use ($existingClient) {
                return $client->id === $existingClient->id && 
                       $data['email'] === 'new@example.com';
            })
            ->andReturn($existingClient);

        $result = $this->importService->importClients($file, $this->company->id, [
            'skip_header' => true,
            'update_existing' => true,
            'mapping' => [
                'name' => 0,
                'email' => 1,
                'phone' => 2,
            ]
        ]);

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(0, $result['skipped']);
    }

    /** @test */
    public function it_skips_existing_clients_when_update_not_enabled()
    {
        // Créer un client existant
        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Existing Company',
            'email' => 'existing@example.com',
        ]);

        $csvContent = "name,email,phone\n" .
                     "Existing Company,existing@example.com,0123456789";

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csvContent);

        $result = $this->importService->importClients($file, $this->company->id, [
            'skip_header' => true,
            'update_existing' => false,
            'mapping' => [
                'name' => 0,
                'email' => 1,
                'phone' => 2,
            ]
        ]);

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(1, $result['skipped']);
    }

    /** @test */
    public function it_handles_different_delimiters()
    {
        $csvContent = "name;email;phone\n" .
                     "Test Company;test@example.com;0123456789";

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csvContent);

        $this->clientService->shouldReceive('checkClientLimit')->once();
        $this->clientService->shouldReceive('createClient')
            ->once()
            ->andReturn(new Client());

        $result = $this->importService->importClients($file, $this->company->id, [
            'skip_header' => true,
            'delimiter' => ';',
            'mapping' => [
                'name' => 0,
                'email' => 1,
                'phone' => 2,
            ]
        ]);

        $this->assertEquals(1, $result['imported']);
    }

    /** @test */
    public function it_handles_different_encodings()
    {
        // Créer un contenu avec des caractères accentués
        $csvContent = "name,email\n" .
                     "Société Française,societe@example.com";

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csvContent);

        $this->clientService->shouldReceive('checkClientLimit')->once();
        $this->clientService->shouldReceive('createClient')
            ->once()
            ->withArgs(function ($companyId, $data) {
                return $data['name'] === 'Société Française';
            })
            ->andReturn(new Client());

        $result = $this->importService->importClients($file, $this->company->id, [
            'skip_header' => true,
            'encoding' => 'UTF-8',
            'mapping' => [
                'name' => 0,
                'email' => 1,
            ]
        ]);

        $this->assertEquals(1, $result['imported']);
    }

    /** @test */
    public function it_validates_required_mapping()
    {
        $csvContent = "name,email\nTest,test@example.com";
        $file = UploadedFile::fake()->createWithContent('clients.csv', $csvContent);

        $result = $this->importService->importClients($file, $this->company->id, [
            'skip_header' => true,
            'mapping' => [
                'email' => 1, // Name mapping missing
            ]
        ]);

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(1, $result['skipped']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('name', $result['errors'][0]['message']);
    }

    /** @test */
    public function it_handles_empty_file()
    {
        $file = UploadedFile::fake()->createWithContent('clients.csv', '');

        $result = $this->importService->importClients($file, $this->company->id, []);

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertEmpty($result['errors']);
    }

    /** @test */
    public function it_handles_file_without_header_when_skip_header_is_false()
    {
        $csvContent = "Test Company,test@example.com,0123456789";

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csvContent);

        $this->clientService->shouldReceive('checkClientLimit')->once();
        $this->clientService->shouldReceive('createClient')
            ->once()
            ->andReturn(new Client());

        $result = $this->importService->importClients($file, $this->company->id, [
            'skip_header' => false,
            'mapping' => [
                'name' => 0,
                'email' => 1,
                'phone' => 2,
            ]
        ]);

        $this->assertEquals(1, $result['imported']);
    }

    /** @test */
    public function it_respects_client_limit_during_import()
    {
        $csvContent = "name,email\n" .
                     "Client 1,client1@example.com\n" .
                     "Client 2,client2@example.com";

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csvContent);

        // Mock client service to throw exception on second client
        $this->clientService->shouldReceive('checkClientLimit')
            ->once()
            ->andThrow(new \App\Exceptions\ClientLimitExceededException('Limit exceeded'));

        $this->clientService->shouldReceive('createClient')
            ->once()
            ->andReturn(new Client());

        $result = $this->importService->importClients($file, $this->company->id, [
            'skip_header' => true,
            'mapping' => [
                'name' => 0,
                'email' => 1,
            ]
        ]);

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(1, $result['skipped']);
        $this->assertCount(1, $result['errors']);
    }

    /** @test */
    public function it_cleans_and_validates_siren_siret()
    {
        $csvContent = "name,siren,siret\n" .
                     "Test Company,123 456 789,12345678901234";

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csvContent);

        $this->clientService->shouldReceive('checkClientLimit')->once();
        $this->clientService->shouldReceive('createClient')
            ->once()
            ->withArgs(function ($companyId, $data) {
                return $data['siren'] === '123456789' && 
                       $data['siret'] === '12345678901234';
            })
            ->andReturn(new Client());

        $result = $this->importService->importClients($file, $this->company->id, [
            'skip_header' => true,
            'mapping' => [
                'name' => 0,
                'siren' => 1,
                'siret' => 2,
            ]
        ]);

        $this->assertEquals(1, $result['imported']);
    }

    /** @test */
    public function it_provides_detailed_error_information()
    {
        $csvContent = "name,email\n" .
                     ",invalid-email\n" . // Missing name and invalid email
                     "Valid Company,valid@example.com";

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csvContent);

        $this->clientService->shouldReceive('checkClientLimit')->once();
        $this->clientService->shouldReceive('createClient')
            ->once()
            ->andReturn(new Client());

        $result = $this->importService->importClients($file, $this->company->id, [
            'skip_header' => true,
            'mapping' => [
                'name' => 0,
                'email' => 1,
            ]
        ]);

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(1, $result['skipped']);
        $this->assertCount(1, $result['errors']);

        $error = $result['errors'][0];
        $this->assertEquals(2, $error['row']); // Row number in file (after header)
        $this->assertArrayHasKey('message', $error);
        $this->assertArrayHasKey('data', $error);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
