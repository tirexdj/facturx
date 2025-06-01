<?php

namespace Tests\Unit\Actions\Api\V1\Customer;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Actions\Api\V1\Customer\GetClientAction;
use App\Domain\Company\Models\Company;
use App\Domain\Customer\Models\Client;
use App\Domain\Customer\Models\ClientAddress;
use App\Domain\Quote\Models\Quote;
use App\Domain\Invoice\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;

class GetClientActionTest extends TestCase
{
    use RefreshDatabase;

    private GetClientAction $action;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->action = new GetClientAction();
    }

    /** @test */
    public function it_returns_paginated_clients_for_company()
    {
        // Create clients for this company
        $clients = Client::factory()
            ->for($this->company)
            ->count(15)
            ->create();

        // Create clients for another company (should not appear)
        $otherCompany = Company::factory()->create();
        Client::factory()
            ->for($otherCompany)
            ->count(5)
            ->create();

        $result = $this->action->execute($this->company);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(15, $result->total());
        $this->assertCount(15, $result->items());
        
        // Check that all clients belong to the correct company
        foreach ($result->items() as $client) {
            $this->assertEquals($this->company->id, $client->company_id);
        }
    }

    /** @test */
    public function it_applies_pagination_parameters()
    {
        Client::factory()
            ->for($this->company)
            ->count(25)
            ->create();

        $filters = [
            'per_page' => 10,
            'page' => 2,
        ];

        $result = $this->action->execute($this->company, $filters);

        $this->assertEquals(25, $result->total());
        $this->assertEquals(10, $result->perPage());
        $this->assertEquals(2, $result->currentPage());
        $this->assertCount(10, $result->items());
    }

    /** @test */
    public function it_filters_by_client_type()
    {
        Client::factory()
            ->for($this->company)
            ->count(3)
            ->create(['type' => 'individual']);

        Client::factory()
            ->for($this->company)
            ->count(2)
            ->create(['type' => 'company']);

        $filters = ['type' => 'individual'];

        $result = $this->action->execute($this->company, $filters);

        $this->assertEquals(3, $result->total());
        foreach ($result->items() as $client) {
            $this->assertEquals('individual', $client->type);
        }
    }

    /** @test */
    public function it_filters_by_active_status()
    {
        Client::factory()
            ->for($this->company)
            ->count(3)
            ->create(['is_active' => true]);

        Client::factory()
            ->for($this->company)
            ->count(2)
            ->create(['is_active' => false]);

        $filters = ['is_active' => true];

        $result = $this->action->execute($this->company, $filters);

        $this->assertEquals(3, $result->total());
        foreach ($result->items() as $client) {
            $this->assertTrue($client->is_active);
        }
    }

    /** @test */
    public function it_searches_clients_by_name()
    {
        Client::factory()
            ->for($this->company)
            ->create([
                'type' => 'individual',
                'first_name' => 'Jean',
                'last_name' => 'Dupont'
            ]);

        Client::factory()
            ->for($this->company)
            ->create([
                'type' => 'company',
                'name' => 'ACME Corporation'
            ]);

        Client::factory()
            ->for($this->company)
            ->create([
                'type' => 'individual',
                'first_name' => 'Marie',
                'last_name' => 'Martin'
            ]);

        $filters = ['search' => 'Jean'];

        $result = $this->action->execute($this->company, $filters);

        $this->assertEquals(1, $result->total());
        $client = $result->items()[0];
        $this->assertEquals('Jean', $client->first_name);
    }

    /** @test */
    public function it_searches_clients_by_email()
    {
        Client::factory()
            ->for($this->company)
            ->create(['email' => 'jean.dupont@example.com']);

        Client::factory()
            ->for($this->company)
            ->create(['email' => 'marie.martin@test.com']);

        $filters = ['search' => 'dupont'];

        $result = $this->action->execute($this->company, $filters);

        $this->assertEquals(1, $result->total());
        $client = $result->items()[0];
        $this->assertEquals('jean.dupont@example.com', $client->email);
    }

    /** @test */
    public function it_searches_clients_by_phone()
    {
        Client::factory()
            ->for($this->company)
            ->create(['phone' => '0123456789']);

        Client::factory()
            ->for($this->company)
            ->create(['phone' => '0987654321']);

        $filters = ['search' => '0123'];

        $result = $this->action->execute($this->company, $filters);

        $this->assertEquals(1, $result->total());
        $client = $result->items()[0];
        $this->assertEquals('0123456789', $client->phone);
    }

    /** @test */
    public function it_searches_company_clients_by_siren()
    {
        Client::factory()
            ->for($this->company)
            ->create([
                'type' => 'company',
                'name' => 'Test Company',
                'siren' => '732829320'
            ]);

        Client::factory()
            ->for($this->company)
            ->create([
                'type' => 'company',
                'name' => 'Other Company',
                'siren' => '552120222'
            ]);

        $filters = ['search' => '732829'];

        $result = $this->action->execute($this->company, $filters);

        $this->assertEquals(1, $result->total());
        $client = $result->items()[0];
        $this->assertEquals('732829320', $client->siren);
    }

    /** @test */
    public function it_sorts_clients_by_name()
    {
        Client::factory()
            ->for($this->company)
            ->create([
                'type' => 'individual',
                'first_name' => 'Zebra',
                'last_name' => 'Last'
            ]);

        Client::factory()
            ->for($this->company)
            ->create([
                'type' => 'individual',
                'first_name' => 'Alpha',
                'last_name' => 'First'
            ]);

        $filters = ['sort' => 'name'];

        $result = $this->action->execute($this->company, $filters);

        $clients = $result->items();
        $this->assertEquals('Alpha', $clients[0]->first_name);
        $this->assertEquals('Zebra', $clients[1]->first_name);
    }

    /** @test */
    public function it_sorts_clients_descending()
    {
        Client::factory()
            ->for($this->company)
            ->create(['created_at' => now()->subDays(2)]);

        Client::factory()
            ->for($this->company)
            ->create(['created_at' => now()->subDays(1)]);

        $filters = ['sort' => '-created_at'];

        $result = $this->action->execute($this->company, $filters);

        $clients = $result->items();
        $this->assertTrue($clients[0]->created_at->gt($clients[1]->created_at));
    }

    /** @test */
    public function it_includes_addresses_when_requested()
    {
        $client = Client::factory()
            ->for($this->company)
            ->create();

        ClientAddress::factory()
            ->for($client)
            ->count(2)
            ->create();

        $filters = ['include' => ['addresses']];

        $result = $this->action->execute($this->company, $filters);

        $client = $result->items()[0];
        $this->assertTrue($client->relationLoaded('addresses'));
        $this->assertCount(2, $client->addresses);
    }

    /** @test */
    public function it_includes_quotes_count_when_requested()
    {
        $client = Client::factory()
            ->for($this->company)
            ->create();

        Quote::factory()
            ->for($client, 'client')
            ->for($this->company)
            ->count(3)
            ->create();

        $filters = ['include' => ['quotes_count']];

        $result = $this->action->execute($this->company, $filters);

        $client = $result->items()[0];
        $this->assertEquals(3, $client->quotes_count);
    }

    /** @test */
    public function it_includes_invoices_count_when_requested()
    {
        $client = Client::factory()
            ->for($this->company)
            ->create();

        Invoice::factory()
            ->for($client, 'client')
            ->for($this->company)
            ->count(5)
            ->create();

        $filters = ['include' => ['invoices_count']];

        $result = $this->action->execute($this->company, $filters);

        $client = $result->items()[0];
        $this->assertEquals(5, $client->invoices_count);
    }

    /** @test */
    public function it_includes_multiple_relations()
    {
        $client = Client::factory()
            ->for($this->company)
            ->create();

        ClientAddress::factory()
            ->for($client)
            ->count(2)
            ->create();

        Quote::factory()
            ->for($client, 'client')
            ->for($this->company)
            ->count(3)
            ->create();

        $filters = ['include' => ['addresses', 'quotes_count']];

        $result = $this->action->execute($this->company, $filters);

        $client = $result->items()[0];
        $this->assertTrue($client->relationLoaded('addresses'));
        $this->assertCount(2, $client->addresses);
        $this->assertEquals(3, $client->quotes_count);
    }

    /** @test */
    public function it_excludes_soft_deleted_clients()
    {
        $activeClient = Client::factory()
            ->for($this->company)
            ->create();

        $deletedClient = Client::factory()
            ->for($this->company)
            ->create();
        $deletedClient->delete();

        $result = $this->action->execute($this->company);

        $this->assertEquals(1, $result->total());
        $this->assertEquals($activeClient->id, $result->items()[0]->id);
    }

    /** @test */
    public function it_filters_by_tags()
    {
        Client::factory()
            ->for($this->company)
            ->create(['tags' => ['VIP', 'Premium']]);

        Client::factory()
            ->for($this->company)
            ->create(['tags' => ['Standard']]);

        Client::factory()
            ->for($this->company)
            ->create(['tags' => ['VIP', 'Gold']]);

        $filters = ['tags' => ['VIP']];

        $result = $this->action->execute($this->company, $filters);

        $this->assertEquals(2, $result->total());
        foreach ($result->items() as $client) {
            $this->assertContains('VIP', $client->tags);
        }
    }

    /** @test */
    public function it_filters_by_created_date_range()
    {
        $oldClient = Client::factory()
            ->for($this->company)
            ->create(['created_at' => now()->subDays(10)]);

        $recentClient = Client::factory()
            ->for($this->company)
            ->create(['created_at' => now()->subDays(2)]);

        $filters = [
            'created_from' => now()->subDays(5)->toDateString(),
            'created_to' => now()->toDateString(),
        ];

        $result = $this->action->execute($this->company, $filters);

        $this->assertEquals(1, $result->total());
        $this->assertEquals($recentClient->id, $result->items()[0]->id);
    }

    /** @test */
    public function it_returns_empty_result_for_company_with_no_clients()
    {
        $result = $this->action->execute($this->company);

        $this->assertEquals(0, $result->total());
        $this->assertEmpty($result->items());
    }

    /** @test */
    public function it_applies_default_sorting()
    {
        $client1 = Client::factory()
            ->for($this->company)
            ->create([
                'type' => 'individual',
                'first_name' => 'Jean',
                'last_name' => 'Dupont'
            ]);

        $client2 = Client::factory()
            ->for($this->company)
            ->create([
                'type' => 'individual',
                'first_name' => 'Marie',
                'last_name' => 'Martin'
            ]);

        // No sort specified, should default to name ascending
        $result = $this->action->execute($this->company);

        $clients = $result->items();
        $this->assertEquals('Jean', $clients[0]->first_name);
        $this->assertEquals('Marie', $clients[1]->first_name);
    }

    /** @test */
    public function it_respects_per_page_limits()
    {
        Client::factory()
            ->for($this->company)
            ->count(150)
            ->create();

        // Test max per_page limit
        $filters = ['per_page' => 200];

        $result = $this->action->execute($this->company, $filters);

        $this->assertEquals(100, $result->perPage()); // Should be capped at 100
    }

    /** @test */
    public function it_handles_complex_combined_filters()
    {
        // Create various clients
        Client::factory()
            ->for($this->company)
            ->create([
                'type' => 'individual',
                'first_name' => 'Jean',
                'last_name' => 'Dupont',
                'email' => 'jean@example.com',
                'is_active' => true,
                'tags' => ['VIP']
            ]);

        Client::factory()
            ->for($this->company)
            ->create([
                'type' => 'company',
                'name' => 'ACME Corp',
                'email' => 'contact@acme.com',
                'is_active' => true,
                'tags' => ['Premium']
            ]);

        Client::factory()
            ->for($this->company)
            ->create([
                'type' => 'individual',
                'first_name' => 'Marie',
                'last_name' => 'Martin',
                'email' => 'marie@example.com',
                'is_active' => false,
                'tags' => ['VIP']
            ]);

        $filters = [
            'type' => 'individual',
            'is_active' => true,
            'tags' => ['VIP'],
            'search' => 'Jean'
        ];

        $result = $this->action->execute($this->company, $filters);

        $this->assertEquals(1, $result->total());
        $client = $result->items()[0];
        $this->assertEquals('Jean', $client->first_name);
        $this->assertEquals('individual', $client->type);
        $this->assertTrue($client->is_active);
        $this->assertContains('VIP', $client->tags);
    }
}
