<?php

declare(strict_types=1);

namespace App\Domain\Model\Facture;

use App\Domain\ValueObject\Identifier;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\VatRate;
use DateTimeImmutable;
use InvalidArgumentException;

class LigneFacture
{
    private Identifier $id;
    private Identifier $invoiceId;
    private string $lineType;
    private ?Identifier $productId;
    private ?Identifier $serviceId;
    private ?Identifier $productVariantId;
    private string $title;
    private ?string $description;
    private float $quantity;
    private Identifier $unitId;
    private Money $unitPriceNet;
    private VatRate $vatRate;
    private ?string $discountType;
    private ?float $discountValue;
    private ?Money $discountAmount;
    private Money $subtotalNet;
    private Money $taxAmount;
    private Money $totalNet;
    private int $position;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;
    private ?DateTimeImmutable $deletedAt;
    private ?Identifier $createdBy;
    private ?Identifier $updatedBy;
    private ?Identifier $deletedBy;

    public function __construct(
        Identifier $id,
        Identifier $invoiceId,
        string $lineType,
        string $title,
        float $quantity,
        Identifier $unitId,
        Money $unitPriceNet,
        VatRate $vatRate,
        ?string $description = null,
        ?Identifier $productId = null,
        ?Identifier $serviceId = null,
        ?Identifier $productVariantId = null,
        int $position = 0,
        ?Identifier $createdBy = null
    ) {
        if (!in_array($lineType, ['product', 'service', 'text', 'section'])) {
            throw new InvalidArgumentException("Invalid line type: {$lineType}");
        }
        
        if (empty(trim($title))) {
            throw new InvalidArgumentException('Line title cannot be empty');
        }
        
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero');
        }
        
        $this->id = $id;
        $this->invoiceId = $invoiceId;
        $this->lineType = $lineType;
        $this->title = $title;
        $this->description = $description;
        $this->quantity = $quantity;
        $this->unitId = $unitId;
        $this->unitPriceNet = $unitPriceNet;
        $this->vatRate = $vatRate;
        $this->productId = $productId;
        $this->serviceId = $serviceId;
        $this->productVariantId = $productVariantId;
        $this->position = $position;
        $this->discountType = null;
        $this->discountValue = null;
        $this->discountAmount = null;
        $this->createdAt = new DateTimeImmutable();
        $this->createdBy = $createdBy;
        
        // Calculate amounts
        $this->recalculateAmounts();
    }

    public static function create(
        Identifier $invoiceId,
        string $lineType,
        string $title,
        float $quantity,
        Identifier $unitId,
        Money $unitPriceNet,
        VatRate $vatRate,
        ?string $description = null,
        ?Identifier $productId = null,
        ?Identifier $serviceId = null,
        ?Identifier $productVariantId = null,
        int $position = 0,
        ?Identifier $createdBy = null
    ): self {
        return new self(
            Identifier::generate(),
            $invoiceId,
            $lineType,
            $title,
            $quantity,
            $unitId,
            $unitPriceNet,
            $vatRate,
            $description,
            $productId,
            $serviceId,
            $productVariantId,
            $position,
            $createdBy
        );
    }

    public function update(
        string $title,
        float $quantity,
        Money $unitPriceNet,
        VatRate $vatRate,
        ?string $description = null,
        ?Identifier $updatedBy = null
    ): void {
        if (empty(trim($title))) {
            throw new InvalidArgumentException('Line title cannot be empty');
        }
        
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero');
        }
        
        $this->title = $title;
        $this->description = $description;
        $this->quantity = $quantity;
        $this->unitPriceNet = $unitPriceNet;
        $this->vatRate = $vatRate;
        $this->updatedAt = new DateTimeImmutable();
        $this->updatedBy = $updatedBy;
        
        // Recalculate amounts after update
        $this->recalculateAmounts();
    }

    public function setDiscount(?string $discountType, ?float $discountValue): void
    {
        if ($discountType !== null && !in_array($discountType, ['percent', 'amount'])) {
            throw new InvalidArgumentException("Invalid discount type: {$discountType}");
        }
        
        if ($discountValue !== null && $discountValue < 0) {
            throw new InvalidArgumentException('Discount value cannot be negative');
        }
        
        $this->discountType = $discountType;
        $this->discountValue = $discountValue;
        
        // Recalculate amounts after setting discount
        $this->recalculateAmounts();
    }

    private function recalculateAmounts(): void
    {
        $subtotalNet = $this->unitPriceNet->getAmount() * $this->quantity;
        
        // Apply discount if applicable
        if ($this->discountType && $this->discountValue) {
            $discountAmount = 0;
            
            if ($this->discountType === 'percent') {
                $discountAmount = $subtotalNet * ($this->discountValue / 100);
            } else { // amount
                $discountAmount = $this->discountValue;
            }
            
            $this->discountAmount = new Money($discountAmount, $this->unitPriceNet->getCurrency());
            $subtotalNet -= $discountAmount;
        } else {
            $this->discountAmount = null;
        }
        
        $this->subtotalNet = new Money($subtotalNet, $this->unitPriceNet->getCurrency());
        $this->totalNet = $this->subtotalNet;
        
        // Calculate tax amount
        $taxAmount = $this->vatRate->calculate($subtotalNet);
        $this->taxAmount = new Money($taxAmount, $this->unitPriceNet->getCurrency());
    }

    public function delete(?Identifier $deletedBy = null): void
    {
        $this->deletedAt = new DateTimeImmutable();
        $this->deletedBy = $deletedBy;
    }

    // Getters
    public function getId(): Identifier
    {
        return $this->id;
    }

    public function getInvoiceId(): Identifier
    {
        return $this->invoiceId;
    }

    public function getLineType(): string
    {
        return $this->lineType;
    }

    public function getProductId(): ?Identifier
    {
        return $this->productId;
    }

    public function getServiceId(): ?Identifier
    {
        return $this->serviceId;
    }

    public function getProductVariantId(): ?Identifier
    {
        return $this->productVariantId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function getUnitId(): Identifier
    {
        return $this->unitId;
    }

    public function getUnitPriceNet(): Money
    {
        return $this->unitPriceNet;
    }

    public function getVatRate(): VatRate
    {
        return $this->vatRate;
    }

    public function getDiscountType(): ?string
    {
        return $this->discountType;
    }

    public function getDiscountValue(): ?float
    {
        return $this->discountValue;
    }

    public function getDiscountAmount(): ?Money
    {
        return $this->discountAmount;
    }

    public function getSubtotalNet(): Money
    {
        return $this->subtotalNet;
    }

    public function getTaxAmount(): Money
    {
        return $this->taxAmount;
    }

    public function getTotalNet(): Money
    {
        return $this->totalNet;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function getCreatedBy(): ?Identifier
    {
        return $this->createdBy;
    }

    public function getUpdatedBy(): ?Identifier
    {
        return $this->updatedBy;
    }

    public function getDeletedBy(): ?Identifier
    {
        return $this->deletedBy;
    }
}
