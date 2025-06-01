<?php

namespace Tests\Unit\Actions\Api\V1\Customer;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Actions\Api\V1\Customer\DeleteClientAction;
use App\Domain\Company\Models\Company;
use App\Domain\Customer\Models\Client;
use App\Domain\Customer\Models\ClientAddress;
use App\Domain\Quote\Models\Quote;
use App\Domain\Invoice\Models\Invoice;
use App\Domain\Payment\Models\Payment;

class DeleteClientActionTest extends TestCase
{
    use RefreshDatabase;

    private DeleteClientAction $action;
    private Company $company;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->action = new DeleteClientAction();
        
        $this->client = Client::factory()
            ->for($this->company)
            ->create([
                'type' => 'individual',
                'first_name' => 'Jean',
                'last_name' => 'Dupont',
                'email' => 'jean.dupont@example.com'
            ]);
    }

    /** @test */
    public function it_soft_deletes_client_successfully()
    {
        $clientId = $this->client->id;

        $result = $this->action->execute($this->client);

        $this->assertTrue($result);
        
        // Check that client is soft deleted
        $this->assertSoftDeleted('clients', [
            'id' => $clientId,
        ]);

        // Verify client exists in database but is soft deleted
        $this->assertDatabaseHas('clients', [
            'id' => $clientId,
        ]);

        $deletedClient = Client::withTrashed()->find($clientId);
        $this->assertNotNull($deletedClient->deleted_at);
    }

    /** @test */
    public function it_soft_deletes_client_addresses()
    {
        $addresses = ClientAddress::factory()
            ->for($this->client)
            ->count(3)
            ->create();

        $addressIds = $addresses->pluck('id')->toArray();

        $result = $this->action->execute($this->client);

        $this->assertTrue($result);

        // Check that all addresses are soft deleted
        foreach ($addressIds as $addressId) {
            $this->assertSoftDeleted('client_addresses', [
                'id' => $addressId,
            ]);
        }
    }

    /** @test */
    public function it_prevents_deletion_if_client_has_active_quotes()
    {
        Quote::factory()
            ->for($this->client, 'client')
            ->for($this->company)
            ->create([
                'status' => 'sent'
            ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Impossible de supprimer un client ayant des devis actifs');

        $this->action->execute($this->client);
    }

    /** @test */
    public function it_prevents_deletion_if_client_has_active_invoices()
    {
        Invoice::factory()
            ->for($this->client, 'client')
            ->for($this->company)
            ->create([
                'status' => 'sent'
            ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Impossible de supprimer un client ayant des factures actives');

        $this->action->execute($this->client);
    }

    /** @test */
    public function it_prevents_deletion_if_client_has_unpaid_invoices()
    {
        Invoice::factory()
            ->for($this->client, 'client')
            ->for($this->company)
            ->create([
                'status' => 'sent',
                'total_amount' => 1000.00,
                'paid_amount' => 500.00 // Partially paid
            ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Impossible de supprimer un client ayant des factures impayées');

        $this->action->execute($this->client);
    }

    /** @test */
    public function it_allows_deletion_if_client_has_only_draft_quotes()
    {
        Quote::factory()
            ->for($this->client, 'client')
            ->for($this->company)
            ->create([
                'status' => 'draft'
            ]);

        $result = $this->action->execute($this->client);

        $this->assertTrue($result);
        $this->assertSoftDeleted('clients', [
            'id' => $this->client->id,
        ]);
    }

    /** @test */
    public function it_allows_deletion_if_client_has_only_expired_quotes()
    {
        Quote::factory()
            ->for($this->client, 'client')
            ->for($this->company)
            ->create([
                'status' => 'expired'
            ]);

        $result = $this->action->execute($this->client);

        $this->assertTrue($result);
        $this->assertSoftDeleted('clients', [
            'id' => $this->client->id,
        ]);
    }

    /** @test */
    public function it_allows_deletion_if_client_has_only_paid_invoices()
    {
        Invoice::factory()
            ->for($this->client, 'client')
            ->for($this->company)
            ->create([
                'status' => 'paid',
                'total_amount' => 1000.00,
                'paid_amount' => 1000.00 // Fully paid
            ]);

        $result = $this->action->execute($this->client);

        $this->assertTrue($result);
        $this->assertSoftDeleted('clients', [
            'id' => $this->client->id,
        ]);
    }

    /** @test */
    public function it_allows_deletion_if_client_has_only_cancelled_invoices()
    {
        Invoice::factory()
            ->for($this->client, 'client')
            ->for($this->company)
            ->create([
                'status' => 'cancelled'
            ]);

        $result = $this->action->execute($this->client);

        $this->assertTrue($result);
        $this->assertSoftDeleted('clients', [
            'id' => $this->client->id,
        ]);
    }

    /** @test */
    public function it_soft_deletes_draft_quotes_when_deleting_client()
    {
        $draftQuote = Quote::factory()
            ->for($this->client, 'client')
            ->for($this->company)
            ->create([
                'status' => 'draft'
            ]);

        $expiredQuote = Quote::factory()
            ->for($this->client, 'client')
            ->for($this->company)
            ->create([
                'status' => 'expired'
            ]);

        $result = $this->action->execute($this->client);

        $this->assertTrue($result);

        // Check that draft and expired quotes are also soft deleted
        $this->assertSoftDeleted('quotes', [
            'id' => $draftQuote->id,
        ]);

        $this->assertSoftDeleted('quotes', [
            'id' => $expiredQuote->id,
        ]);
    }

    /** @test */
    public function it_soft_deletes_cancelled_invoices_when_deleting_client()
    {
        $cancelledInvoice = Invoice::factory()
            ->for($this->client, 'client')
            ->for($this->company)
            ->create([
                'status' => 'cancelled'
            ]);

        $result = $this->action->execute($this->client);

        $this->assertTrue($result);

        // Check that cancelled invoice is also soft deleted
        $this->assertSoftDeleted('invoices', [
            'id' => $cancelledInvoice->id,
        ]);
    }

    /** @test */
    public function it_preserves_paid_invoices_when_deleting_client()
    {
        $paidInvoice = Invoice::factory()
            ->for($this->client, 'client')
            ->for($this->company)
            ->create([
                'status' => 'paid',
                'total_amount' => 1000.00,
                'paid_amount' => 1000.00
            ]);

        $result = $this->action->execute($this->client);

        $this->assertTrue($result);

        // Check that paid invoice is NOT deleted (for accounting purposes)
        $this->assertDatabaseHas('invoices', [
            'id' => $paidInvoice->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function it_preserves_payments_when_deleting_client()
    {
        $invoice = Invoice::factory()
            ->for($this->client, 'client')
            ->for($this->company)
            ->create([
                'status' => 'paid',
                'total_amount' => 1000.00,
                'paid_amount' => 1000.00
            ]);

        $payment = Payment::factory()
            ->for($invoice)
            ->for($this->company)
            ->create([
                'amount' => 1000.00
            ]);

        $result = $this->action->execute($this->client);

        $this->assertTrue($result);

        // Check that payment is NOT deleted (for accounting purposes)
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function it_checks_multiple_constraints_before_deletion()
    {
        // Create multiple blocking items
        Quote::factory()
            ->for($this->client, 'client')
            ->for($this->company)
            ->create(['status' => 'sent']);

        Invoice::factory()
            ->for($this->client, 'client')
            ->for($this->company)
            ->create(['status' => 'sent']);

        $this->expectException(\InvalidArgumentException::class);
        // Should throw exception for quotes (first check)
        $this->expectExceptionMessage('Impossible de supprimer un client ayant des devis actifs');

        $this->action->execute($this->client);
    }

    /** @test */
    public function it_handles_client_with_no_related_data()
    {
        // Client with no quotes, invoices, or additional addresses
        $result = $this->action->execute($this->client);

        $this->assertTrue($result);
        $this->assertSoftDeleted('clients', [
            'id' => $this->client->id,
        ]);
    }

    /** @test */
    public function it_logs_deletion_action()
    {
        $this->expectsEvents(\App\Events\ClientDeleted::class);

        $result = $this->action->execute($this->client);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_true_on_successful_deletion()
    {
        $result = $this->action->execute($this->client);

        $this->assertTrue($result);
        $this->assertIsBool($result);
    }

    /** @test */
    public function it_maintains_referential_integrity()
    {
        $address = ClientAddress::factory()
            ->for($this->client)
            ->create();

        $result = $this->action->execute($this->client);

        $this->assertTrue($result);

        // Check that soft deleted client can still be found with relationships
        $deletedClient = Client::withTrashed()
            ->with('addresses')
            ->find($this->client->id);

        $this->assertNotNull($deletedClient);
        $this->assertNotNull($deletedClient->deleted_at);
        
        // Addresses should also be soft deleted but relationship should work
        $deletedAddresses = $deletedClient->addresses()->withTrashed()->get();
        $this->assertCount(1, $deletedAddresses);
        $this->assertNotNull($deletedAddresses->first()->deleted_at);
    }

    /** @test */
    public function it_handles_force_delete_parameter()
    {
        $result = $this->action->execute($this->client, true);

        $this->assertTrue($result);

        // With force delete, client should be permanently deleted
        $this->assertDatabaseMissing('clients', [
            'id' => $this->client->id,
        ]);

        // Should not be found even with withTrashed
        $deletedClient = Client::withTrashed()->find($this->client->id);
        $this->assertNull($deletedClient);
    }

    /** @test */
    public function it_prevents_force_delete_if_constraints_exist()
    {
        Invoice::factory()
            ->for($this->client, 'client')
            ->for($this->company)
            ->create([
                'status' => 'paid',
                'total_amount' => 1000.00,
                'paid_amount' => 1000.00
            ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Impossible de supprimer définitivement un client ayant un historique de facturation');

        $this->action->execute($this->client, true);
    }
}
