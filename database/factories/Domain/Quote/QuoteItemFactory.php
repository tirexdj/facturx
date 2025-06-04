<?php

namespace Database\Factories\Domain\Quote;

use App\Domain\Quote\Models\Quote;
use App\Domain\Quote\Models\QuoteItem;
use App\Domain\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Quote\Models\QuoteItem>
 */
class QuoteItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = QuoteItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(2, 1, 10);
        $unitPrice = $this->faker->randomFloat(2, 10, 500);
        $lineTotal = $quantity * $unitPrice;
        $taxRate = $this->faker->randomElement([0, 5.5, 10, 20]);

        return [
            'quote_id' => Quote::factory(),
            'product_id' => $this->faker->boolean(70) ? Product::factory() : null,
            'description' => $this->faker->sentence(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate' => $taxRate,
            'line_total' => $lineTotal,
        ];
    }

    /**
     * Indicate that the item is for a specific quote.
     */
    public function forQuote(Quote $quote): static
    {
        return $this->state(function (array $attributes) use ($quote) {
            return [
                'quote_id' => $quote->id,
            ];
        });
    }

    /**
     * Indicate that the item is linked to a specific product.
     */
    public function withProduct(Product $product): static
    {
        return $this->state(function (array $attributes) use ($product) {
            $quantity = $attributes['quantity'];
            $lineTotal = $quantity * $product->price;
            
            return [
                'product_id' => $product->id,
                'description' => $product->name,
                'unit_price' => $product->price,
                'tax_rate' => $product->tax_rate,
                'line_total' => $lineTotal,
            ];
        });
    }

    /**
     * Indicate that the item is a service (no product).
     */
    public function service(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'product_id' => null,
                'description' => $this->faker->randomElement([
                    'Consultation',
                    'Formation',
                    'Maintenance',
                    'Support technique',
                    'Développement',
                    'Prestation de service',
                    'Conseil',
                    'Audit'
                ]) . ' - ' . $this->faker->sentence(),
            ];
        });
    }

    /**
     * Indicate that the item has a specific price.
     */
    public function withPrice(float $unitPrice): static
    {
        return $this->state(function (array $attributes) use ($unitPrice) {
            $quantity = $attributes['quantity'];
            return [
                'unit_price' => $unitPrice,
                'line_total' => $quantity * $unitPrice,
            ];
        });
    }

    /**
     * Indicate that the item has a specific quantity.
     */
    public function withQuantity(float $quantity): static
    {
        return $this->state(function (array $attributes) use ($quantity) {
            $unitPrice = $attributes['unit_price'];
            return [
                'quantity' => $quantity,
                'line_total' => $quantity * $unitPrice,
            ];
        });
    }

    /**
     * Indicate that the item has a specific tax rate.
     */
    public function withTaxRate(float $taxRate): static
    {
        return $this->state(function (array $attributes) use ($taxRate) {
            return [
                'tax_rate' => $taxRate,
            ];
        });
    }

    /**
     * Indicate that the item is tax-exempt.
     */
    public function taxExempt(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'tax_rate' => 0,
            ];
        });
    }

    /**
     * Indicate that the item has standard VAT rate (20%).
     */
    public function standardVat(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'tax_rate' => 20,
            ];
        });
    }

    /**
     * Indicate that the item has reduced VAT rate (5.5%).
     */
    public function reducedVat(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'tax_rate' => 5.5,
            ];
        });
    }

    /**
     * Indicate that the item has intermediate VAT rate (10%).
     */
    public function intermediateVat(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'tax_rate' => 10,
            ];
        });
    }
}
