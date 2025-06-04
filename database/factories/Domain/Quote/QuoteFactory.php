<?php

namespace Database\Factories\Domain\Quote;

use App\Domain\Company\Models\Company;
use App\Domain\Customer\Models\Client;
use App\Domain\Quote\Models\Quote;
use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Quote\Models\Quote>
 */
class QuoteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Quote::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quoteDate = $this->faker->dateTimeBetween('-3 months', 'now');
        $validUntil = (clone $quoteDate)->modify('+30 days');
        
        $subtotal = $this->faker->randomFloat(2, 100, 5000);
        $discountValue = $this->faker->boolean(30) ? $this->faker->randomFloat(2, 0, $subtotal * 0.2) : 0;
        $shippingAmount = $this->faker->boolean(20) ? $this->faker->randomFloat(2, 10, 100) : 0;
        $taxAmount = ($subtotal - $discountValue + $shippingAmount) * 0.2; // TVA 20%
        $total = $subtotal - $discountValue + $shippingAmount + $taxAmount;

        return [
            'company_id' => Company::factory(),
            'customer_id' => Client::factory(),
            'quote_number' => $this->generateQuoteNumber(),
            'quote_date' => $quoteDate,
            'valid_until' => $validUntil,
            'subject' => $this->faker->sentence(),
            'notes' => $this->faker->boolean(50) ? $this->faker->paragraph() : null,
            'terms' => $this->faker->boolean(30) ? $this->faker->text(500) : null,
            'status' => $this->faker->randomElement(array_column(QuoteStatus::cases(), 'value')),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'discount_type' => $discountValue > 0 ? $this->faker->randomElement(['percentage', 'fixed']) : null,
            'discount_value' => $discountValue,
            'shipping_amount' => $shippingAmount,
            'sent_at' => $this->faker->boolean(60) ? $this->faker->dateTimeBetween($quoteDate, 'now') : null,
        ];
    }

    /**
     * Generate a unique quote number
     */
    private function generateQuoteNumber(): string
    {
        $year = date('Y');
        $number = $this->faker->unique()->numberBetween(1, 9999);
        return sprintf('DEV-%s-%04d', $year, $number);
    }

    /**
     * Indicate that the quote is in draft status.
     */
    public function draft(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => QuoteStatus::DRAFT->value,
                'sent_at' => null,
            ];
        });
    }

    /**
     * Indicate that the quote has been sent.
     */
    public function sent(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => QuoteStatus::SENT->value,
                'sent_at' => $this->faker->dateTimeBetween($attributes['quote_date'], 'now'),
            ];
        });
    }

    /**
     * Indicate that the quote is pending.
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => QuoteStatus::PENDING->value,
                'sent_at' => $this->faker->dateTimeBetween($attributes['quote_date'], 'now'),
            ];
        });
    }

    /**
     * Indicate that the quote has been accepted.
     */
    public function accepted(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => QuoteStatus::ACCEPTED->value,
                'sent_at' => $this->faker->dateTimeBetween($attributes['quote_date'], '-1 week'),
            ];
        });
    }

    /**
     * Indicate that the quote has been declined.
     */
    public function declined(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => QuoteStatus::DECLINED->value,
                'sent_at' => $this->faker->dateTimeBetween($attributes['quote_date'], '-1 week'),
            ];
        });
    }

    /**
     * Indicate that the quote has expired.
     */
    public function expired(): static
    {
        return $this->state(function (array $attributes) {
            $pastDate = $this->faker->dateTimeBetween('-2 months', '-1 month');
            return [
                'status' => QuoteStatus::EXPIRED->value,
                'quote_date' => $pastDate,
                'valid_until' => (clone $pastDate)->modify('+30 days'),
                'sent_at' => $this->faker->dateTimeBetween($pastDate, (clone $pastDate)->modify('+1 week')),
            ];
        });
    }

    /**
     * Indicate that the quote has been converted to invoice.
     */
    public function converted(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => QuoteStatus::CONVERTED->value,
                'sent_at' => $this->faker->dateTimeBetween($attributes['quote_date'], '-1 week'),
            ];
        });
    }

    /**
     * Indicate that the quote has a specific total amount.
     */
    public function withTotal(float $total): static
    {
        return $this->state(function (array $attributes) use ($total) {
            $subtotal = $total / 1.2; // Assuming 20% tax
            $taxAmount = $total - $subtotal;
            
            return [
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'discount_value' => 0,
                'shipping_amount' => 0,
            ];
        });
    }

    /**
     * Indicate that the quote has a discount.
     */
    public function withDiscount(float $discountValue, string $discountType = 'fixed'): static
    {
        return $this->state(function (array $attributes) use ($discountValue, $discountType) {
            $subtotal = $attributes['subtotal'];
            $actualDiscount = $discountType === 'percentage' 
                ? $subtotal * ($discountValue / 100)
                : $discountValue;
            
            $taxableAmount = $subtotal - $actualDiscount + $attributes['shipping_amount'];
            $taxAmount = $taxableAmount * 0.2;
            $total = $taxableAmount + $taxAmount;
            
            return [
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'tax_amount' => $taxAmount,
                'total' => $total,
            ];
        });
    }

    /**
     * Indicate that the quote is for a specific company.
     */
    public function forCompany(Company $company): static
    {
        return $this->state(function (array $attributes) use ($company) {
            return [
                'company_id' => $company->id,
                'quote_number' => $this->generateQuoteNumberForCompany($company),
            ];
        });
    }

    /**
     * Indicate that the quote is for a specific customer.
     */
    public function forCustomer(Client $customer): static
    {
        return $this->state(function (array $attributes) use ($customer) {
            return [
                'customer_id' => $customer->id,
                'company_id' => $customer->company_id,
            ];
        });
    }

    /**
     * Generate a quote number for a specific company
     */
    private function generateQuoteNumberForCompany(Company $company): string
    {
        $prefix = $company->quote_prefix ?? 'DEV';
        $year = date('Y');
        $number = $this->faker->unique()->numberBetween(1, 9999);
        return sprintf('%s-%s-%04d', $prefix, $year, $number);
    }
}
