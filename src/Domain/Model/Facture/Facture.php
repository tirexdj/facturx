<?php

declare(strict_types=1);

namespace App\Domain\Model\Facture;

use App\Domain\Event\FactureCreatedEvent;
use App\Domain\Event\FactureDeletedEvent;
use App\Domain\Event\FacturePaidEvent;
use App\Domain\Event\FactureTransmittedEvent;
use App\Domain\Event\FactureUpdatedEvent;
use App\Domain\ValueObject\Identifier;
use App\Domain\ValueObject\Money;
use DateTimeImmutable;
use InvalidArgumentException;

class Facture
{
    private Identifier $id;
    private Identifier $companyId;
    private Identifier $clientId;
    private ?Identifier $quoteId;
    private string $invoiceNumber;
    private ?string $reference;
    private ?string $title;
    private ?string $introduction;
    private DateTimeImmutable $date;
    private DateTimeImmutable $dueDate;
    private string $status;
    private ?string $eInvoiceStatus;
    private ?string $paymentStatus;
    private string $currencyCode;
    private float $exchangeRate;
    private Money $subtotalNet;
    private ?string $discountType;
    private ?float $discountValue;
    private ?Money $discountAmount;
    private Money $totalNet;
    private Money $totalTax;
    private Money $totalGross;
    private Money $amountPaid;
    private Money $amountDue;
    private ?string $notes;
    private ?string $terms;
    private ?string $footer;
    private ?string $pdfPath;
    private ?DateTimeImmutable $sentAt;
    private ?DateTimeImmutable $paidAt;
    private ?string $eInvoiceFormat;
    private ?array $eInvoiceData;
    private ?string $eInvoicePath;
    private ?string $eReportingStatus;
    private ?DateTimeImmutable $eReportingTransmittedAt;
    private bool $isRecurring;
    private ?Identifier $recurrenceId;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;
    private ?DateTimeImmutable $deletedAt;
    private ?Identifier $createdBy;
    private ?Identifier $updatedBy;
    private ?Identifier $deletedBy;
    
    /** @var LigneFacture[] */
    private array $lines = [];
    
    private array $events = [];
    
    public function __construct(
        Identifier $id,
        Identifier $companyId,
        Identifier $clientId,
        string $invoiceNumber,
        DateTimeImmutable $date,
        DateTimeImmutable $dueDate,
        string $currencyCode,
        ?Identifier $quoteId = null,
        ?string $reference = null,
        ?string $title = null,
        ?string $introduction = null,
        ?Identifier $createdBy = null
    ) {
        if (empty(trim($invoiceNumber))) {
            throw new InvalidArgumentException('Invoice number cannot be empty');
        }
        
        $this->id = $id;
        $this->companyId = $companyId;
        $this->clientId = $clientId;
        $this->quoteId = $quoteId;
        $this->invoiceNumber = $invoiceNumber;
        $this->reference = $reference;
        $this->title = $title;
        $this->introduction = $introduction;
        $this->date = $date;
        $this->dueDate = $dueDate;
        $this->status = 'draft';
        $this->eInvoiceStatus = null;
        $this->paymentStatus = null;
        $this->currencyCode = $currencyCode;
        $this->exchangeRate = 1.0;
        $this->subtotalNet = new Money(0, $currencyCode);
        $this->discountType = null;
        $this->discountValue = null;
        $this->discountAmount = null;
        $this->totalNet = new Money(0, $currencyCode);
        $this->totalTax = new Money(0, $currencyCode);
        $this->totalGross = new Money(0, $currencyCode);
        $this->amountPaid = new Money(0, $currencyCode);
        $this->amountDue = new Money(0, $currencyCode);
        $this->notes = null;
        $this->terms = null;
        $this->footer = null;
        $this->pdfPath = null;
        $this->sentAt = null;
        $this->paidAt = null;
        $this->eInvoiceFormat = null;
        $this->eInvoiceData = null;
        $this->eInvoicePath = null;
        $this->eReportingStatus = null;
        $this->eReportingTransmittedAt = null;
        $this->isRecurring = false;
        $this->recurrenceId = null;
        $this->createdAt = new DateTimeImmutable();
        $this->createdBy = $createdBy;
        
        $this->events[] = new FactureCreatedEvent($this->id);
    }
    
    public static function create(
        Identifier $companyId,
        Identifier $clientId,
        string $invoiceNumber,
        DateTimeImmutable $date,
        DateTimeImmutable $dueDate,
        string $currencyCode = 'EUR',
        ?Identifier $quoteId = null,
        ?string $reference = null,
        ?string $title = null,
        ?string $introduction = null,
        ?Identifier $createdBy = null
    ): self {
        return new self(
            Identifier::generate(),
            $companyId,
            $clientId,
            $invoiceNumber,
            $date,
            $dueDate,
            $currencyCode,
            $quoteId,
            $reference,
            $title,
            $introduction,
            $createdBy
        );
    }
    
