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

class QuoteIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create([
            'quote_prefix' => 'TEST'
        ]);
        $this->user = User::factory()->for($this->company)->create();
        
        Sanctum::actingAs($this->user);
    }

    public function test_complete_quote_lifecycle(): void
    {
        // 1. Créer un client
        $customer = Client::factory()->for($this->company)->create([
            'email' => 'client@test.com'
        ]);

        // 2. Créer des produits
        $products = Product::factory()
            ->for($this->company)
            ->count(3)
            ->create();

        // 3. Créer un devis
        $quoteData = [
            'customer_id' => $customer->id,
            'subject' => 'Devis test complet',
            'notes' => 'Notes du devis de test',
            'terms' => 'Conditions générales',
            'discount_type' => 'percentage',
            'discount_value' => 5,
            'shipping_amount' => 25,
            'items' => [
                [
                    'product_id' => $products[0]->id,
                    'description' => $products[0]->name,
                    'quantity' => 2,
                    'unit_price' => $products[0]->price,
                    'tax_rate' => $products[0]->tax_rate
                ],
                [
                    'product_id' => $products[1]->id,
                    'description' => $products[1]->name,
                    'quantity' => 1,
                    'unit_price' => $products[1]->price,
                    'tax_rate' => $products[1]->tax_rate
                ],
                [
                    'description' => 'Service personnalisé',
                    'quantity' => 3,
                    'unit_price' => 150,
                    'tax_rate' => 20
                ]
            ]
        ];

        $createResponse = $this->postJson('/api/v1/quotes', $quoteData);
        $createResponse->assertStatus(201);

        $quoteId = $createResponse->json('data.id');
        $quote = Quote::find($quoteId);

        // Vérifier que le devis a été créé correctement
        $this->assertEquals(QuoteStatus::DRAFT->value, $quote->status);
        $this->assertStringStartsWith('TEST-', $quote->quote_number);
        $this->assertCount(3, $quote->items);

        // 4. Modifier le devis
        $updateData = [
            'subject' => 'Devis test modifié',
            'items' => [
                [
                    'description' => 'Service modifié',
                    'quantity' => 1,
                    'unit_price' => 500,
                    'tax_rate' => 20
                ]
            ]
        ];

        $updateResponse = $this->putJson("/api/v1/quotes/{$quoteId}", $updateData);
        $updateResponse->assertStatus(200);

        $quote->refresh();
        $this->assertEquals('Devis test modifié', $quote->subject);
        $this->assertCount(1, $quote->items);

        // 5. Envoyer le devis
        $sendResponse = $this->postJson("/api/v1/quotes/{$quoteId}/send", [
            'email' => $customer->email,
            'subject' => 'Votre devis TEST',
            'message' => 'Veuillez trouver ci-joint votre devis.'
        ]);

        $sendResponse->assertStatus(200);
        $quote->refresh();
        $this->assertEquals(QuoteStatus::SENT->value, $quote->status);
        $this->assertNotNull($quote->sent_at);

        // 6. Simuler l'acceptation du devis
        $quote->update(['status' => QuoteStatus::ACCEPTED->value]);

        // 7. Convertir en facture
        $convertResponse = $this->postJson("/api/v1/quotes/{$quoteId}/convert");
        $convertResponse->assertStatus(201);

        $quote->refresh();
        $this->assertEquals(QuoteStatus::CONVERTED->value, $quote->status);

        $invoiceId = $convertResponse->json('data.invoice_id');
        $this->assertDatabaseHas('invoices', [
            'id' => $invoiceId,
            'quote_id' => $quoteId,
            'company_id' => $this->company->id,
            'customer_id' => $customer->id
        ]);

        // 8. Vérifier l'historique des statuts
        $this->assertDatabaseCount('status_histories', 4); // draft, sent, accepted, converted
    }

    public function test_quote_filtering_and_pagination(): void
    {
        $customers = Client::factory()->for($this->company)->count(3)->create();
        
        // Créer des devis avec différents statuts et dates
        Quote::factory()->for($this->company)->for($customers[0])->draft()->count(5)->create();
        Quote::factory()->for($this->company)->for($customers[1])->sent()->count(3)->create();
        Quote::factory()->for($this->company)->for($customers[2])->accepted()->count(2)->create();

        // Test de pagination
        $response = $this->getJson('/api/v1/quotes?per_page=5');
        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 10);

        // Test de filtrage par statut
        $response = $this->getJson('/api/v1/quotes?filter[status]=draft');
        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');

        $response = $this->getJson('/api/v1/quotes?filter[status]=sent');
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

        // Test de filtrage par client
        $response = $this->getJson("/api/v1/quotes?filter[customer_id]={$customers[0]->id}");
        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');

        // Test de recherche
        $specificQuote = Quote::factory()
            ->for($this->company)
            ->for($customers[0])
            ->create([
                'subject' => 'Devis très spécifique pour recherche'
            ]);

        $response = $this->getJson('/api/v1/quotes?search=très spécifique');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $specificQuote->id);

        // Test de tri
        $response = $this->getJson('/api/v1/quotes?sort=-total');
        $response->assertStatus(200);
        
        $totals = collect($response->json('data'))->pluck('total');
        $this->assertTrue($totals->first() >= $totals->last());
    }

    public function test_quote_with_complex_calculations(): void
    {
        $customer = Client::factory()->for($this->company)->create();

        // Devis complexe avec différents taux de TVA et remises
        $quoteData = [
            'customer_id' => $customer->id,
            'discount_type' => 'fixed',
            'discount_value' => 100,
            'shipping_amount' => 50,
            'items' => [
                [
                    'description' => 'Produit TVA 20%',
                    'quantity' => 2,
                    'unit_price' => 200,
                    'tax_rate' => 20
                ],
                [
                    'description' => 'Produit TVA 10%',
                    'quantity' => 1,
                    'unit_price' => 300,
                    'tax_rate' => 10
                ],
                [
                    'description' => 'Produit exempt TVA',
                    'quantity' => 3,
                    'unit_price' => 50,
                    'tax_rate' => 0
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/quotes', $quoteData);
        $response->assertStatus(201);

        $quote = Quote::latest()->first();
        
        // Vérifications des calculs
        $this->assertEquals(750, $quote->subtotal); // (2*200) + (1*300) + (3*50) = 400 + 300 + 150
        
        // Subtotal après remise: 750 - 100 = 650
        // Plus frais de port: 650 + 50 = 700
        // TVA proportionnelle sur chaque taux
        // TVA 20% sur 400 moins remise proportionnelle = 400 - (400/750)*100 = 346.67 => TVA = 69.33
        // TVA 10% sur 300 moins remise proportionnelle = 300 - (300/750)*100 = 260 => TVA = 26
        // TVA 0% sur 150 = 0
        // Total TVA approximativement 95.33
        
        $this->assertEqualsWithDelta(95.33, $quote->tax_amount, 1);
        $this->assertEqualsWithDelta(795.33, $quote->total, 1);
    }

    public function test_quote_bulk_operations(): void
    {
        $customers = Client::factory()->for($this->company)->count(2)->create();
        
        // Créer plusieurs devis
        $quotes = Quote::factory()
            ->for($this->company)
            ->for($customers[0])
            ->draft()
            ->count(5)
            ->create();

        // Test de récupération en lot avec include
        $response = $this->getJson('/api/v1/quotes?include=customer,items&per_page=20');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'customer' => [
                            'id',
                            'name',
                            'email'
                        ],
                        'items'
                    ]
                ]
            ]);

        // Vérifier que les relations sont incluses
        $firstQuote = $response->json('data.0');
        $this->assertArrayHasKey('customer', $firstQuote);
        $this->assertNotNull($firstQuote['customer']);
    }

    public function test_quote_status_history_tracking(): void
    {
        $customer = Client::factory()->for($this->company)->create();
        
        $quote = Quote::factory()
            ->for($this->company)
            ->for($customer)
            ->draft()
            ->create();

        // Vérifier l'historique initial
        $this->assertDatabaseHas('status_histories', [
            'historyable_type' => Quote::class,
            'historyable_id' => $quote->id,
            'status' => QuoteStatus::DRAFT->value
        ]);

        // Changer le statut
        $quote->update(['status' => QuoteStatus::SENT->value]);
        
        // Vérifier le nouvel historique
        $this->assertDatabaseHas('status_histories', [
            'historyable_type' => Quote::class,
            'historyable_id' => $quote->id,
            'status' => QuoteStatus::SENT->value
        ]);

        $response = $this->getJson("/api/v1/quotes/{$quote->id}?include=statusHistories");
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'status_histories' => [
                        '*' => [
                            'status',
                            'comment',
                            'created_at'
                        ]
                    ]
                ]
            ]);

        $histories = $response->json('data.status_histories');
        $this->assertCount(2, $histories);
    }

    public function test_quote_expiration_automation(): void
    {
        // Créer un devis qui devrait expirer
        $customer = Client::factory()->for($this->company)->create();
        
        $quote = Quote::factory()
            ->for($this->company)
            ->for($customer)
            ->create([
                'status' => QuoteStatus::SENT->value,
                'valid_until' => now()->subDays(1) // Expiré depuis 1 jour
            ]);

        // Simuler le passage de l'observer
        $quote->touch();
        
        $quote->refresh();
        $this->assertEquals(QuoteStatus::EXPIRED->value, $quote->status);

        // Vérifier l'historique d'expiration
        $this->assertDatabaseHas('status_histories', [
            'historyable_type' => Quote::class,
            'historyable_id' => $quote->id,
            'status' => QuoteStatus::EXPIRED->value,
            'comment' => 'Devis expiré automatiquement'
        ]);
    }

    public function test_quote_resource_includes_computed_fields(): void
    {
        $customer = Client::factory()->for($this->company)->create();
        
        $quote = Quote::factory()
            ->for($this->company)
            ->for($customer)
            ->sent()
            ->create();

        $response = $this->getJson("/api/v1/quotes/{$quote->id}");
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'can_edit',
                    'can_delete', 
                    'can_send',
                    'can_convert',
                    'is_expired',
                    'actions' => [
                        'view_pdf',
                        'download_pdf',
                        'send',
                        'duplicate',
                        'convert'
                    ]
                ]
            ]);

        $data = $response->json('data');
        
        // Un devis envoyé peut être modifié
        $this->assertTrue($data['can_edit']);
        // Un devis envoyé peut être supprimé
        $this->assertTrue($data['can_delete']);
        // Un devis envoyé peut être renvoyé
        $this->assertTrue($data['can_send']);
        // Un devis envoyé ne peut pas être converti (il faut qu'il soit accepté)
        $this->assertFalse($data['can_convert']);
    }
}
