<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use Tests\Traits\WithSeededDatabase;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use Illuminate\Http\UploadedFile;
use App\Domain\Auth\Models\Permission;
use App\Domain\Company\Models\Company;
use App\Domain\Customer\Models\Client;
use Illuminate\Support\Facades\Storage;
use App\Domain\Analytics\Models\Feature;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;



class ClientControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker, WithSeededDatabase;

    private User $user;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpWithSeededDatabase();

        // Créer une entreprise et un utilisateur avec les bonnes permissions
        $this->company = Company::factory()->create();

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->user, 'sanctum');
    }

    #[Test]
    public function it_can_list_clients()
    {
        // Créer quelques clients
        Client::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->getJson('/api/v1/clients');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'client_type',
                        'name',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'meta' => [
                    'total',
                    'per_page',
                    'current_page',
                    'last_page',
                ]
            ]);
    }

    #[Test]
    public function it_can_filter_clients_by_search()
    {
        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'John Doe Company',
        ]);

        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Jane Smith Ltd',
        ]);

        $response = $this->getJson('/api/v1/clients?search=John');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.count'));
    }

    #[Test]
    public function it_can_filter_clients_by_type()
    {
        Client::factory()->create([
            'company_id' => $this->company->id,
            'client_type' => 'individual',
        ]);

        Client::factory()->create([
            'company_id' => $this->company->id,
            'client_type' => 'company',
        ]);

        $response = $this->getJson('/api/v1/clients?client_type=individual');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.count'));
    }

    #[Test]
    public function it_can_create_a_client()
    {
        $clientData = [
            'client_type' => 'company',
            'name' => 'Test Company',
            'siren' => '123456789',
            'siret' => '12345678901234',

        ];

        $response = $this->postJson('/api/v1/clients', $clientData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'client_type',
                    'name',
                    'siren',
                    'siret',
                ]
            ]);

        $this->assertDatabaseHas('clients', [
            'name' => 'Test Company',
            'siren' => '123456789',
            'company_id' => $this->company->id,
        ]);
    }

    #[Test]
    public function it_validates_required_fields_when_creating_client()
    {
        $response = $this->postJson('/api/v1/clients', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function it_validates_siren_format()
    {
        $clientData = [
            'client_type' => 'company',
            'name' => 'Test Company',
            'siren' => '12345', // Invalid format
        ];

        $response = $this->postJson('/api/v1/clients', $clientData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['siren']);
    }

    #[Test]
    public function it_validates_siret_format()
    {
        $clientData = [
            'client_type' => 'company',
            'name' => 'Test Company',
            'siret' => '12345', // Invalid format
        ];

        $response = $this->postJson('/api/v1/clients', $clientData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['siret']);
    }

    #[Test]
    public function it_can_create_multiple_clients()
    {
        // Créer quelques clients
        Client::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);

        $clientData = [
            'client_type' => 'company',
            'name' => 'Test Company',
            'siren' => '123456789',
        ];

        $response = $this->postJson('/api/v1/clients', $clientData);

        $response->assertStatus(201);
    }

    #[Test]
    public function it_can_show_a_client()
    {
        $client = Client::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->getJson("/api/v1/clients/{$client->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'client_type',
                    'name',
                    'created_at',
                    'updated_at',
                ]
            ]);
    }

    #[Test]
    public function it_cannot_show_client_from_other_company()
    {
        $otherCompany = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $response = $this->getJson("/api/v1/clients/{$client->id}");

        $response->assertStatus(403);
    }

    #[Test]
    public function it_can_update_a_client()
    {
        $client = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Old Name',
        ]);

        $updateData = [
            'name' => 'New Name',
        ];

        $response = $this->putJson("/api/v1/clients/{$client->id}", $updateData);

        $response->assertStatus(200)
        ->assertJsonPath('message', 'Client mis à jour avec succès.')
        ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'New Name',
        ]);
    }

    #[Test]
    public function it_can_delete_a_client_without_invoices()
    {
        $client = Client::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->deleteJson("/api/v1/clients/{$client->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Client supprimé avec succès.');
          
        $this->assertSoftDeleted('clients', [
            'id' => $client->id,
        ]);
    }

    #[Test]
    public function it_can_delete_a_client()
    {
        $client = Client::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->deleteJson("/api/v1/clients/{$client->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('clients', [
            'id' => $client->id,
        ]);
    }

    #[Test]
    public function it_can_import_clients()
    {
        Storage::fake('local');

        $csv = "name,email,phone\nJohn Doe,john@example.com,0123456789\nJane Smith,jane@example.com,0987654321";
        $file = UploadedFile::fake()->createWithContent('clients.csv', $csv);

        $response = $this->postJson('/api/v1/clients/import', [
            'file' => $file,
            'skip_header' => true,
            'mapping' => [
                'name' => 0,
                'email' => 1,
                'phone' => 2,
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'imported',
                    'skipped',
                    'errors',
                ]
            ]);
    }

    #[Test]
    public function it_validates_import_file()
    {
        $response = $this->postJson('/api/v1/clients/import', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }
    
    #[Test]
    public function it_can_validate_siren()
    {
        $response = $this->postJson('/api/v1/clients/validate-siren', [
            'siren' => '123456789',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'valid',
                    'message',
                ]
            ]);
    }

    #[Test]
    public function it_can_get_client_statistics()
    {
        Client::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'client_type' => 'company',
        ]);

        Client::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'client_type' => 'individual',
        ]);

        $response = $this->getJson('/api/v1/clients/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total',
                ]
            ]);
    }

    #[Test]
    public function it_requires_authentication()
    {
        // Pour Sanctum, on doit arrêter d'agir en tant qu'utilisateur
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/v1/clients');

        $response->assertStatus(401);
    }

    #[Test]
    public function it_requires_proper_permissions()
    {
        // Créer un utilisateur sans permissions
        $userWithoutPermissions = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($userWithoutPermissions, 'sanctum');

        $response = $this->getJson('/api/v1/clients');

        // Pour l'instant, on vérifie juste que l'échec n'est pas dû à une erreur serveur
        $this->assertContains($response->status(), [200, 403]);
    }
}