    public function addLine(LigneFacture $line): void
    {
        $this->lines[] = $line;
        $this->recalculateTotals();
    }
    
    public function removeLine(Identifier $lineId): void
    {
        foreach ($this->lines as $key => $line) {
            if ($line->getId()->equals($lineId)) {
                unset($this->lines[$key]);
                $this->lines = array_values($this->lines);
                $this->recalculateTotals();
                return;
            }
        }
        
        throw new InvalidArgumentException("Line with ID {$lineId} not found in this invoice");
    }
    
    private function recalculateTotals(): void
    {
        $subtotalNet = 0;
        $totalTax = 0;
        
        foreach ($this->lines as $line) {
            $subtotalNet += $line->getTotalNet()->getAmount();
            $totalTax += $line->getTaxAmount()->getAmount();
        }
        
        $this->subtotalNet = new Money($subtotalNet, $this->currencyCode);
        
        // Apply discount if applicable
        $totalNet = $subtotalNet;
        if ($this->discountType && $this->discountValue) {
            if ($this->discountType === 'percent') {
                $discountAmount = $subtotalNet * ($this->discountValue / 100);
            } else { // amount
                $discountAmount = $this->discountValue;
            }
            
            $this->discountAmount = new Money($discountAmount, $this->currencyCode);
            $totalNet = $subtotalNet - $discountAmount;
        }
        
        $this->totalNet = new Money($totalNet, $this->currencyCode);
        $this->totalTax = new Money($totalTax, $this->currencyCode);
        $this->totalGross = new Money($totalNet + $totalTax, $this->currencyCode);
        $this->amountDue = new Money($this->totalGross->getAmount() - $this->amountPaid->getAmount(), $this->currencyCode);
        
        // Update payment status based on amounts
        $this->updatePaymentStatus();
    }
    
    private function updatePaymentStatus(): void
    {
        if ($this->amountPaid->getAmount() <= 0) {
            $this->paymentStatus = null;
        } elseif ($this->amountPaid->getAmount() >= $this->totalGross->getAmount()) {
            $this->paymentStatus = 'paid';
            $this->status = 'paid';
            $this->paidAt = new DateTimeImmutable();
            $this->events[] = new FacturePaidEvent($this->id);
        } else {
            $this->paymentStatus = 'partial';
            $this->status = 'partial';
        }
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
        
        $this->recalculateTotals();
    }
    
    public function recordPayment(Money $amount, DateTimeImmutable $date): void
    {
        if ($amount.getCurrency() !== $this->currencyCode) {
            throw new InvalidArgumentException("Payment currency {$amount->getCurrency()} does not match invoice currency {$this->currencyCode}");
        }
        
        $this->amountPaid = new Money($this->amountPaid->getAmount() + $amount->getAmount(), $this->currencyCode);
        
        $this->recalculateTotals();
        
        if ($this->paymentStatus === 'paid') {
            $this->paidAt = $date;
        }
    }
    
    public function send(DateTimeImmutable $sentAt): void
    {
        if ($this->status !== 'draft') {
            throw new InvalidArgumentException("Cannot send invoice with status {$this->status}");
        }
        
        $this->status = 'sent';
        $this->sentAt = $sentAt;
        
        $this->events[] = new FactureUpdatedEvent($this->id);
    }
    
    public function markAsTransmitted(string $eInvoiceFormat, ?array $eInvoiceData = null, ?string $eInvoicePath = null): void
    {
        if (!in_array($eInvoiceFormat, ['UBL', 'CII', 'Factur-X'])) {
            throw new InvalidArgumentException("Invalid e-invoice format: {$eInvoiceFormat}");
        }
        
        $this->eInvoiceStatus = 'transmitted';
        $this->eInvoiceFormat = $eInvoiceFormat;
        $this->eInvoiceData = $eInvoiceData;
        $this->eInvoicePath = $eInvoicePath;
        
        $this->events[] = new FactureTransmittedEvent($this->id);
    }
    
    public function markAsAccepted(): void
    {
        if ($this->eInvoiceStatus !== 'transmitted') {
            throw new InvalidArgumentException("Cannot mark invoice as accepted when not in 'transmitted' status");
        }
        
        $this->eInvoiceStatus = 'accepted';
    }
    
