<?php

namespace App\Domain\Shared\Enums;

enum DocumentType: string
{
    case QUOTE = 'quote';
    case PURCHASE_ORDER = 'purchase_order';
    case INVOICE = 'invoice';
    case CREDIT_NOTE = 'credit_note';
    case RECEIPT = 'receipt';

    public function label(): string
    {
        return match($this) {
            self::QUOTE => 'Devis',
            self::PURCHASE_ORDER => 'Bon de commande',
            self::INVOICE => 'Facture',
            self::CREDIT_NOTE => 'Avoir',
            self::RECEIPT => 'Reçu',
        };
    }

    public function prefix(): string
    {
        return match($this) {
            self::QUOTE => 'DEV',
            self::PURCHASE_ORDER => 'BC',
            self::INVOICE => 'FAC',
            self::CREDIT_NOTE => 'AV',
            self::RECEIPT => 'REC',
        };
    }

    public function isPaidDocument(): bool
    {
        return in_array($this, [self::QUOTE, self::PURCHASE_ORDER]);
    }

    public function template(): string
    {
        return match($this) {
            self::QUOTE => 'quote',
            self::PURCHASE_ORDER => 'purchase_order',
            self::INVOICE => 'invoice',
            self::CREDIT_NOTE => 'credit_note',
            self::RECEIPT => 'receipt',
        };
    }
}
