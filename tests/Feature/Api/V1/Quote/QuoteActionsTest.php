<?php

namespace Tests\Feature\Api\V1\Quote;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domain\Company\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\Customer\Models\Client;
use App\Domain\Product\Models\Product;
use App\Domain\Quote\Models\Quote;
use App\Domain\Quote\Models\QuoteItem;
use App\Enums\QuoteStatus;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class QuoteActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Client $customer;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->for($this->company)->create();
        $this->customer = Client::factory()->for($this->company)->create();
        $this->product = Product::factory()->for($this->company)->create([
            'price' => 100.00,
            'tax_rate' => 20.00
        ]);
        
        Sanctum::actingAs($this->user);
    }

    public function test_can_send_quote_with_default_email(): void
    {
        Mail::fake();
        Storage::fake();

        $quote = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->draft()
            ->create();

        QuoteItem::factory()
            ->for($quote)
            ->create();

        $response = $this->postJson("/api/v1/quotes/{$quote->id}/send", [
            'email' => $this->customer->email,
            'subject' => 'Test Subject',
            'message' => 'Test message'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'sent_at',
                    'recipient'
                ]
            ]);

        // Vérifier que le statut a été mis à jour
        $quote->refresh();
        $this->assertEquals(QuoteStatus::SENT->value, $quote->status);
        $this->assertNotNull($quote->sent_at);

        // Vérifier que l'email a été envoyé
        Mail::assertSent(\App\Mail\QuoteMail::class);
    }

    public function test_cannot_send_expired_quote(): void
    {
        $quote = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->expired()
            ->create();

        $response = $this->postJson("/api/v1/quotes/{$quote->id}/send", [
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(500);
    }

    public function test_can_convert_accepted_quote_to_invoice(): void
    {
        $quote = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->accepted()
            ->create();

        QuoteItem::factory()
            ->for($quote)
            ->count(2)
            ->create();

        $response = $this->postJson("/api/v1/quotes/{$quote->id}/convert");

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'invoice_id',
                    'invoice_number'
                ]
            ]);

        // Vérifier que le devis a été marqué comme converti
        $quote->refresh();
        $this->assertEquals(QuoteStatus::CONVERTED->value, $quote->status);

        // Vérifier qu'une facture a été créée
        $this->assertDatabaseHas('invoices', [
            'quote_id' => $quote->id,
            'company_id' => $quote->company_id,
            'customer_id' => $quote->customer_id,
            'total' => $quote->total
        ]);

        // Vérifier que les lignes ont été copiées
        $this->assertDatabaseCount('invoice_items', 2);
    }

    public function test_cannot_convert_non_accepted_quote(): void
    {
        $quote = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->draft()
            ->create();

        $response = $this->postJson("/api/v1/quotes/{$quote->id}/convert");

        $response->assertStatus(500);
    }

    public function test_can_duplicate_quote(): void
    {
        $quote = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->create([
                'subject' => 'Original Quote',
                'total' => 1000
            ]);

        QuoteItem::factory()
            ->for($quote)
            ->count(3)
            ->create();

        $response = $this->postJson("/api/v1/quotes/{$quote->id}/duplicate");

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'quote_number',
                    'status'
                ]
            ]);

        // Vérifier qu'un nouveau devis a été créé
        $this->assertDatabaseCount('quotes', 2);
        
        // Vérifier que les lignes ont été dupliquées
        $this->assertDatabaseCount('quote_items', 6);

        // Vérifier que le nouveau devis est en brouillon
        $newQuoteId = $response->json('data.id');
        $newQuote = Quote::find($newQuoteId);
        $this->assertEquals(QuoteStatus::DRAFT->value, $newQuote->status);
        $this->assertNotEquals($quote->quote_number, $newQuote->quote_number);
    }

    public function test_can_download_quote_pdf(): void
    {
        $quote = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->create();

        QuoteItem::factory()
            ->for($quote)
            ->create();

        $response = $this->get("/api/v1/quotes/{$quote->id}/pdf");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="devis-' . $quote->quote_number . '.pdf"');
    }

    public function test_quote_status_transitions(): void
    {
        $quote = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->draft()
            ->create();

        // Draft -> Sent
        $quote->update(['status' => QuoteStatus::SENT->value]);
        $this->assertEquals(QuoteStatus::SENT->value, $quote->status);

        // Sent -> Accepted
        $quote->update(['status' => QuoteStatus::ACCEPTED->value]);
        $this->assertEquals(QuoteStatus::ACCEPTED->value, $quote->status);

        // Accepted -> Converted
        $quote->update(['status' => QuoteStatus::CONVERTED->value]);
        $this->assertEquals(QuoteStatus::CONVERTED->value, $quote->status);
    }

    public function test_quote_expiration_logic(): void
    {
        // Créer un devis expiré
        $expiredQuote = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->create([
                'status' => QuoteStatus::SENT->value,
                'valid_until' => now()->subDays(5)
            ]);

        // Déclencher l'observer
        $expiredQuote->touch();

        // Le statut devrait être automatiquement mis à expiré
        $expiredQuote->refresh();
        $this->assertEquals(QuoteStatus::EXPIRED->value, $expiredQuote->status);
    }

    public function test_quote_totals_calculation_with_discount(): void
    {
        $quoteData = [
            'customer_id' => $this->customer->id,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'shipping_amount' => 50,
            'items' => [
                [
                    'description' => 'Test Item',
                    'quantity' => 2,
                    'unit_price' => 100,
                    'tax_rate' => 20
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/quotes', $quoteData);

        $response->assertStatus(201);

        $quote = Quote::latest()->first();
        
        // Vérifier les calculs
        $this->assertEquals(200, $quote->subtotal); // 2 * 100
        $this->assertEquals(36, $quote->tax_amount); // (200 - 20 + 50) * 0.20 = 46, mais ici c'est calculé sur le sous-total après remise
        $this->assertEquals(266, $quote->total); // 200 - 20 + 50 + 36
    }

    public function test_quote_number_generation(): void
    {
        $quote1 = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->create();

        $quote2 = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->create();

        // Les numéros doivent être différents et suivre un pattern
        $this->assertNotEquals($quote1->quote_number, $quote2->quote_number);
        $this->assertMatchesRegularExpression('/^DEV-\d{4}-\d{4}$/', $quote1->quote_number);
        $this->assertMatchesRegularExpression('/^DEV-\d{4}-\d{4}$/', $quote2->quote_number);
    }

    public function test_quote_permissions_by_company(): void
    {
        // Créer une autre entreprise avec un utilisateur
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->for($otherCompany)->create();
        $otherCustomer = Client::factory()->for($otherCompany)->create();

        $quote = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->create();

        $otherQuote = Quote::factory()
            ->for($otherCompany)
            ->for($otherCustomer)
            ->create();

        // L'utilisateur ne peut pas voir le devis de l'autre entreprise
        $response = $this->getJson("/api/v1/quotes/{$otherQuote->id}");
        $response->assertStatus(403);

        // L'utilisateur peut voir son propre devis
        $response = $this->getJson("/api/v1/quotes/{$quote->id}");
        $response->assertStatus(200);

        // Changer d'utilisateur
        Sanctum::actingAs($otherUser);

        // Maintenant il ne peut plus voir le premier devis
        $response = $this->getJson("/api/v1/quotes/{$quote->id}");
        $response->assertStatus(403);

        // Mais peut voir le sien
        $response = $this->getJson("/api/v1/quotes/{$otherQuote->id}");
        $response->assertStatus(200);
    }

    public function test_quote_validation_errors(): void
    {
        // Test sans client
        $response = $this->postJson('/api/v1/quotes', [
            'items' => [
                [
                    'description' => 'Test',
                    'quantity' => 1,
                    'unit_price' => 100
                ]
            ]
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id']);

        // Test sans lignes
        $response = $this->postJson('/api/v1/quotes', [
            'customer_id' => $this->customer->id
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);

        // Test avec ligne invalide
        $response = $this->postJson('/api/v1/quotes', [
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'description' => '',
                    'quantity' => -1,
                    'unit_price' => 'invalid'
                ]
            ]
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'items.0.description',
                'items.0.quantity',
                'items.0.unit_price'
            ]);
    }

    public function test_quote_soft_delete_prevention(): void
    {
        // Les devis acceptés ne peuvent pas être supprimés
        $acceptedQuote = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->accepted()
            ->create();

        $response = $this->deleteJson("/api/v1/quotes/{$acceptedQuote->id}");
        $response->assertStatus(500);

        // Les devis convertis ne peuvent pas être supprimés
        $convertedQuote = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->converted()
            ->create();

        $response = $this->deleteJson("/api/v1/quotes/{$convertedQuote->id}");
        $response->assertStatus(500);

        // Les devis en brouillon peuvent être supprimés
        $draftQuote = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->draft()
            ->create();

        $response = $this->deleteJson("/api/v1/quotes/{$draftQuote->id}");
        $response->assertStatus(204);
    }
}
