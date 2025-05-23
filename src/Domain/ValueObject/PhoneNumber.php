<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

final class PhoneNumber
{
    private string $countryCode;
    private string $number;
    private ?string $extension;

    public function __construct(string $countryCode, string $number, ?string $extension = null)
    {
        // Validate country code (simple validation)
        if (!preg_match('/^\+\d{1,4}$/', $countryCode)) {
            throw new InvalidArgumentException("Invalid country code: {$countryCode}");
        }
        
        // Validate phone number (simple validation for numeric characters, spaces, and dashes)
        if (!preg_match('/^[\d\s\-]+$/', $number)) {
            throw new InvalidArgumentException("Invalid phone number: {$number}");
        }
        
        // Validate extension if provided
        if ($extension !== null && !preg_match('/^[\d]+$/', $extension)) {
            throw new InvalidArgumentException("Invalid extension: {$extension}");
        }
        
        $this->countryCode = $countryCode;
        $this->number = $number;
        $this->extension = $extension;
    }

    public static function fromParts(string $countryCode, string $number, ?string $extension = null): self
    {
        return new self($countryCode, $number, $extension);
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function hasExtension(): bool
    {
        return $this->extension !== null;
    }

    public function equals(PhoneNumber $other): bool
    {
        return $this->countryCode === $other->countryCode && 
               $this->number === $other->number && 
               $this->extension === $other->extension;
    }

    public function getFormattedNumber(): string
    {
        $formatted = $this->countryCode . ' ' . $this->number;
        
        if ($this->extension) {
            $formatted .= ' ext. ' . $this->extension;
        }
        
        return $formatted;
    }

    public function __toString(): string
    {
        return $this->getFormattedNumber();
    }
}
