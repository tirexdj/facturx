<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case SENT = 'sent';
    case PAID = 'paid';
    case PARTIALLY_PAID = 'partially_paid';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    /**
     * Obtenir le libellé en français du statut
     */
    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Brouillon',
            self::PENDING => 'En attente',
            self::SENT => 'Envoyée',
            self::PAID => 'Payée',
            self::PARTIALLY_PAID => 'Partiellement payée',
            self::OVERDUE => 'En retard',
            self::CANCELLED => 'Annulée',
            self::REFUNDED => 'Remboursée',
        };
    }

    /**
     * Obtenir la couleur associée au statut (pour l'interface)
     */
    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::PENDING => 'blue',
            self::SENT => 'indigo',
            self::PAID => 'green',
            self::PARTIALLY_PAID => 'yellow',
            self::OVERDUE => 'red',
            self::CANCELLED => 'red',
            self::REFUNDED => 'purple',
        };
    }

    /**
     * Vérifier si la facture peut être modifiée
     */
    public function canEdit(): bool
    {
        return in_array($this, [
            self::DRAFT,
            self::PENDING
        ]);
    }

    /**
     * Vérifier si la facture peut être supprimée
     */
    public function canDelete(): bool
    {
        return in_array($this, [
            self::DRAFT,
            self::PENDING
        ]);
    }

    /**
     * Vérifier si la facture peut être envoyée
     */
    public function canSend(): bool
    {
        return in_array($this, [
            self::DRAFT,
            self::PENDING
        ]);
    }

    /**
     * Vérifier si la facture peut recevoir un paiement
     */
    public function canReceivePayment(): bool
    {
        return in_array($this, [
            self::SENT,
            self::OVERDUE,
            self::PARTIALLY_PAID
        ]);
    }

    /**
     * Obtenir tous les statuts sous forme de tableau
     */
    public static function toArray(): array
    {
        $statuses = [];
        foreach (self::cases() as $status) {
            $statuses[$status->value] = $status->label();
        }
        return $statuses;
    }

    /**
     * Obtenir les statuts qui indiquent une facture payée
     */
    public static function paidStatuses(): array
    {
        return [
            self::PAID->value,
            self::REFUNDED->value
        ];
    }

    /**
     * Obtenir les statuts qui indiquent une facture impayée
     */
    public static function unpaidStatuses(): array
    {
        return [
            self::DRAFT->value,
            self::PENDING->value,
            self::SENT->value,
            self::OVERDUE->value,
            self::PARTIALLY_PAID->value
        ];
    }
}
