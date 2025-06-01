<?php

namespace Tests\Unit\Policies;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Policies\ClientPolicy;
use App\Domain\Company\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\Customer\Models\Client;

class ClientPolicyTest extends TestCase
{
    use RefreshDatabase;

    private ClientPolicy $policy;
    private Company $company;
    private Company $otherCompany;
    private User $user;
    private User $adminUser;
    private User $readOnlyUser;
    private User $otherCompanyUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ClientPolicy();
        
        $this->company = Company::factory()->create();
        $this->otherCompany = Company::factory()->create();
        
        $this->user = User::factory()
            ->for($this->company)
            ->create(['role' => 'user']);
            
        $this->adminUser = User::factory()
            ->for($this->company)
            ->create(['role' => 'admin']);
            
        $this->readOnlyUser = User::factory()
            ->for($this->company)
            ->create(['role' => 'readonly']);
            
        $this->otherCompanyUser = User::factory()
            ->for($this->otherCompany)
            ->create(['role' => 'user']);
    }

    /** @test */
    public function user_can_view_any_clients_from_same_company()
    {
        $this->assertTrue($this->policy->viewAny($this->user));
        $this->assertTrue($this->policy->viewAny($this->adminUser));
        $this->assertTrue($this->policy->viewAny($this->readOnlyUser));
    }

    /** @test */
    public function user_can_view_client_from_same_company()
    {
        $client = Client::factory()->for($this->company)->create();

        $this->assertTrue($this->policy->view($this->user, $client));
        $this->assertTrue($this->policy->view($this->adminUser, $client));
        $this->assertTrue($this->policy->view($this->readOnlyUser, $client));
    }

    /** @test */
    public function user_cannot_view_client_from_different_company()
    {
        $client = Client::factory()->for($this->otherCompany)->create();

        $this->assertFalse($this->policy->view($this->user, $client));
        $this->assertFalse($this->policy->view($this->adminUser, $client));
        $this->assertFalse($this->policy->view($this->readOnlyUser, $client));
    }

    /** @test */
    public function user_can_create_client_in_same_company()
    {
        $this->assertTrue($this->policy->create($this->user));
        $this->assertTrue($this->policy->create($this->adminUser));
    }

    /** @test */
    public function readonly_user_cannot_create_client()
    {
        $this->assertFalse($this->policy->create($this->readOnlyUser));
    }

    /** @test */
    public function user_can_update_client_from_same_company()
    {
        $client = Client::factory()->for($this->company)->create();

        $this->assertTrue($this->policy->update($this->user, $client));
        $this->assertTrue($this->policy->update($this->adminUser, $client));
    }

    /** @test */
    public function user_cannot_update_client_from_different_company()
    {
        $client = Client::factory()->for($this->otherCompany)->create();

        $this->assertFalse($this->policy->update($this->user, $client));
        $this->assertFalse($this->policy->update($this->adminUser, $client));
    }

    /** @test */
    public function readonly_user_cannot_update_client()
    {
        $client = Client::factory()->for($this->company)->create();

        $this->assertFalse($this->policy->update($this->readOnlyUser, $client));
    }

    /** @test */
    public function user_can_delete_client_from_same_company()
    {
        $client = Client::factory()->for($this->company)->create();

        $this->assertTrue($this->policy->delete($this->user, $client));
        $this->assertTrue($this->policy->delete($this->adminUser, $client));
    }

    /** @test */
    public function user_cannot_delete_client_from_different_company()
    {
        $client = Client::factory()->for($this->otherCompany)->create();

        $this->assertFalse($this->policy->delete($this->user, $client));
        $this->assertFalse($this->policy->delete($this->adminUser, $client));
    }

    /** @test */
    public function readonly_user_cannot_delete_client()
    {
        $client = Client::factory()->for($this->company)->create();

        $this->assertFalse($this->policy->delete($this->readOnlyUser, $client));
    }

    /** @test */
    public function user_can_restore_soft_deleted_client_from_same_company()
    {
        $client = Client::factory()->for($this->company)->create();
        $client->delete();

        $this->assertTrue($this->policy->restore($this->user, $client));
        $this->assertTrue($this->policy->restore($this->adminUser, $client));
    }

    /** @test */
    public function user_cannot_restore_client_from_different_company()
    {
        $client = Client::factory()->for($this->otherCompany)->create();
        $client->delete();

        $this->assertFalse($this->policy->restore($this->user, $client));
        $this->assertFalse($this->policy->restore($this->adminUser, $client));
    }

    /** @test */
    public function readonly_user_cannot_restore_client()
    {
        $client = Client::factory()->for($this->company)->create();
        $client->delete();

        $this->assertFalse($this->policy->restore($this->readOnlyUser, $client));
    }

    /** @test */
    public function admin_can_force_delete_client()
    {
        $client = Client::factory()->for($this->company)->create();

        $this->assertTrue($this->policy->forceDelete($this->adminUser, $client));
    }

    /** @test */
    public function regular_user_cannot_force_delete_client()
    {
        $client = Client::factory()->for($this->company)->create();

        $this->assertFalse($this->policy->forceDelete($this->user, $client));
        $this->assertFalse($this->policy->forceDelete($this->readOnlyUser, $client));
    }

    /** @test */
    public function user_cannot_force_delete_client_from_different_company()
    {
        $client = Client::factory()->for($this->otherCompany)->create();

        $this->assertFalse($this->policy->forceDelete($this->adminUser, $client));
    }

    /** @test */
    public function user_can_export_clients_from_same_company()
    {
        $this->assertTrue($this->policy->export($this->user));
        $this->assertTrue($this->policy->export($this->adminUser));
        $this->assertTrue($this->policy->export($this->readOnlyUser));
    }

    /** @test */
    public function user_can_import_clients_to_same_company()
    {
        $this->assertTrue($this->policy->import($this->user));
        $this->assertTrue($this->policy->import($this->adminUser));
    }

    /** @test */
    public function readonly_user_cannot_import_clients()
    {
        $this->assertFalse($this->policy->import($this->readOnlyUser));
    }

    /** @test */
    public function user_can_bulk_delete_clients_from_same_company()
    {
        $this->assertTrue($this->policy->bulkDelete($this->user));
        $this->assertTrue($this->policy->bulkDelete($this->adminUser));
    }

    /** @test */
    public function readonly_user_cannot_bulk_delete_clients()
    {
        $this->assertFalse($this->policy->bulkDelete($this->readOnlyUser));
    }

    /** @test */
    public function inactive_user_cannot_perform_actions()
    {
        $inactiveUser = User::factory()
            ->for($this->company)
            ->create([
                'role' => 'user',
                'is_active' => false
            ]);

        $client = Client::factory()->for($this->company)->create();

        $this->assertFalse($this->policy->view($inactiveUser, $client));
        $this->assertFalse($this->policy->create($inactiveUser));
        $this->assertFalse($this->policy->update($inactiveUser, $client));
        $this->assertFalse($this->policy->delete($inactiveUser, $client));
    }

    /** @test */
    public function suspended_company_user_cannot_perform_write_actions()
    {
        $this->company->update(['status' => 'suspended']);

        $client = Client::factory()->for($this->company)->create();

        // Can still view
        $this->assertTrue($this->policy->view($this->user, $client));
        $this->assertTrue($this->policy->viewAny($this->user));

        // Cannot perform write actions
        $this->assertFalse($this->policy->create($this->user));
        $this->assertFalse($this->policy->update($this->user, $client));
        $this->assertFalse($this->policy->delete($this->user, $client));
    }

    /** @test */
    public function blocked_company_user_cannot_perform_any_actions()
    {
        $this->company->update(['status' => 'blocked']);

        $client = Client::factory()->for($this->company)->create();

        $this->assertFalse($this->policy->viewAny($this->user));
        $this->assertFalse($this->policy->view($this->user, $client));
        $this->assertFalse($this->policy->create($this->user));
        $this->assertFalse($this->policy->update($this->user, $client));
        $this->assertFalse($this->policy->delete($this->user, $client));
    }

    /** @test */
    public function user_can_manage_client_addresses()
    {
        $client = Client::factory()->for($this->company)->create();

        $this->assertTrue($this->policy->manageAddresses($this->user, $client));
        $this->assertTrue($this->policy->manageAddresses($this->adminUser, $client));
    }

    /** @test */
    public function user_cannot_manage_addresses_for_client_from_different_company()
    {
        $client = Client::factory()->for($this->otherCompany)->create();

        $this->assertFalse($this->policy->manageAddresses($this->user, $client));
        $this->assertFalse($this->policy->manageAddresses($this->adminUser, $client));
    }

    /** @test */
    public function readonly_user_cannot_manage_client_addresses()
    {
        $client = Client::factory()->for($this->company)->create();

        $this->assertFalse($this->policy->manageAddresses($this->readOnlyUser, $client));
    }

    /** @test */
    public function user_can_view_client_statistics()
    {
        $this->assertTrue($this->policy->viewStatistics($this->user));
        $this->assertTrue($this->policy->viewStatistics($this->adminUser));
        $this->assertTrue($this->policy->viewStatistics($this->readOnlyUser));
    }

    /** @test */
    public function user_can_send_client_communications()
    {
        $client = Client::factory()->for($this->company)->create();

        $this->assertTrue($this->policy->sendCommunication($this->user, $client));
        $this->assertTrue($this->policy->sendCommunication($this->adminUser, $client));
    }

    /** @test */
    public function readonly_user_cannot_send_client_communications()
    {
        $client = Client::factory()->for($this->company)->create();

        $this->assertFalse($this->policy->sendCommunication($this->readOnlyUser, $client));
    }

    /** @test */
    public function user_cannot_send_communication_to_client_from_different_company()
    {
        $client = Client::factory()->for($this->otherCompany)->create();

        $this->assertFalse($this->policy->sendCommunication($this->user, $client));
        $this->assertFalse($this->policy->sendCommunication($this->adminUser, $client));
    }

    /** @test */
    public function policy_respects_custom_permissions()
    {
        // Create user with custom permissions
        $customUser = User::factory()
            ->for($this->company)
            ->create([
                'role' => 'custom',
                'permissions' => [
                    'clients.view' => true,
                    'clients.create' => false,
                    'clients.update' => true,
                    'clients.delete' => false,
                ]
            ]);

        $client = Client::factory()->for($this->company)->create();

        $this->assertTrue($this->policy->view($customUser, $client));
        $this->assertFalse($this->policy->create($customUser));
        $this->assertTrue($this->policy->update($customUser, $client));
        $this->assertFalse($this->policy->delete($customUser, $client));
    }

    /** @test */
    public function super_admin_has_access_to_all_companies()
    {
        $superAdmin = User::factory()
            ->for($this->company)
            ->create([
                'role' => 'super_admin',
                'is_super_admin' => true
            ]);

        $clientFromOtherCompany = Client::factory()->for($this->otherCompany)->create();

        $this->assertTrue($this->policy->view($superAdmin, $clientFromOtherCompany));
        $this->assertTrue($this->policy->update($superAdmin, $clientFromOtherCompany));
        $this->assertTrue($this->policy->delete($superAdmin, $clientFromOtherCompany));
    }

    /** @test */
    public function user_can_access_archived_clients()
    {
        $archivedClient = Client::factory()
            ->for($this->company)
            ->create(['status' => 'archived']);

        $this->assertTrue($this->policy->view($this->user, $archivedClient));
        $this->assertTrue($this->policy->update($this->user, $archivedClient));
    }

    /** @test */
    public function user_cannot_delete_client_with_active_invoices()
    {
        $client = Client::factory()->for($this->company)->create();
        
        // Create active invoice for client
        \App\Domain\Invoice\Models\Invoice::factory()
            ->for($client, 'client')
            ->for($this->company)
            ->create(['status' => 'sent']);

        $this->assertFalse($this->policy->delete($this->user, $client));
        $this->assertFalse($this->policy->delete($this->adminUser, $client));
    }

    /** @test */
    public function admin_can_override_business_rules_for_deletion()
    {
        $client = Client::factory()->for($this->company)->create();
        
        // Create active invoice for client
        \App\Domain\Invoice\Models\Invoice::factory()
            ->for($client, 'client')
            ->for($this->company)
            ->create(['status' => 'sent']);

        // Admin can force delete despite business rules
        $this->assertTrue($this->policy->forceDelete($this->adminUser, $client));
    }

    /** @test */
    public function policy_handles_null_user_gracefully()
    {
        $client = Client::factory()->for($this->company)->create();

        $this->assertFalse($this->policy->view(null, $client));
        $this->assertFalse($this->policy->create(null));
        $this->assertFalse($this->policy->update(null, $client));
        $this->assertFalse($this->policy->delete(null, $client));
    }

    /** @test */
    public function policy_handles_deleted_user_gracefully()
    {
        $deletedUser = User::factory()
            ->for($this->company)
            ->create(['deleted_at' => now()]);

        $client = Client::factory()->for($this->company)->create();

        $this->assertFalse($this->policy->view($deletedUser, $client));
        $this->assertFalse($this->policy->create($deletedUser));
        $this->assertFalse($this->policy->update($deletedUser, $client));
        $this->assertFalse($this->policy->delete($deletedUser, $client));
    }
}
