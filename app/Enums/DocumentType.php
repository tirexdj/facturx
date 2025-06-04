<?php

namespace App\Enums;

enum DocumentType: string
{
    case DEVIS = 'devis';
    case BON_COMMANDE = 'bon_commande';
    case FACTURE = 'facture';
    case AVOIR = 'avoir';
    case ACOMPTE = 'acompte';

    public function label(): string
    {
        return match($this) {
            self::DEVIS => 'Devis',
            self::BON_COMMANDE => 'Bon de commande',
            self::FACTURE => 'Facture',
            self::AVOIR => 'Avoir',
            self::ACOMPTE => 'Acompte',
        };
    }

    public function prefix(): string
    {
        return match($this) {
            self::DEVIS => 'DEV',
            self::BON_COMMANDE => 'BC',
            self::FACTURE => 'FAC',
            self::AVOIR => 'AV',
            self::ACOMPTE => 'ACC',
        };
    }

    public function isPaidDocument(): bool
    {
        return in_array($this, [self::DEVIS, self::BON_COMMANDE]);
    }
}
