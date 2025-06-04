<?php

namespace App\Enums;

enum DevisStatus: string
{
    case BROUILLON = 'brouillon';
    case ENVOYE = 'envoye';
    case EN_ATTENTE = 'en_attente';
    case ACCEPTE = 'accepte';
    case REFUSE = 'refuse';
    case EXPIRE = 'expire';
    case ANNULE = 'annule';

    public function label(): string
    {
        return match($this) {
            self::BROUILLON => 'Brouillon',
            self::ENVOYE => 'Envoyé',
            self::EN_ATTENTE => 'En attente',
            self::ACCEPTE => 'Accepté',
            self::REFUSE => 'Refusé',
            self::EXPIRE => 'Expiré',
            self::ANNULE => 'Annulé',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::BROUILLON => 'gray',
            self::ENVOYE => 'blue',
            self::EN_ATTENTE => 'yellow',
            self::ACCEPTE => 'green',
            self::REFUSE => 'red',
            self::EXPIRE => 'orange',
            self::ANNULE => 'red',
        };
    }

    /**
     * Statuts depuis lesquels on peut passer au statut courant
     */
    public function allowedPreviousStatuses(): array
    {
        return match($this) {
            self::BROUILLON => [],
            self::ENVOYE => [self::BROUILLON],
            self::EN_ATTENTE => [self::ENVOYE],
            self::ACCEPTE => [self::ENVOYE, self::EN_ATTENTE],
            self::REFUSE => [self::ENVOYE, self::EN_ATTENTE],
            self::EXPIRE => [self::ENVOYE, self::EN_ATTENTE],
            self::ANNULE => [self::BROUILLON, self::ENVOYE, self::EN_ATTENTE],
        };
    }

    /**
     * Statuts vers lesquels on peut passer depuis le statut courant
     */
    public function allowedNextStatuses(): array
    {
        return match($this) {
            self::BROUILLON => [self::ENVOYE, self::ANNULE],
            self::ENVOYE => [self::EN_ATTENTE, self::ACCEPTE, self::REFUSE, self::EXPIRE, self::ANNULE],
            self::EN_ATTENTE => [self::ACCEPTE, self::REFUSE, self::EXPIRE, self::ANNULE],
            self::ACCEPTE => [],
            self::REFUSE => [],
            self::EXPIRE => [],
            self::ANNULE => [],
        };
    }

    public function canTransitionTo(DevisStatus $newStatus): bool
    {
        return in_array($newStatus, $this->allowedNextStatuses());
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::ACCEPTE, self::REFUSE, self::EXPIRE, self::ANNULE]);
    }
}
