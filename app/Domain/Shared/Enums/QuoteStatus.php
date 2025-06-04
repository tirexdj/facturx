<?php

namespace App\Domain\Shared\Enums;

enum QuoteStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Brouillon',
            self::SENT => 'Envoyé',
            self::PENDING => 'En attente',
            self::ACCEPTED => 'Accepté',
            self::REJECTED => 'Refusé',
            self::EXPIRED => 'Expiré',
            self::CANCELLED => 'Annulé',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::SENT => 'blue',
            self::PENDING => 'yellow',
            self::ACCEPTED => 'green',
            self::REJECTED => 'red',
            self::EXPIRED => 'orange',
            self::CANCELLED => 'red',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::DRAFT => 'document',
            self::SENT => 'paper-airplane',
            self::PENDING => 'clock',
            self::ACCEPTED => 'check-circle',
            self::REJECTED => 'x-circle',
            self::EXPIRED => 'exclamation-triangle',
            self::CANCELLED => 'ban',
        };
    }

    /**
     * Statuts depuis lesquels on peut passer au statut courant
     */
    public function allowedPreviousStatuses(): array
    {
        return match($this) {
            self::DRAFT => [],
            self::SENT => [self::DRAFT],
            self::PENDING => [self::SENT],
            self::ACCEPTED => [self::SENT, self::PENDING],
            self::REJECTED => [self::SENT, self::PENDING],
            self::EXPIRED => [self::SENT, self::PENDING],
            self::CANCELLED => [self::DRAFT, self::SENT, self::PENDING],
        };
    }

    /**
     * Statuts vers lesquels on peut passer depuis le statut courant
     */
    public function allowedNextStatuses(): array
    {
        return match($this) {
            self::DRAFT => [self::SENT, self::CANCELLED],
            self::SENT => [self::PENDING, self::ACCEPTED, self::REJECTED, self::EXPIRED, self::CANCELLED],
            self::PENDING => [self::ACCEPTED, self::REJECTED, self::EXPIRED, self::CANCELLED],
            self::ACCEPTED => [],
            self::REJECTED => [],
            self::EXPIRED => [],
            self::CANCELLED => [],
        };
    }

    public function canTransitionTo(QuoteStatus $newStatus): bool
    {
        return in_array($newStatus, $this->allowedNextStatuses());
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::ACCEPTED, self::REJECTED, self::EXPIRED, self::CANCELLED]);
    }

    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }

    public function canBeConverted(): bool
    {
        return $this === self::ACCEPTED;
    }

    public function canBeSent(): bool
    {
        return in_array($this, [self::DRAFT, self::SENT]);
    }
}
