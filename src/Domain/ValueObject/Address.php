<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

final class Address
{
    private string $addressLine1;
    private ?string $addressLine2;
    private ?string $addressLine3;
    private string $postalCode;
    private string $city;
    private ?string $stateProvince;
    private string $countryCode;

    public function __construct(
        string $addressLine1,
        ?string $addressLine2 = null,
        ?string $addressLine3 = null,
        string $postalCode,
        string $city,
        ?string $stateProvince = null,
        string $countryCode = 'FR'
    ) {
        if (empty(trim($addressLine1))) {
            throw new InvalidArgumentException('Address line 1 cannot be empty');
        }
        
        if (empty(trim($postalCode))) {
            throw new InvalidArgumentException('Postal code cannot be empty');
        }
        
        if (empty(trim($city))) {
            throw new InvalidArgumentException('City cannot be empty');
        }
        
        if (strlen($countryCode) !== 2) {
            throw new InvalidArgumentException('Country code must be exactly 2 characters');
        }
        
        $this->addressLine1 = trim($addressLine1);
        $this->addressLine2 = $addressLine2 ? trim($addressLine2) : null;
        $this->addressLine3 = $addressLine3 ? trim($addressLine3) : null;
        $this->postalCode = trim($postalCode);
        $this->city = trim($city);
        $this->stateProvince = $stateProvince ? trim($stateProvince) : null;
        $this->countryCode = strtoupper($countryCode);
    }

    public function getAddressLine1(): string
    {
        return $this->addressLine1;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    public function getAddressLine3(): ?string
    {
        return $this->addressLine3;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getStateProvince(): ?string
    {
        return $this->stateProvince;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function equals(Address $other): bool
    {
        return $this->addressLine1 === $other->addressLine1 &&
               $this->addressLine2 === $other->addressLine2 &&
               $this->addressLine3 === $other->addressLine3 &&
               $this->postalCode === $other->postalCode &&
               $this->city === $other->city &&
               $this->stateProvince === $other->stateProvince &&
               $this->countryCode === $other->countryCode;
    }

    public function getFormattedAddress(string $separator = "\n"): string
    {
        $lines = [$this->addressLine1];
        
        if ($this->addressLine2) {
            $lines[] = $this->addressLine2;
        }
        
        if ($this->addressLine3) {
            $lines[] = $this->addressLine3;
        }
        
        $cityLine = $this->postalCode . ' ' . $this->city;
        
        if ($this->stateProvince) {
            $cityLine = $this->stateProvince . ', ' . $cityLine;
        }
        
        $lines[] = $cityLine;
        
        return implode($separator, $lines);
    }

    public function __toString(): string
    {
        return $this->getFormattedAddress(', ');
    }
}
