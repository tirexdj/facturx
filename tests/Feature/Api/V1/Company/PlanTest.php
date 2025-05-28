<?php

namespace Tests\Feature\Api\V1\Company;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\Plan;
use App\Domain\Auth\Models\User;
use App\Domain\Analytics\Models\Feature;
use Laravel\Sanctum\Sanctum;

class PlanTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->plan = Plan::factory()->create(['is_public' => true, 'is_active' => true]);
        $this->company = Company::factory()->for($this->plan)->create();
        $this->user = User::factory()->for($this->company)->create();
    }

    public function test_can_list_plans(): void
    {
        Sanctum::actingAs($this->user);

        // Créer quelques plans supplémentaires
        Plan::factory()->create(['is_public' => true, 'is_active' => true]);
        Plan::factory()->create(['is_public' => false, 'is_active' => true]); // Privé
        Plan::factory()->create(['is_public' => true, 'is_active' => false]); // Inactif

        $response = $this->getJson('/api/v1/plans');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'code',
                            'description',
                            'price_monthly',
                            'price_yearly',
                            'currency_code',
                            'is_active',
                            'is_public',
                            'trial_days',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ],
                'success',
                'message'
            ]);

        // Vérifier que tous les plans retournés sont publics et actifs par défaut
        $plans = $response->json('data.data');
        $this->assertGreaterThanOrEqual(2, count($plans)); // Au moins 2 plans publics et actifs
        
        foreach ($plans as $plan) {
            $this->assertTrue($plan['is_public']);
            $this->assertTrue($plan['is_active']);
        }
    }

    public function test_can_filter_plans_by_public_status(): void
    {
        Sanctum::actingAs($this->user);

        Plan::factory()->create(['is_public' => false, 'is_active' => true]);

        $response = $this->getJson('/api/v1/plans?is_public=false');

        $response->assertStatus(200);
        
        $plans = $response->json('data.data');
        foreach ($plans as $plan) {
            $this->assertFalse($plan['is_public']);
        }
    }

    public function test_can_filter_plans_by_active_status(): void
    {
        Sanctum::actingAs($this->user);

        Plan::factory()->create(['is_public' => true, 'is_active' => false]);

        $response = $this->getJson('/api/v1/plans?is_active=false');

        $response->assertStatus(200);
        
        $plans = $response->json('data.data');
        foreach ($plans as $plan) {
            $this->assertFalse($plan['is_active']);
        }
    }

    public function test_can_show_plan(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/plans/{$this->plan->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'code',
                    'description',
                    'price_monthly',
                    'price_yearly',
                    'currency_code',
                    'is_active',
                    'is_public',
                    'trial_days',
                    'features',
                    'plan_features',
                    'created_at',
                    'updated_at'
                ],
                'success',
                'message'
            ])
            ->assertJson([
                'data' => [
                    'id' => $this->plan->id,
                    'name' => $this->plan->name,
                    'code' => $this->plan->code
                ],
                'success' => true
            ]);
    }

    public function test_cannot_show_private_plan(): void
    {
        Sanctum::actingAs($this->user);

        $privatePlan = Plan::factory()->create(['is_public' => false, 'is_active' => true]);

        $response = $this->getJson("/api/v1/plans/{$privatePlan->id}");

        $response->assertStatus(404);
    }

    public function test_cannot_show_inactive_plan(): void
    {
        Sanctum::actingAs($this->user);

        $inactivePlan = Plan::factory()->create(['is_public' => true, 'is_active' => false]);

        $response = $this->getJson("/api/v1/plans/{$inactivePlan->id}");

        $response->assertStatus(404);
    }

    public function test_can_show_plan_with_features(): void
    {
        Sanctum::actingAs($this->user);

        // Créer des features
        $feature1 = Feature::factory()->create(['code' => 'max_clients']);
        $feature2 = Feature::factory()->create(['code' => 'max_invoices']);

        // Attacher les features au plan
        $this->plan->features()->attach($feature1->id, [
            'is_enabled' => true,
            'value_limit' => 100
        ]);
        $this->plan->features()->attach($feature2->id, [
            'is_enabled' => true,
            'value_limit' => 500
        ]);

        $response = $this->getJson("/api/v1/plans/{$this->plan->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'features' => [
                        '*' => [
                            'id',
                            'name',
                            'code',
                            'description',
                            'is_enabled',
                            'value_limit'
                        ]
                    ]
                ]
            ]);

        $features = $response->json('data.features');
        $this->assertCount(2, $features);
        
        $featureCodes = collect($features)->pluck('code')->toArray();
        $this->assertContains('max_clients', $featureCodes);
        $this->assertContains('max_invoices', $featureCodes);
    }

    public function test_unauthorized_access_returns_401(): void
    {
        $response = $this->getJson('/api/v1/plans');

        $response->assertStatus(401);
    }

    public function test_plans_ordered_by_price(): void
    {
        Sanctum::actingAs($this->user);

        // Créer des plans avec différents prix
        Plan::factory()->create([
            'price_monthly' => 50.00,
            'is_public' => true,
            'is_active' => true
        ]);
        Plan::factory()->create([
            'price_monthly' => 10.00,
            'is_public' => true,
            'is_active' => true
        ]);

        $response = $this->getJson('/api/v1/plans');

        $response->assertStatus(200);

        $plans = $response->json('data.data');
        $prices = collect($plans)->pluck('price_monthly')->toArray();
        
        // Vérifier que les prix sont triés par ordre croissant
        $sortedPrices = $prices;
        sort($sortedPrices);
        $this->assertEquals($sortedPrices, $prices);
    }

    public function test_can_include_stats(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/plans/{$this->plan->id}?include_stats=1");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'stats' => [
                        'companies_count',
                        'active_companies_count'
                    ]
                ]
            ]);

        $stats = $response->json('data.stats');
        $this->assertIsInt($stats['companies_count']);
        $this->assertIsInt($stats['active_companies_count']);
        $this->assertGreaterThanOrEqual(1, $stats['companies_count']); // Au moins 1 (notre company de test)
    }

    public function test_plan_not_found_returns_404(): void
    {
        Sanctum::actingAs($this->user);

        $nonExistentId = '550e8400-e29b-41d4-a716-446655440000';
        $response = $this->getJson("/api/v1/plans/{$nonExistentId}");

        $response->assertStatus(404);
    }
}
