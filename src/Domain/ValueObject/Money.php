<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

final class Money
{
    private float $amount;
    private string $currency;

    public function __construct(float $amount, string $currency = 'EUR')
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Money amount cannot be negative');
        }
        
        if (strlen($currency) !== 3) {
            throw new InvalidArgumentException('Currency code must be exactly 3 characters');
        }
        
        $this->amount = $amount;
        $this->currency = strtoupper($currency);
    }

    public static function fromFloat(float $amount, string $currency = 'EUR'): self
    {
        return new self($amount, $currency);
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function add(Money $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException("Cannot add Money with different currencies: {$this->currency} and {$other->currency}");
        }
        
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException("Cannot subtract Money with different currencies: {$this->currency} and {$other->currency}");
        }
        
        $result = $this->amount - $other->amount;
        if ($result < 0) {
            throw new InvalidArgumentException('Result of money subtraction cannot be negative');
        }
        
        return new self($result, $this->currency);
    }

    public function multiply(float $multiplier): self
    {
        if ($multiplier < 0) {
            throw new InvalidArgumentException('Multiplier cannot be negative');
        }
        
        return new self($this->amount * $multiplier, $this->currency);
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function isGreaterThan(Money $other): bool
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException("Cannot compare Money with different currencies: {$this->currency} and {$other->currency}");
        }
        
        return $this->amount > $other->amount;
    }

    public function isLessThan(Money $other): bool
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException("Cannot compare Money with different currencies: {$this->currency} and {$other->currency}");
        }
        
        return $this->amount < $other->amount;
    }

    public function __toString(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }
}
