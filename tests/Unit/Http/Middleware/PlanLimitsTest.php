<?php

namespace Tests\Unit\Http\Middleware;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Middleware\PlanLimits;
use App\Domain\Company\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\Customer\Models\Client;
use App\Domain\Quote\Models\Quote;
use App\Domain\Invoice\Models\Invoice;
use App\Domain\Product\Models\Product;

class PlanLimitsTest extends TestCase
{
    use RefreshDatabase;

    private PlanLimits $middleware;
    private User $user;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new PlanLimits();
        $this->company = Company::factory()->create(['plan' => 'free']);
        $this->user = User::factory()->for($this->company)->create();
    }

    /** @test */
    public function it_allows_creation_when_under_free_plan_limits()
    {
        // Free plan allows 50 clients
        Client::factory()->for($this->company)->count(45)->create();
        
        $request = Request::create('/api/v1/clients', 'POST', [
            'type' => 'individual',
            'first_name' => 'Test',
            'last_name' => 'User'
        ]);
        $request->setUserResolver(fn() => $this->user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 201);
        });

        $this->assertEquals(201, $response->getStatusCode());
    }

    /** @test */
    public function it_blocks_creation_when_free_plan_client_limit_reached()
    {
        // Free plan allows 50 clients
        Client::factory()->for($this->company)->count(50)->create();
        
        $request = Request::create('/api/v1/clients', 'POST', [
            'type' => 'individual',
            'first_name' => 'Test',
            'last_name' => 'User'
        ]);
        $request->setUserResolver(fn() => $this->user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 201);
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('Limite de clients atteinte', $response->getContent());
    }

    /** @test */
    public function it_blocks_creation_when_free_plan_quote_monthly_limit_reached()
    {
        // Free plan allows 50 quotes per month
        Quote::factory()
            ->for($this->company)
            ->count(50)
            ->create(['created_at' => now()->startOfMonth()]);
        
        $request = Request::create('/api/v1/quotes', 'POST', [
            'client_id' => Client::factory()->for($this->company)->create()->id,
            'title' => 'Test Quote'
        ]);
        $request->setUserResolver(fn() => $this->user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 201);
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('Limite de devis mensuelle atteinte', $response->getContent());
    }

    /** @test */
    public function it_allows_creation_when_previous_month_quotes_dont_count()
    {
        // Create quotes from previous month (shouldn't count towards current limit)
        Quote::factory()
            ->for($this->company)
            ->count(50)
            ->create(['created_at' => now()->subMonth()]);
        
        $request = Request::create('/api/v1/quotes', 'POST', [
            'client_id' => Client::factory()->for($this->company)->create()->id,
            'title' => 'Test Quote'
        ]);
        $request->setUserResolver(fn() => $this->user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 201);
        });

        $this->assertEquals(201, $response->getStatusCode());
    }

    /** @test */
    public function it_allows_unlimited_resources_for_premium_plan()
    {
        $this->company->update(['plan' => 'premium']);
        
        // Create way more than free plan limits
        Client::factory()->for($this->company)->count(100)->create();
        Quote::factory()->for($this->company)->count(200)->create(['created_at' => now()]);
        
        $request = Request::create('/api/v1/clients', 'POST', [
            'type' => 'individual',
            'first_name' => 'Test',
            'last_name' => 'User'
        ]);
        $request->setUserResolver(fn() => $this->user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 201);
        });

        $this->assertEquals(201, $response->getStatusCode());
    }

    /** @test */
    public function it_respects_business_plan_limits()
    {
        $this->company->update(['plan' => 'business']);
        
        // Business plan allows 500 quotes per month
        Quote::factory()
            ->for($this->company)
            ->count(500)
            ->create(['created_at' => now()->startOfMonth()]);
        
        $request = Request::create('/api/v1/quotes', 'POST', [
            'client_id' => Client::factory()->for($this->company)->create()->id,
            'title' => 'Test Quote'
        ]);
        $request->setUserResolver(fn() => $this->user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 201);
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('Limite de devis mensuelle atteinte', $response->getContent());
    }

    /** @test */
    public function it_allows_get_requests_regardless_of_limits()
    {
        // Free plan limits reached
        Client::factory()->for($this->company)->count(50)->create();
        
        $request = Request::create('/api/v1/clients', 'GET');
        $request->setUserResolver(fn() => $this->user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_allows_put_and_patch_requests_regardless_of_limits()
    {
        $client = Client::factory()->for($this->company)->create();
        
        // Free plan limits reached
        Client::factory()->for($this->company)->count(50)->create();
        
        $request = Request::create('/api/v1/clients/' . $client->id, 'PUT', [
            'first_name' => 'Updated Name'
        ]);
        $request->setUserResolver(fn() => $this->user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_allows_delete_requests_regardless_of_limits()
    {
        $client = Client::factory()->for($this->company)->create();
        
        $request = Request::create('/api/v1/clients/' . $client->id, 'DELETE');
        $request->setUserResolver(fn() => $this->user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 204);
        });

        $this->assertEquals(204, $response->getStatusCode());
    }

    /** @test */
    public function it_blocks_invoice_creation_when_monthly_limit_reached()
    {
        // Free plan allows 50 invoices per month
        Invoice::factory()
            ->for($this->company)
            ->count(50)
            ->create(['created_at' => now()->startOfMonth()]);
        
        $request = Request::create('/api/v1/invoices', 'POST', [
            'client_id' => Client::factory()->for($this->company)->create()->id,
            'total_amount' => 100.00
        ]);
        $request->setUserResolver(fn() => $this->user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 201);
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('Limite de factures mensuelle atteinte', $response->getContent());
    }

    /** @test */
    public function it_blocks_product_creation_when_limit_reached()
    {
        // Free plan allows 100 products
        Product::factory()->for($this->company)->count(100)->create();
        
        $request = Request::create('/api/v1/products', 'POST', [
            'name' => 'Test Product',
            'price' => 50.00
        ]);
        $request->setUserResolver(fn() => $this->user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 201);
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('Limite de produits atteinte', $response->getContent());
    }

    /** @test */
    public function it_returns_detailed_limit_information()
    {
        Client::factory()->for($this->company)->count(45)->create();
        
        $request = Request::create('/api/v1/clients', 'POST', [
            'type' => 'individual',
            'first_name' => 'Test',
            'last_name' => 'User'
        ]);
        $request->setUserResolver(fn() => $this->user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 201);
        });

        // Should include usage information in response headers
        $this->assertTrue($response->headers->has('X-Plan-Clients-Used'));
        $this->assertTrue($response->headers->has('X-Plan-Clients-Limit'));
        $this->assertEquals('46', $response->headers->get('X-Plan-Clients-Used'));
        $this->assertEquals('50', $response->headers->get('X-Plan-Clients-Limit'));
    }

    /** @test */
    public function it_handles_unknown_plans_as_free()
    {
        $this->company->update(['plan' => 'unknown_plan']);
        
        Client::factory()->for($this->company)->count(50)->create();
        
        $request = Request::create('/api/v1/clients', 'POST', [
            'type' => 'individual',
            'first_name' => 'Test',
            'last_name' => 'User'
        ]);
        $request->setUserResolver(fn() => $this->user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 201);
        });

        // Should apply free plan limits
        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function it_handles_soft_deleted_resources_correctly()
    {
        // Create 50 clients, then soft delete 5
        $clients = Client::factory()->for($this->company)->count(50)->create();
        $clients->take(5)->each->delete();
        
        $request = Request::create('/api/v1/clients', 'POST', [
            'type' => 'individual',
            'first_name' => 'Test',
            'last_name' => 'User'
        ]);
        $request->setUserResolver(fn() => $this->user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 201);
        });

        // Should count only active (non-soft-deleted) resources
        $this->assertEquals(201, $response->getStatusCode());
    }

    /** @test */
    public function it_blocks_api_access_for_blocked_companies()
    {
        $this->company->update(['status' => 'blocked']);
        
        $request = Request::create('/api/v1/clients', 'POST', [
            'type' => 'individual',
            'first_name' => 'Test',
            'last_name' => 'User'
        ]);
        $request->setUserResolver(fn() => $this->user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 201);
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('Compte bloqué', $response->getContent());
    }

    /** @test */
    public function it_blocks_api_access_for_suspended_companies()
    {
        $this->company->update(['status' => 'suspended']);
        
        $request = Request::create('/api/v1/clients', 'GET');
        $request->setUserResolver(fn() => $this->user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('Compte suspendu', $response->getContent());
    }

    /** @test */
    public function it_allows_access_for_active_companies()
    {
        $this->company->update(['status' => 'active']);
        
        $request = Request::create('/api/v1/clients', 'GET');
        $request->setUserResolver(fn() => $this->user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_logs_limit_violations()
    {
        Client::factory()->for($this->company)->count(50)->create();
        
        $request = Request::create('/api/v1/clients', 'POST', [
            'type' => 'individual',
            'first_name' => 'Test',
            'last_name' => 'User'
        ]);
        $request->setUserResolver(fn() => $this->user);

        $this->middleware->handle($request, function ($req) {
            return new Response('Success', 201);
        });

        // Assert that limit violation was logged
        $this->assertDatabaseHas('activity_logs', [
            'type' => 'plan_limit_violation',
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'description' => 'Client creation blocked - limit reached'
        ]);
    }

    /** @test */
    public function it_provides_upgrade_suggestions()
    {
        Client::factory()->for($this->company)->count(50)->create();
        
        $request = Request::create('/api/v1/clients', 'POST', [
            'type' => 'individual',
            'first_name' => 'Test',
            'last_name' => 'User'
        ]);
        $request->setUserResolver(fn() => $this->user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 201);
        });

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('upgrade_suggestion', $responseData);
        $this->assertStringContainsString('business', $responseData['upgrade_suggestion']['recommended_plan']);
    }

    /** @test */
    public function it_handles_concurrent_requests_safely()
    {
        // Create 49 clients (1 below limit)
        Client::factory()->for($this->company)->count(49)->create();
        
        $request1 = Request::create('/api/v1/clients', 'POST', [
            'type' => 'individual',
            'first_name' => 'Test1',
            'last_name' => 'User'
        ]);
        $request1->setUserResolver(fn() => $this->user);

        $request2 = Request::create('/api/v1/clients', 'POST', [
            'type' => 'individual',
            'first_name' => 'Test2',
            'last_name' => 'User'
        ]);
        $request2->setUserResolver(fn() => $this->user);

        // Both requests should be handled correctly
        $response1 = $this->middleware->handle($request1, function ($req) {
            return new Response('Success', 201);
        });

        $response2 = $this->middleware->handle($request2, function ($req) {
            return new Response('Success', 201);
        });

        // First request should succeed, second should fail
        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertEquals(403, $response2->getStatusCode());
    }

    /** @test */
    public function it_caches_usage_counts_for_performance()
    {
        $request = Request::create('/api/v1/clients', 'POST', [
            'type' => 'individual',
            'first_name' => 'Test',
            'last_name' => 'User'
        ]);
        $request->setUserResolver(fn() => $this->user);

        // First call should cache the counts
        $this->middleware->handle($request, function ($req) {
            return new Response('Success', 201);
        });

        // Verify cache was used (this would need actual cache assertions)
        $this->assertTrue(cache()->has('plan_limits:' . $this->company->id . ':clients'));
    }
}
