<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

final class VatRate
{
    private float $rate;
    private string $countryCode;

    public function __construct(float $rate, string $countryCode = 'FR')
    {
        if ($rate < 0) {
            throw new InvalidArgumentException('VAT rate cannot be negative');
        }
        
        if (strlen($countryCode) !== 2) {
            throw new InvalidArgumentException('Country code must be exactly 2 characters');
        }
        
        $this->rate = $rate;
        $this->countryCode = strtoupper($countryCode);
    }

    public static function fromFloat(float $rate, string $countryCode = 'FR'): self
    {
        return new self($rate, $countryCode);
    }

    /**
     * Create a French standard VAT rate (20%)
     */
    public static function frenchStandard(): self
    {
        return new self(20.0, 'FR');
    }

    /**
     * Create a French reduced VAT rate (10%)
     */
    public static function frenchReduced(): self
    {
        return new self(10.0, 'FR');
    }

    /**
     * Create a French super-reduced VAT rate (5.5%)
     */
    public static function frenchSuperReduced(): self
    {
        return new self(5.5, 'FR');
    }

    /**
     * Create a French special VAT rate (2.1%)
     */
    public static function frenchSpecial(): self
    {
        return new self(2.1, 'FR');
    }

    /**
     * Create a zero VAT rate
     */
    public static function zero(string $countryCode = 'FR'): self
    {
        return new self(0.0, $countryCode);
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function calculate(float $amount): float
    {
        return $amount * ($this->rate / 100);
    }

    public function equals(VatRate $other): bool
    {
        return $this->rate === $other->rate && $this->countryCode === $other->countryCode;
    }

    public function getPercentageString(): string
    {
        return number_format($this->rate, 2) . '%';
    }

    public function __toString(): string
    {
        return $this->getPercentageString();
    }
}
