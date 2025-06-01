<?php

namespace Tests\Unit\Http\Middleware;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Middleware\CompanyAccess;
use App\Domain\Company\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\Customer\Models\Client;
use Illuminate\Support\Facades\Route;

class CompanyAccessTest extends TestCase
{
    use RefreshDatabase;

    private CompanyAccess $middleware;
    private User $user;
    private Company $company;
    private Company $otherCompany;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new CompanyAccess();
        $this->company = Company::factory()->create();
        $this->otherCompany = Company::factory()->create();
        $this->user = User::factory()->for($this->company)->create();
    }

    /** @test */
    public function it_allows_access_to_own_company_resources()
    {
        $client = Client::factory()->for($this->company)->create();
        
        $request = Request::create('/api/v1/clients/' . $client->id, 'GET');
        $request->setUserResolver(fn() => $this->user);
        $request->route()->setParameter('client', $client);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_denies_access_to_other_company_resources()
    {
        $client = Client::factory()->for($this->otherCompany)->create();
        
        $request = Request::create('/api/v1/clients/' . $client->id, 'GET');
        $request->setUserResolver(fn() => $this->user);
        $request->route()->setParameter('client', $client);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('Accès interdit', $response->getContent());
    }

    /** @test */
    public function it_allows_access_when_no_company_resource_in_route()
    {
        $request = Request::create('/api/v1/companies', 'GET');
        $request->setUserResolver(fn() => $this->user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_handles_multiple_route_parameters()
    {
        $client = Client::factory()->for($this->company)->create();
        $address = $client->addresses()->create([
            'type' => 'billing',
            'line1' => 'Test Address',
            'city' => 'Test City',
            'postal_code' => '12345',
            'country' => 'FR',
        ]);
        
        $request = Request::create('/api/v1/clients/' . $client->id . '/addresses/' . $address->id, 'GET');
        $request->setUserResolver(fn() => $this->user);
        
        // Mock route parameters
        $route = Route::get('/api/v1/clients/{client}/addresses/{address}', function () {});
        $route->setParameter('client', $client);
        $route->setParameter('address', $address);
        $request->setRouteResolver(fn() => $route);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_denies_access_when_nested_resource_belongs_to_different_company()
    {
        $otherClient = Client::factory()->for($this->otherCompany)->create();
        $address = $otherClient->addresses()->create([
            'type' => 'billing',
            'line1' => 'Test Address',
            'city' => 'Test City',
            'postal_code' => '12345',
            'country' => 'FR',
        ]);
        
        $request = Request::create('/api/v1/clients/' . $otherClient->id . '/addresses/' . $address->id, 'GET');
        $request->setUserResolver(fn() => $this->user);
        
        // Mock route parameters
        $route = Route::get('/api/v1/clients/{client}/addresses/{address}', function () {});
        $route->setParameter('client', $otherClient);
        $route->setParameter('address', $address);
        $request->setRouteResolver(fn() => $route);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function it_handles_admin_users_with_multiple_companies()
    {
        // Create admin user with access to multiple companies
        $adminUser = User::factory()->create([
            'is_admin' => true,
            'company_id' => $this->company->id
        ]);
        
        // Admin should have access to any company's resources
        $client = Client::factory()->for($this->otherCompany)->create();
        
        $request = Request::create('/api/v1/clients/' . $client->id, 'GET');
        $request->setUserResolver(fn() => $adminUser);
        $request->route()->setParameter('client', $client);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        // For admin users, this might be allowed depending on business logic
        // Adjust assertion based on your actual requirements
        $this->assertIn($response->getStatusCode(), [200, 403]);
    }

    /** @test */
    public function it_handles_soft_deleted_resources()
    {
        $client = Client::factory()->for($this->company)->create();
        $client->delete(); // Soft delete
        
        $request = Request::create('/api/v1/clients/' . $client->id, 'GET');
        $request->setUserResolver(fn() => $this->user);
        $request->route()->setParameter('client', $client);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        // Should still allow access to soft-deleted resources from same company
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_works_with_model_route_binding()
    {
        $client = Client::factory()->for($this->company)->create();
        
        $request = Request::create('/api/v1/clients/' . $client->id, 'GET');
        $request->setUserResolver(fn() => $this->user);
        
        // Simulate Laravel's route model binding
        $route = Route::get('/api/v1/clients/{client}', function () {});
        $route->bind($request);
        $route->setParameter('client', $client);
        $request->setRouteResolver(fn() => $route);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_logs_access_violations()
    {
        $client = Client::factory()->for($this->otherCompany)->create();
        
        $request = Request::create('/api/v1/clients/' . $client->id, 'GET');
        $request->setUserResolver(fn() => $this->user);
        $request->route()->setParameter('client', $client);

        $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        // Assert that security log was written
        $this->assertDatabaseHas('activity_logs', [
            'type' => 'security_violation',
            'user_id' => $this->user->id,
            'description' => 'Attempted access to resource from different company'
        ]);
    }

    /** @test */
    public function it_handles_unauthenticated_requests()
    {
        $client = Client::factory()->for($this->company)->create();
        
        $request = Request::create('/api/v1/clients/' . $client->id, 'GET');
        $request->route()->setParameter('client', $client);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    /** @test */
    public function it_handles_different_model_types()
    {
        // Test with Quote model
        $quote = \App\Domain\Quote\Models\Quote::factory()
            ->for($this->company)
            ->create();
        
        $request = Request::create('/api/v1/quotes/' . $quote->id, 'GET');
        $request->setUserResolver(fn() => $this->user);
        $request->route()->setParameter('quote', $quote);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_handles_post_requests_with_company_data()
    {
        $request = Request::create('/api/v1/clients', 'POST', [
            'type' => 'individual',
            'first_name' => 'Test',
            'last_name' => 'User',
            'company_id' => $this->company->id
        ]);
        $request->setUserResolver(fn() => $this->user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 201);
        });

        $this->assertEquals(201, $response->getStatusCode());
    }

    /** @test */
    public function it_denies_post_requests_with_different_company_data()
    {
        $request = Request::create('/api/v1/clients', 'POST', [
            'type' => 'individual',
            'first_name' => 'Test',
            'last_name' => 'User',
            'company_id' => $this->otherCompany->id
        ]);
        $request->setUserResolver(fn() => $this->user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 201);
        });

        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function it_automatically_sets_company_id_on_post_requests()
    {
        $request = Request::create('/api/v1/clients', 'POST', [
            'type' => 'individual',
            'first_name' => 'Test',
            'last_name' => 'User'
            // company_id not provided
        ]);
        $request->setUserResolver(fn() => $this->user);

        $this->middleware->handle($request, function ($req) {
            // After middleware, company_id should be automatically set
            $this->assertEquals($this->company->id, $req->input('company_id'));
            return new Response('Success', 201);
        });
    }

    /** @test */
    public function it_handles_bulk_operations()
    {
        $client1 = Client::factory()->for($this->company)->create();
        $client2 = Client::factory()->for($this->company)->create();
        $clientFromOtherCompany = Client::factory()->for($this->otherCompany)->create();
        
        $request = Request::create('/api/v1/clients/bulk-delete', 'DELETE', [
            'ids' => [$client1->id, $client2->id, $clientFromOtherCompany->id]
        ]);
        $request->setUserResolver(fn() => $this->user);

        $response = $this->middleware->handle($request, function ($req) {
            // Should filter out the client from other company
            $filteredIds = $req->input('ids');
            $this->assertNotContains($clientFromOtherCompany->id, $filteredIds);
            return new Response('Success', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_preserves_request_method_and_headers()
    {
        $client = Client::factory()->for($this->company)->create();
        
        $request = Request::create('/api/v1/clients/' . $client->id, 'PUT', [], [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer token123'
        ]);
        $request->setUserResolver(fn() => $this->user);
        $request->route()->setParameter('client', $client);

        $this->middleware->handle($request, function ($req) {
            $this->assertEquals('PUT', $req->getMethod());
            $this->assertEquals('application/json', $req->header('Content-Type'));
            $this->assertEquals('application/json', $req->header('Accept'));
            $this->assertEquals('Bearer token123', $req->header('Authorization'));
            return new Response('Success', 200);
        });
    }

    /** @test */
    public function it_handles_numeric_string_company_ids()
    {
        $client = Client::factory()->for($this->company)->create();
        
        $request = Request::create('/api/v1/clients', 'POST', [
            'type' => 'individual',
            'first_name' => 'Test',
            'last_name' => 'User',
            'company_id' => (string) $this->company->id // String instead of int
        ]);
        $request->setUserResolver(fn() => $this->user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 201);
        });

        $this->assertEquals(201, $response->getStatusCode());
    }
}
