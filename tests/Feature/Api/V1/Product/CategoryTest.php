<?php

namespace Tests\Feature\Api\V1\Product;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domain\Company\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\Service;
use Laravel\Sanctum\Sanctum;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->for($this->company)->create();
        
        Sanctum::actingAs($this->user);
    }

    public function test_can_list_categories(): void
    {
        Category::factory()->count(3)->for($this->company)->create();

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'type',
                            'sort_order',
                            'color',
                            'icon',
                            'parent_id',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'meta',
                    'links'
                ]);
    }

    public function test_can_create_category(): void
    {
        $categoryData = [
            'name' => 'Electronics',
            'description' => 'Electronic products and gadgets',
            'type' => 'product',
            'sort_order' => 1,
            'color' => '#3498db',
            'icon' => 'electronics'
        ];

        $response = $this->postJson('/api/v1/categories', $categoryData);

        $response->assertCreated()
                ->assertJsonStructure([
                    'id',
                    'name',
                    'description',
                    'type',
                    'sort_order',
                    'color',
                    'icon',
                    'parent_id',
                    'created_at',
                    'updated_at'
                ])
                ->assertJson([
                    'name' => 'Electronics',
                    'type' => 'product',
                    'sort_order' => 1,
                    'color' => '#3498db'
                ]);

        $this->assertDatabaseHas('categories', [
            'company_id' => $this->company->id,
            'name' => 'Electronics',
            'type' => 'product'
        ]);
    }

    public function test_can_create_subcategory(): void
    {
        $parentCategory = Category::factory()->for($this->company)->create([
            'name' => 'Electronics',
            'type' => 'product'
        ]);

        $categoryData = [
            'name' => 'Smartphones',
            'description' => 'Mobile phones and accessories',
            'type' => 'product',
            'parent_id' => $parentCategory->id
        ];

        $response = $this->postJson('/api/v1/categories', $categoryData);

        $response->assertCreated()
                ->assertJson([
                    'name' => 'Smartphones',
                    'parent_id' => $parentCategory->id
                ]);

        $this->assertDatabaseHas('categories', [
            'company_id' => $this->company->id,
            'name' => 'Smartphones',
            'parent_id' => $parentCategory->id
        ]);
    }

    public function test_can_show_category(): void
    {
        $category = Category::factory()->for($this->company)->create();

        $response = $this->getJson("/api/v1/categories/{$category->id}");

        $response->assertOk()
                ->assertJsonStructure([
                    'id',
                    'name',
                    'description',
                    'type',
                    'sort_order',
                    'parent_id',
                    'created_at',
                    'updated_at'
                ])
                ->assertJson([
                    'id' => $category->id,
                    'name' => $category->name
                ]);
    }

    public function test_can_update_category(): void
    {
        $category = Category::factory()->for($this->company)->create();
        
        $updateData = [
            'name' => 'Updated Category Name',
            'description' => 'Updated description',
            'color' => '#e74c3c'
        ];

        $response = $this->putJson("/api/v1/categories/{$category->id}", $updateData);

        $response->assertOk()
                ->assertJson([
                    'id' => $category->id,
                    'name' => 'Updated Category Name',
                    'description' => 'Updated description',
                    'color' => '#e74c3c'
                ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Category Name'
        ]);
    }

    public function test_can_delete_empty_category(): void
    {
        $category = Category::factory()->for($this->company)->create();

        $response = $this->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_cannot_delete_category_with_products(): void
    {
        $category = Category::factory()->for($this->company)->create(['type' => 'product']);
        Product::factory()->for($this->company)->for($category)->create();

        $response = $this->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_cannot_delete_category_with_services(): void
    {
        $category = Category::factory()->for($this->company)->create(['type' => 'service']);
        Service::factory()->for($this->company)->for($category)->create();

        $response = $this->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_cannot_delete_category_with_children(): void
    {
        $parentCategory = Category::factory()->for($this->company)->create();
        Category::factory()->for($this->company)->create(['parent_id' => $parentCategory->id]);

        $response = $this->deleteJson("/api/v1/categories/{$parentCategory->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('categories', ['id' => $parentCategory->id]);
    }

    public function test_can_filter_categories_by_type(): void
    {
        Category::factory()->for($this->company)->create(['type' => 'product']);
        Category::factory()->for($this->company)->create(['type' => 'service']);

        $response = $this->getJson('/api/v1/categories?filter[type]=product');

        $response->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.type', 'product');
    }

    public function test_can_filter_categories_by_parent(): void
    {
        $parentCategory = Category::factory()->for($this->company)->create();
        Category::factory()->for($this->company)->create(['parent_id' => $parentCategory->id]);
        Category::factory()->for($this->company)->create(); // Root category

        $response = $this->getJson("/api/v1/categories?filter[parent_id]={$parentCategory->id}");

        $response->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.parent_id', $parentCategory->id);
    }

    public function test_can_filter_root_categories(): void
    {
        $parentCategory = Category::factory()->for($this->company)->create();
        Category::factory()->for($this->company)->create(['parent_id' => $parentCategory->id]);
        Category::factory()->for($this->company)->create(); // Root category

        $response = $this->getJson('/api/v1/categories?filter[parent_id]=null');

        $response->assertOk()
                ->assertJsonCount(2, 'data'); // parent + root
    }

    public function test_can_search_categories(): void
    {
        Category::factory()->for($this->company)->create([
            'name' => 'Electronics',
            'description' => 'Electronic devices and gadgets'
        ]);
        Category::factory()->for($this->company)->create([
            'name' => 'Clothing',
            'description' => 'Fashion and apparel'
        ]);

        $response = $this->getJson('/api/v1/categories?search=electronic');

        $response->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.name', 'Electronics');
    }

    public function test_categories_sorted_by_sort_order_and_name(): void
    {
        Category::factory()->for($this->company)->create(['name' => 'Zebra', 'sort_order' => 2]);
        Category::factory()->for($this->company)->create(['name' => 'Alpha', 'sort_order' => 1]);
        Category::factory()->for($this->company)->create(['name' => 'Beta', 'sort_order' => 1]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
                ->assertJsonPath('data.0.name', 'Alpha') // sort_order 1, alphabetically first
                ->assertJsonPath('data.1.name', 'Beta')  // sort_order 1, alphabetically second
                ->assertJsonPath('data.2.name', 'Zebra'); // sort_order 2
    }

    public function test_unauthorized_access_returns_401(): void
    {
        Sanctum::actingAs(null);

        $response = $this->getJson('/api/v1/categories');

        $response->assertUnauthorized();
    }

    public function test_forbidden_access_returns_403(): void
    {
        $otherCompany = Company::factory()->create();
        $category = Category::factory()->for($otherCompany)->create();

        $response = $this->getJson("/api/v1/categories/{$category->id}");

        $response->assertForbidden();
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/v1/categories', []);

        $response->assertUnprocessable()
                ->assertJsonValidationErrors(['name', 'type']);
    }

    public function test_unique_name_per_company_and_parent_validation(): void
    {
        Category::factory()->for($this->company)->create([
            'name' => 'Electronics',
            'parent_id' => null
        ]);

        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Electronics',
            'type' => 'product',
            'parent_id' => null
        ]);

        $response->assertUnprocessable()
                ->assertJsonValidationErrors(['name']);
    }

    public function test_invalid_color_format_validation(): void
    {
        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Test Category',
            'type' => 'product',
            'color' => 'invalid-color'
        ]);

        $response->assertUnprocessable()
                ->assertJsonValidationErrors(['color']);
    }

    public function test_circular_reference_prevention(): void
    {
        $category1 = Category::factory()->for($this->company)->create();
        $category2 = Category::factory()->for($this->company)->create(['parent_id' => $category1->id]);

        // Try to make category1 a child of category2 (would create circular reference)
        $response = $this->putJson("/api/v1/categories/{$category1->id}", [
            'parent_id' => $category2->id
        ]);

        $response->assertUnprocessable()
                ->assertJsonValidationErrors(['parent_id']);
    }

    public function test_can_include_relations(): void
    {
        $parentCategory = Category::factory()->for($this->company)->create();
        $childCategory = Category::factory()->for($this->company)->create(['parent_id' => $parentCategory->id]);
        Product::factory()->for($this->company)->for($childCategory)->create();

        $response = $this->getJson("/api/v1/categories/{$childCategory->id}?include=parent,products");

        $response->assertOk()
                ->assertJsonStructure([
                    'parent' => [
                        'id',
                        'name'
                    ],
                    'products' => [
                        '*' => [
                            'id',
                            'name'
                        ]
                    ]
                ]);
    }

    public function test_counts_are_included_in_index(): void
    {
        $category = Category::factory()->for($this->company)->create(['type' => 'both']);
        Product::factory()->count(2)->for($this->company)->for($category)->create();
        Service::factory()->count(3)->for($this->company)->for($category)->create();

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
                ->assertJsonPath('data.0.products_count', 2)
                ->assertJsonPath('data.0.services_count', 3)
                ->assertJsonPath('data.0.total_items', 5);
    }
}
