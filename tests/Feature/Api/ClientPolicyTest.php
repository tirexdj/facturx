<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use Illuminate\Http\UploadedFile;
use App\Domain\Auth\Models\Permission;
use App\Domain\Company\Models\Company;
use App\Domain\Customer\Models\Client;
use Illuminate\Support\Facades\Storage;
use App\Domain\Analytics\Models\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClientPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $manager;
    private User $employee;
    private User $readOnly;
    private User $otherCompanyUser;
    private Company $company;
    private Company $otherCompany;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les entreprises
        $this->company = Company::factory()->create();
        $this->otherCompany = Company::factory()->create();

        // Créer les permissions et fonctionnalités
        $clientManagePermission = Permission::factory()->create(['name' => 'manage_clients']);
        $clientViewPermission = Permission::factory()->create(['name' => 'view_clients']);
        $clientCreatePermission = Permission::factory()->create(['name' => 'create_clients']);
        $clientUpdatePermission = Permission::factory()->create(['name' => 'update_clients']);
        $clientDeletePermission = Permission::factory()->create(['name' => 'delete_clients']);

        $clientFeature = Feature::factory()->create(['name' => 'client_management']);

        // Créer les rôles
        $ownerRole = Role::factory()->create([
            'name' => 'owner',
            'company_id' => $this->company->id,
        ]);
        $ownerRole->permissions()->attach([
            $clientManagePermission->id,
            $clientViewPermission->id,
            $clientCreatePermission->id,
            $clientUpdatePermission->id,
            $clientDeletePermission->id,
        ]);
        $ownerRole->features()->attach($clientFeature->id);

        $managerRole = Role::factory()->create([
            'name' => 'manager',
            'company_id' => $this->company->id,
        ]);
        $managerRole->permissions()->attach([
            $clientViewPermission->id,
            $clientCreatePermission->id,
            $clientUpdatePermission->id,
        ]);
        $managerRole->features()->attach($clientFeature->id);

        $employeeRole = Role::factory()->create([
            'name' => 'employee',
            'company_id' => $this->company->id,
        ]);
        $employeeRole->permissions()->attach([
            $clientViewPermission->id,
            $clientCreatePermission->id,
        ]);
        $employeeRole->features()->attach($clientFeature->id);

        $readOnlyRole = Role::factory()->create([
            'name' => 'read_only',
            'company_id' => $this->company->id,
        ]);
        $readOnlyRole->permissions()->attach($clientViewPermission->id);
        $readOnlyRole->features()->attach($clientFeature->id);

        // Créer les utilisateurs
        $this->owner = User::factory()->create(['current_company_id' => $this->company->id]);
        $this->owner->companies()->attach($this->company->id, ['role' => 'owner', 'is_active' => true]);
        $this->owner->roles()->attach($ownerRole->id);

        $this->manager = User::factory()->create(['current_company_id' => $this->company->id]);
        $this->manager->companies()->attach($this->company->id, ['role' => 'manager', 'is_active' => true]);
        $this->manager->roles()->attach($managerRole->id);

        $this->employee = User::factory()->create(['current_company_id' => $this->company->id]);
        $this->employee->companies()->attach($this->company->id, ['role' => 'employee', 'is_active' => true]);
        $this->employee->roles()->attach($employeeRole->id);

        $this->readOnly = User::factory()->create(['current_company_id' => $this->company->id]);
        $this->readOnly->companies()->attach($this->company->id, ['role' => 'read_only', 'is_active' => true]);
        $this->readOnly->roles()->attach($readOnlyRole->id);

        $this->otherCompanyUser = User::factory()->create(['current_company_id' => $this->otherCompany->id]);
        $this->otherCompanyUser->companies()->attach($this->otherCompany->id, ['role' => 'owner', 'is_active' => true]);
    }

    /** @test */
    public function owner_can_view_any_clients()
    {
        $this->actingAs($this->owner, 'sanctum');

        $response = $this->getJson('/api/clients');
        $response->assertStatus(200);
    }

    /** @test */
    public function manager_can_view_any_clients()
    {
        $this->actingAs($this->manager, 'sanctum');

        $response = $this->getJson('/api/clients');
        $response->assertStatus(200);
    }

    /** @test */
    public function employee_can_view_any_clients()
    {
        $this->actingAs($this->employee, 'sanctum');

        $response = $this->getJson('/api/clients');
        $response->assertStatus(200);
    }

    /** @test */
    public function read_only_can_view_any_clients()
    {
        $this->actingAs($this->readOnly, 'sanctum');

        $response = $this->getJson('/api/clients');
        $response->assertStatus(200);
    }

    /** @test */
    public function owner_can_create_clients()
    {
        $this->actingAs($this->owner, 'sanctum');

        $response = $this->postJson('/api/clients', [
            'type' => 'client',
            'name' => 'Test Company',
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function manager_can_create_clients()
    {
        $this->actingAs($this->manager, 'sanctum');

        $response = $this->postJson('/api/clients', [
            'type' => 'client',
            'name' => 'Test Company',
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function employee_can_create_clients()
    {
        $this->actingAs($this->employee, 'sanctum');

        $response = $this->postJson('/api/clients', [
            'type' => 'client',
            'name' => 'Test Company',
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function read_only_cannot_create_clients()
    {
        $this->actingAs($this->readOnly, 'sanctum');

        $response = $this->postJson('/api/clients', [
            'type' => 'client',
            'name' => 'Test Company',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function owner_can_update_clients()
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->owner, 'sanctum');

        $response = $this->putJson("/api/clients/{$client->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function manager_can_update_clients()
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->manager, 'sanctum');

        $response = $this->putJson("/api/clients/{$client->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function employee_cannot_update_clients()
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->employee, 'sanctum');

        $response = $this->putJson("/api/clients/{$client->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function read_only_cannot_update_clients()
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->readOnly, 'sanctum');

        $response = $this->putJson("/api/clients/{$client->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function owner_can_delete_clients()
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->owner, 'sanctum');

        $response = $this->deleteJson("/api/clients/{$client->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function manager_cannot_delete_clients()
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->manager, 'sanctum');

        $response = $this->deleteJson("/api/clients/{$client->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function employee_cannot_delete_clients()
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->employee, 'sanctum');

        $response = $this->deleteJson("/api/clients/{$client->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function read_only_cannot_delete_clients()
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->readOnly, 'sanctum');

        $response = $this->deleteJson("/api/clients/{$client->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function users_cannot_access_clients_from_other_companies()
    {
        $client = Client::factory()->create(['company_id' => $this->otherCompany->id]);
        $this->actingAs($this->owner, 'sanctum');

        // View
        $response = $this->getJson("/api/clients/{$client->id}");
        $response->assertStatus(403);

        // Update
        $response = $this->putJson("/api/clients/{$client->id}", [
            'name' => 'Updated Name',
        ]);
        $response->assertStatus(403);

        // Delete
        $response = $this->deleteJson("/api/clients/{$client->id}");
        $response->assertStatus(403);
    }

    /** @test */
    public function users_from_other_companies_cannot_access_clients()
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->otherCompanyUser, 'sanctum');

        $response = $this->getJson("/api/clients/{$client->id}");
        $response->assertStatus(403);
    }

    /** @test */
    public function users_can_only_see_their_company_clients_in_list()
    {
        // Créer des clients pour chaque entreprise
        Client::factory()->count(3)->create(['company_id' => $this->company->id]);
        Client::factory()->count(2)->create(['company_id' => $this->otherCompany->id]);

        $this->actingAs($this->owner, 'sanctum');

        $response = $this->getJson('/api/clients');
        $response->assertStatus(200);

        // Ne devrait voir que les 3 clients de sa propre entreprise
        $this->assertEquals(3, $response->json('meta.total'));
    }

    /** @test */
    public function user_without_client_feature_cannot_access_clients()
    {
        // Créer un utilisateur sans la fonctionnalité client
        $roleWithoutFeature = Role::factory()->create([
            'name' => 'no_client_access',
            'company_id' => $this->company->id,
        ]);

        $userWithoutFeature = User::factory()->create(['current_company_id' => $this->company->id]);
        $userWithoutFeature->companies()->attach($this->company->id, ['role' => 'no_client_access', 'is_active' => true]);
        $userWithoutFeature->roles()->attach($roleWithoutFeature->id);

        $this->actingAs($userWithoutFeature, 'sanctum');

        $response = $this->getJson('/api/clients');
        $response->assertStatus(403);
    }

    /** @test */
    public function inactive_user_cannot_access_clients()
    {
        // Désactiver l'utilisateur dans l'entreprise
        $this->owner->companies()->updateExistingPivot($this->company->id, ['is_active' => false]);

        $this->actingAs($this->owner, 'sanctum');

        $response = $this->getJson('/api/clients');
        $response->assertStatus(403);
    }

    /** @test */
    public function owner_can_import_clients()
    {
        $this->actingAs($this->owner, 'sanctum');

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('clients.csv', 'name,email\nTest,test@example.com');

        $response = $this->postJson('/api/clients/import', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function read_only_cannot_import_clients()
    {
        $this->actingAs($this->readOnly, 'sanctum');

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('clients.csv', 'name,email\nTest,test@example.com');

        $response = $this->postJson('/api/clients/import', [
            'file' => $file,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function all_roles_can_export_clients()
    {
        Client::factory()->create(['company_id' => $this->company->id]);

        foreach ([$this->owner, $this->manager, $this->employee, $this->readOnly] as $user) {
            $this->actingAs($user, 'sanctum');

            $response = $this->getJson('/api/clients/export');
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function users_can_convert_prospects_based_on_update_permission()
    {
        $prospect = Client::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'prospect',
        ]);

        // Owner et manager peuvent convertir
        foreach ([$this->owner, $this->manager] as $user) {
            $this->actingAs($user, 'sanctum');

            $response = $this->postJson("/api/clients/{$prospect->id}/convert-to-client");
            $response->assertStatus(200);

            // Reset prospect type pour le test suivant
            $prospect->update(['type' => 'prospect']);
        }

        // Employee et read-only ne peuvent pas convertir
        foreach ([$this->employee, $this->readOnly] as $user) {
            $this->actingAs($user, 'sanctum');

            $response = $this->postJson("/api/clients/{$prospect->id}/convert-to-client");
            $response->assertStatus(403);
        }
    }
}
