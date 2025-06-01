<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\Plan;
use App\Domain\Auth\Models\User;
use App\Domain\Auth\Models\Role;
use Tests\Traits\ApiTestHelpers;
use Tests\Traits\ManagesTestTransactions;
use Tests\Traits\TestDatabaseMigrations;

abstract class BaseApiTest extends TestCase
{
    use RefreshDatabase, ApiTestHelpers, ManagesTestTransactions, TestDatabaseMigrations;

    protected User $user;
    protected Company $company;
    protected Plan $plan;
    protected Role $role;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Nettoyer les transactions avant de commencer
        $this->setUpManagesTestTransactions();
        
        // Configuration de la base de données de test
        $this->setupTestDatabase();
        
        // Vérifier la santé de la base de données
        $this->verifyDatabaseHealth();

        // Create a plan
        $this->plan = Plan::factory()->create([
            'name' => 'Test Plan',
            'code' => 'test',
            'price_monthly' => 0,
            'is_active' => true,
        ]);

        // Create a role
        $this->role = Role::factory()->admin()->create();

        // Create a company
        $this->company = Company::factory()->create([
            'plan_id' => $this->plan->id,
        ]);

        // Create a user
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'role_id' => $this->role->id,
            'is_active' => true,
        ]);
    }
    

    
    protected function tearDown(): void
    {
        $this->tearDownManagesTestTransactions();
        parent::tearDown();
    }

    /**
     * Create an additional user for the same company.
     */
    protected function createUserForCompany(?Company $company = null, ?Role $role = null): User
    {
        $company = $company ?? $this->company;
        $role = $role ?? $this->role;
        
        return User::factory()->create([
            'company_id' => $company->id,
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    /**
     * Create a user for a different company.
     */
    protected function createUserForDifferentCompany(): User
    {
        $plan = Plan::factory()->create();
        $role = Role::factory()->create();
        $company = Company::factory()->create(['plan_id' => $plan->id]);
        
        return User::factory()->create([
            'company_id' => $company->id,
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }
}
