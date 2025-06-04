<?php

namespace Tests\Feature\Api\V1\Product;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domain\Company\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\Category;
use Laravel\Sanctum\Sanctum;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->for($this->company)->create();
        $this->category = Category::factory()->for($this->company)->create([
            'type' => 'product'
        ]);
        
        Sanctum::actingAs($this->user);
    }

    public function test_can_list_products(): void
    {
        Product::factory()->count(3)->for($this->company)->create();

        $response = $this->getJson('/api/v1/products');

        $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'reference',
                            'unit_price',
                            'vat_rate',
                            'is_active',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'meta' => [
                        'total',
                        'count',
                        'per_page',
                        'current_page',
                        'total_pages',
                        'has_more_pages'
                    ],
                    'links' => [
                        'first',
                        'last',
                        'prev',
                        'next'
                    ]
                ]);
    }

    public function test_can_create_product(): void
    {
        $productData = [
            'name' => 'Test Product',
            'description' => 'A test product description',
            'reference' => 'TEST-001',
            'category_id' => $this->category->id,
            'unit_price' => 29.99,
            'cost_price' => 15.00,
            'vat_rate' => 20,
            'unit' => 'piece',
            'weight' => 1.5,
            'dimensions' => '10x20x5',
            'barcode' => '1234567890123',
            'stock_quantity' => 100,
            'stock_alert_threshold' => 10,
            'is_active' => true,
            'attributes' => ['color' => 'red', 'size' => 'M'],
            'variants' => [
                ['name' => 'color', 'value' => 'blue', 'price_adjustment' => 5],
                ['name' => 'size', 'value' => 'L', 'price_adjustment' => 2]
            ]
        ];

        $response = $this->postJson('/api/v1/products', $productData);

        $response->assertCreated()
                ->assertJsonStructure([
                    'id',
                    'name',
                    'description',
                    'reference',
                    'unit_price',
                    'cost_price',
                    'vat_rate',
                    'unit',
                    'weight',
                    'dimensions',
                    'barcode',
                    'stock_quantity',
                    'stock_alert_threshold',
                    'is_active',
                    'attributes',
                    'variants',
                    'margin',
                    'margin_percentage',
                    'category',
                    'created_at',
                    'updated_at'
                ])
                ->assertJson([
                    'name' => 'Test Product',
                    'reference' => 'TEST-001',
                    'unit_price' => 29.99,
                    'cost_price' => 15.00,
                    'vat_rate' => 20,
                    'margin' => 14.99,
                    'margin_percentage' => 99.93
                ]);

        $this->assertDatabaseHas('products', [
            'company_id' => $this->company->id,
            'name' => 'Test Product',
            'reference' => 'TEST-001',
            'unit_price' => 29.99
        ]);
    }

    public function test_can_show_product(): void
    {
        $product = Product::factory()->for($this->company)->for($this->category)->create();

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertOk()
                ->assertJsonStructure([
                    'id',
                    'name',
                    'description',
                    'unit_price',
                    'vat_rate',
                    'category',
                    'created_at',
                    'updated_at'
                ])
                ->assertJson([
                    'id' => $product->id,
                    'name' => $product->name
                ]);
    }

    public function test_can_update_product(): void
    {
        $product = Product::factory()->for($this->company)->create();
        
        $updateData = [
            'name' => 'Updated Product Name',
            'unit_price' => 39.99,
            'vat_rate' => 10
        ];

        $response = $this->putJson("/api/v1/products/{$product->id}", $updateData);

        $response->assertOk()
                ->assertJson([
                    'id' => $product->id,
                    'name' => 'Updated Product Name',
                    'unit_price' => 39.99,
                    'vat_rate' => 10
                ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'unit_price' => 39.99
        ]);
    }

    public function test_can_delete_product(): void
    {
        $product = Product::factory()->for($this->company)->create();

        $response = $this->deleteJson("/api/v1/products/{$product->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_can_filter_products_by_name(): void
    {
        Product::factory()->for($this->company)->create(['name' => 'iPhone 14']);
        Product::factory()->for($this->company)->create(['name' => 'Samsung Galaxy']);

        $response = $this->getJson('/api/v1/products?filter[name]=iPhone');

        $response->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.name', 'iPhone 14');
    }

    public function test_can_filter_products_by_category(): void
    {
        $category1 = Category::factory()->for($this->company)->create(['type' => 'product']);
        $category2 = Category::factory()->for($this->company)->create(['type' => 'product']);

        Product::factory()->for($this->company)->for($category1)->create();
        Product::factory()->for($this->company)->for($category2)->create();

        $response = $this->getJson("/api/v1/products?filter[category_id]={$category1->id}");

        $response->assertOk()
                ->assertJsonCount(1, 'data');
    }

    public function test_can_search_products(): void
    {
        Product::factory()->for($this->company)->create([
            'name' => 'iPhone 14',
            'description' => 'Latest Apple smartphone'
        ]);
        Product::factory()->for($this->company)->create([
            'name' => 'Samsung Galaxy',
            'description' => 'Android phone'
        ]);

        $response = $this->getJson('/api/v1/products?search=Apple');

        $response->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.name', 'iPhone 14');
    }

    public function test_can_sort_products(): void
    {
        Product::factory()->for($this->company)->create(['name' => 'Zebra']);
        Product::factory()->for($this->company)->create(['name' => 'Alpha']);

        $response = $this->getJson('/api/v1/products?sort=name');

        $response->assertOk()
                ->assertJsonPath('data.0.name', 'Alpha')
                ->assertJsonPath('data.1.name', 'Zebra');
    }

    public function test_unauthorized_access_returns_401(): void
    {
        Sanctum::actingAs(null);

        $response = $this->getJson('/api/v1/products');

        $response->assertUnauthorized();
    }

    public function test_forbidden_access_returns_403(): void
    {
        $otherCompany = Company::factory()->create();
        $product = Product::factory()->for($otherCompany)->create();

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertForbidden();
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/v1/products', []);

        $response->assertUnprocessable()
                ->assertJsonValidationErrors(['name', 'unit_price', 'vat_rate']);
    }

    public function test_unique_reference_validation(): void
    {
        Product::factory()->for($this->company)->create(['reference' => 'UNIQUE-001']);

        $response = $this->postJson('/api/v1/products', [
            'name' => 'Test Product',
            'reference' => 'UNIQUE-001',
            'unit_price' => 29.99,
            'vat_rate' => 20
        ]);

        $response->assertUnprocessable()
                ->assertJsonValidationErrors(['reference']);
    }

    public function test_invalid_vat_rate_validation(): void
    {
        $response = $this->postJson('/api/v1/products', [
            'name' => 'Test Product',
            'unit_price' => 29.99,
            'vat_rate' => 15 // Invalid VAT rate
        ]);

        $response->assertUnprocessable()
                ->assertJsonValidationErrors(['vat_rate']);
    }

    public function test_can_include_category_relation(): void
    {
        $product = Product::factory()->for($this->company)->for($this->category)->create();

        $response = $this->getJson("/api/v1/products/{$product->id}?include=category");

        $response->assertOk()
                ->assertJsonStructure([
                    'category' => [
                        'id',
                        'name',
                        'type'
                    ]
                ]);
    }

    public function test_margin_calculations(): void
    {
        $productData = [
            'name' => 'Test Product',
            'unit_price' => 100,
            'cost_price' => 60,
            'vat_rate' => 20
        ];

        $response = $this->postJson('/api/v1/products', $productData);

        $response->assertCreated()
                ->assertJson([
                    'margin' => 40,
                    'margin_percentage' => 66.67
                ]);
    }
}