    public function markAsRejected(): void
    {
        if ($this->eInvoiceStatus !== 'transmitted') {
            throw new InvalidArgumentException("Cannot mark invoice as rejected when not in 'transmitted' status");
        }
        
        $this->eInvoiceStatus = 'rejected';
    }
    
    public function markAsOverdue(): void
    {
        if (!in_array($this->status, ['sent', 'partial'])) {
            throw new InvalidArgumentException("Cannot mark invoice as overdue when in status {$this->status}");
        }
        
        $this->status = 'overdue';
    }
    
    public function cancel(?Identifier $updatedBy = null): void
    {
        if (in_array($this->status, ['paid', 'cancelled'])) {
            throw new InvalidArgumentException("Cannot cancel invoice with status {$this->status}");
        }
        
        $this->status = 'cancelled';
        $this->updatedAt = new DateTimeImmutable();
        $this->updatedBy = $updatedBy;
        
        $this->events[] = new FactureUpdatedEvent($this->id);
    }
    
    public function delete(?Identifier $deletedBy = null): void
    {
        if ($this->status !== 'draft') {
            throw new InvalidArgumentException("Only draft invoices can be deleted");
        }
        
        $this->deletedAt = new DateTimeImmutable();
        $this->deletedBy = $deletedBy;
        
        $this->events[] = new FactureDeletedEvent($this->id);
    }
    
    public function markEReported(DateTimeImmutable $transmittedAt): void
    {
        $this->eReportingStatus = 'transmitted';
        $this->eReportingTransmittedAt = $transmittedAt;
    }
    
    public function setRecurring(bool $isRecurring, ?Identifier $recurrenceId = null): void
    {
        $this->isRecurring = $isRecurring;
        $this->recurrenceId = $recurrenceId;
    }
    
    // Getters
    public function getId(): Identifier
    {
        return $this->id;
    }
    
    public function getCompanyId(): Identifier
    {
        return $this->companyId;
    }
    
    public function getClientId(): Identifier
    {
        return $this->clientId;
    }
    
    public function getQuoteId(): ?Identifier
    {
        return $this->quoteId;
    }
    
    public function getInvoiceNumber(): string
    {
        return $this->invoiceNumber;
    }
    
    public function getReference(): ?string
    {
        return $this->reference;
    }
    
    public function getTitle(): ?string
    {
        return $this->title;
    }
    
    public function getIntroduction(): ?string
    {
        return $this->introduction;
    }
    
    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }
    
    public function getDueDate(): DateTimeImmutable
    {
        return $this->dueDate;
    }
    
    public function getStatus(): string
    {
        return $this->status;
    }
    
    public function getEInvoiceStatus(): ?string
    {
        return $this->eInvoiceStatus;
    }
    
    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus;
    }
    
    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }
    
    public function getExchangeRate(): float
    {
        return $this->exchangeRate;
    }
    
    public function getSubtotalNet(): Money
    {
        return $this->subtotalNet;
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
    
    public function getTotalNet(): Money
    {
        return $this->totalNet;
    }
    
    public function getTotalTax(): Money
    {
        return $this->totalTax;
    }
    
    public function getTotalGross(): Money
    {
        return $this->totalGross;
    }
    
    public function getAmountPaid(): Money
    {
        return $this->amountPaid;
    }
    
    public function getAmountDue(): Money
    {
        return $this->amountDue;
    }
    
    public function getNotes(): ?string
    {
        return $this->notes;
    }
    
    public function getTerms(): ?string
    {
        return $this->terms;
    }
    
    public function getFooter(): ?string
    {
        return $this->footer;
    }
    
    public function getPdfPath(): ?string
    {
        return $this->pdfPath;
    }
    
    public function getSentAt(): ?DateTimeImmutable
    {
        return $this->sentAt;
    }
    
    public function getPaidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }
    
    public function getEInvoiceFormat(): ?string
    {
        return $this->eInvoiceFormat;
    }
    
    public function getEInvoiceData(): ?array
    {
        return $this->eInvoiceData;
    }
    
    public function getEInvoicePath(): ?string
    {
        return $this->eInvoicePath;
    }
    
    public function getEReportingStatus(): ?string
    {
        return $this->eReportingStatus;
    }
    
    public function getEReportingTransmittedAt(): ?DateTimeImmutable
    {
        return $this->eReportingTransmittedAt;
    }
    
    public function isRecurring(): bool
    {
        return $this->isRecurring;
    }
    
    public function getRecurrenceId(): ?Identifier
    {
        return $this->recurrenceId;
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
    
    /**
     * @return LigneFacture[]
     */
    public function getLines(): array
    {
        return $this->lines;
    }
    
    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];
        return $events;
    }
}
