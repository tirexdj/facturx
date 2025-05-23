<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\Facture\Facture;
use App\Domain\ValueObject\Identifier;

interface InvoiceRepository
{
    /**
     * Find an invoice by its identifier
     */
    public function findById(Identifier $id): ?Facture;
    
    /**
     * Find invoices by company ID
     * 
     * @return Facture[]
     */
    public function findByCompanyId(Identifier $companyId): array;
    
    /**
     * Find invoices by client ID
     * 
     * @return Facture[]
     */
    public function findByClientId(Identifier $clientId): array;
    
    /**
     * Find an invoice by its number
     */
    public function findByInvoiceNumber(string $invoiceNumber): ?Facture;
    
    /**
     * Save an invoice
     */
    public function save(Facture $facture): void;
    
    /**
     * Delete an invoice
     */
    public function delete(Facture $facture): void;
    
    /**
     * Find invoices to be transmitted to PDP
     * 
     * @return Facture[]
     */
    public function findPendingTransmission(int $limit = 100): array;
    
    /**
     * Find overdue invoices
     * 
     * @return Facture[]
     */
    public function findOverdue(): array;
}
