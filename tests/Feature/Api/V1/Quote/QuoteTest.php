<?php

namespace Tests\Feature\Api\V1\Quote;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Company;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Enums\QuoteStatus;
use Laravel\Sanctum\Sanctum;

class QuoteTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $customer;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->for($this->company)->create();
        $this->customer = Customer::factory()->for($this->company)->create();
        $this->product = Product::factory()->for($this->company)->create([
            'price' => 100.00,
            'tax_rate' => 20.00
        ]);
        
        Sanctum::actingAs($this->user);
    }

    public function test_can_list_quotes(): void
    {
        // Créer des devis pour le test
        Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->count(5)
            ->create();

        // Créer un devis pour une autre entreprise (ne doit pas apparaître)
        $otherCompany = Company::factory()->create();
        Quote::factory()
            ->for($otherCompany)
            ->for(Customer::factory()->for($otherCompany))
            ->create();

        $response = $this->getJson('/api/v1/quotes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'quote_number',
                        'quote_date',
                        'valid_until',
                        'status',
                        'total',
                        'customer',
                        'created_at'
                    ]
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total'
                ],
                'links',
                'summary'
            ])
            ->assertJsonCount(5, 'data');
    }

    public function test_can_filter_quotes_by_status(): void
    {
        Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->create(['status' => QuoteStatus::DRAFT->value]);

        Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->create(['status' => QuoteStatus::SENT->value]);

        $response = $this->getJson('/api/v1/quotes?filter[status]=draft');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'draft');
    }

    public function test_can_search_quotes(): void
    {
        Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->create([
                'quote_number' => 'DEV-2024-0001',
                'subject' => 'Devis pour site web'
            ]);

        Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->create([
                'quote_number' => 'DEV-2024-0002',
                'subject' => 'Devis pour application mobile'
            ]);

        $response = $this->getJson('/api/v1/quotes?search=site web');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.subject', 'Devis pour site web');
    }

    public function test_can_create_quote(): void
    {
        $quoteData = [
            'customer_id' => $this->customer->id,
            'quote_date' => '2024-01-15',
            'valid_until' => '2024-02-15',
            'subject' => 'Devis pour développement',
            'notes' => 'Notes du devis',
            'terms' => 'Conditions du devis',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'shipping_amount' => 50,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'description' => 'Développement site web',
                    'quantity' => 1,
                    'unit_price' => 1000,
                    'tax_rate' => 20
                ],
                [
                    'description' => 'Consultation',
                    'quantity' => 5,
                    'unit_price' => 100,
                    'tax_rate' => 20
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/quotes', $quoteData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'quote_number',
                    'status',
                    'total',
                    'items'
                ]
            ]);

        $this->assertDatabaseHas('quotes', [
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'subject' => 'Devis pour développement',
            'status' => QuoteStatus::DRAFT->value
        ]);

        $this->assertDatabaseCount('quote_items', 2);
    }

    public function test_create_quote_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/quotes', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id', 'items']);
    }

    public function test_create_quote_validates_customer_belongs_to_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherCustomer = Customer::factory()->for($otherCompany)->create();

        $quoteData = [
            'customer_id' => $otherCustomer->id,
            'items' => [
                [
                    'description' => 'Test',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'tax_rate' => 20
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/quotes', $quoteData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id']);
    }

    public function test_can_show_quote(): void
    {
        $quote = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->create();

        QuoteItem::factory()
            ->for($quote)
            ->for($this->product)
            ->create();

        $response = $this->getJson("/api/v1/quotes/{$quote->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'quote_number',
                    'status',
                    'customer',
                    'items' => [
                        '*' => [
                            'id',
                            'description',
                            'quantity',
                            'unit_price',
                            'line_total'
                        ]
                    ],
                    'actions',
                    'can_edit',
                    'can_delete'
                ]
            ]);
    }

    public function test_cannot_show_quote_from_other_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherQuote = Quote::factory()
            ->for($otherCompany)
            ->for(Customer::factory()->for($otherCompany))
            ->create();

        $response = $this->getJson("/api/v1/quotes/{$otherQuote->id}");

        $response->assertStatus(403);
    }

    public function test_can_update_quote(): void
    {
        $quote = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->create(['status' => QuoteStatus::DRAFT->value]);

        $updateData = [
            'subject' => 'Devis modifié',
            'notes' => 'Notes modifiées',
            'items' => [
                [
                    'description' => 'Nouveau service',
                    'quantity' => 2,
                    'unit_price' => 500,
                    'tax_rate' => 20
                ]
            ]
        ];

        $response = $this->putJson("/api/v1/quotes/{$quote->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.subject', 'Devis modifié');

        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id,
            'subject' => 'Devis modifié',
            'notes' => 'Notes modifiées'
        ]);
    }

    public function test_cannot_update_accepted_quote(): void
    {
        $quote = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->create(['status' => QuoteStatus::ACCEPTED->value]);

        $updateData = [
            'subject' => 'Tentative de modification'
        ];

        $response = $this->putJson("/api/v1/quotes/{$quote->id}", $updateData);

        $response->assertStatus(500); // Erreur métier
    }

    public function test_can_delete_quote(): void
    {
        $quote = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->create(['status' => QuoteStatus::DRAFT->value]);

        $response = $this->deleteJson("/api/v1/quotes/{$quote->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('quotes', [
            'id' => $quote->id
        ]);
    }

    public function test_cannot_delete_accepted_quote(): void
    {
        $quote = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->create(['status' => QuoteStatus::ACCEPTED->value]);

        $response = $this->deleteJson("/api/v1/quotes/{$quote->id}");

        $response->assertStatus(500); // Erreur métier
    }

    public function test_can_send_quote(): void
    {
        $quote = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->create(['status' => QuoteStatus::DRAFT->value]);

        $sendData = [
            'email' => 'client@example.com',
            'subject' => 'Votre devis',
            'message' => 'Veuillez trouver ci-joint votre devis.'
        ];

        $response = $this->postJson("/api/v1/quotes/{$quote->id}/send", $sendData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'sent_at',
                    'recipient'
                ]
            ]);

        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id,
            'status' => QuoteStatus::SENT->value
        ]);
    }

    public function test_can_duplicate_quote(): void
    {
        $quote = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->create();

        QuoteItem::factory()
            ->for($quote)
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

        $this->assertDatabaseCount('quotes', 2);
        $this->assertDatabaseCount('quote_items', 2);
    }

    public function test_can_download_quote_pdf(): void
    {
        $quote = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->create();

        $response = $this->getJson("/api/v1/quotes/{$quote->id}/pdf");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_unauthorized_access_returns_401(): void
    {
        Sanctum::actingAs(null);

        $response = $this->getJson('/api/v1/quotes');

        $response->assertStatus(401);
    }

    public function test_forbidden_access_returns_403(): void
    {
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->for($otherCompany)->create();
        
        Sanctum::actingAs($otherUser);

        $quote = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->create();

        $response = $this->getJson("/api/v1/quotes/{$quote->id}");

        $response->assertStatus(403);
    }

    public function test_can_include_relations(): void
    {
        $quote = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->create();

        QuoteItem::factory()
            ->for($quote)
            ->for($this->product)
            ->create();

        $response = $this->getJson("/api/v1/quotes/{$quote->id}?include=customer,items,statusHistories");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'customer' => [
                        'id',
                        'name',
                        'email'
                    ],
                    'items' => [
                        '*' => [
                            'id',
                            'description',
                            'product' => [
                                'id',
                                'name'
                            ]
                        ]
                    ]
                ]
            ]);
    }

    public function test_pagination_works_correctly(): void
    {
        Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->count(25)
            ->create();

        $response = $this->getJson('/api/v1/quotes?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.last_page', 3);
    }

    public function test_sorting_works_correctly(): void
    {
        $quote1 = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->create(['total' => 100]);

        $quote2 = Quote::factory()
            ->for($this->company)
            ->for($this->customer)
            ->create(['total' => 200]);

        $response = $this->getJson('/api/v1/quotes?sort=-total');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $quote2->id)
            ->assertJsonPath('data.1.id', $quote1->id);
    }
}
