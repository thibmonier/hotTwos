<?php

declare(strict_types=1);

namespace App\Domain\Project;

/**
 * Cycle de vie d'un projet (US-030, EF-PRJ-4, RG-PRJ-1). Le statut conditionne les actions permises :
 * l'imputation de temps n'est ouverte qu'« En cours » (CA-2), la facturation à partir de la livraison
 * (CA-3). Les transitions autorisées sont un défaut raisonnable (paramétrage ADMIN ultérieur, EF-PRJ-4).
 */
enum ProjectStatus: string
{
    case EN_PREPARATION = 'en_preparation';
    case EN_COURS = 'en_cours';
    case EN_ATTENTE_CLIENT = 'en_attente_client';
    case LIVRE_ATTENTE_RECEPTION = 'livre_attente_reception';
    case RECEPTIONNE = 'receptionne';
    case CLOTURE = 'cloture';
    case ANNULE = 'annule';

    /** L'imputation de temps de production n'est autorisée que projet « En cours » (CA-2). */
    public function allowsImputation(): bool
    {
        return self::EN_COURS === $this;
    }

    /** La facturation est autorisée dès l'exécution et jusqu'à réception (CA-3). */
    public function allowsBilling(): bool
    {
        return match ($this) {
            self::EN_COURS, self::LIVRE_ATTENTE_RECEPTION, self::RECEPTIONNE => true,
            default => false,
        };
    }

    public function isTerminal(): bool
    {
        return self::CLOTURE === $this || self::ANNULE === $this;
    }

    /**
     * Le statut cible est-il atteignable depuis celui-ci ? Transitions par défaut (EF-PRJ-4).
     */
    public function canTransitionTo(self $target): bool
    {
        if ($this === $target || $this->isTerminal()) {
            return false;
        }

        return match ($this) {
            self::EN_PREPARATION => self::EN_COURS === $target || self::ANNULE === $target,
            self::EN_COURS => in_array($target, [self::EN_ATTENTE_CLIENT, self::LIVRE_ATTENTE_RECEPTION, self::ANNULE], true),
            self::EN_ATTENTE_CLIENT => in_array($target, [self::EN_COURS, self::LIVRE_ATTENTE_RECEPTION, self::ANNULE], true),
            self::LIVRE_ATTENTE_RECEPTION => in_array($target, [self::RECEPTIONNE, self::EN_COURS], true),
            self::RECEPTIONNE => self::CLOTURE === $target,
            default => false,
        };
    }

    /**
     * Transitions autorisées depuis ce statut — pour n'afficher que les actions valides (US-030,
     * anti-pattern du `select` exhaustif). La clôture (« Clôturé ») passe par le flux dédié d'US-038.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return array_values(array_filter(self::cases(), $this->canTransitionTo(...)));
    }

    /** Famille visuelle du badge (US-030) — la couleur renforce le libellé, jamais seule porteuse. */
    public function badgeFamily(): string
    {
        return match ($this) {
            self::EN_PREPARATION => 'neutral',
            self::EN_COURS, self::EN_ATTENTE_CLIENT, self::LIVRE_ATTENTE_RECEPTION, self::RECEPTIONNE => 'active',
            self::CLOTURE => 'closed',
            self::ANNULE => 'cancelled',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::EN_PREPARATION => 'En préparation',
            self::EN_COURS => 'En cours',
            self::EN_ATTENTE_CLIENT => 'En attente client',
            self::LIVRE_ATTENTE_RECEPTION => 'Livré – en attente de réception',
            self::RECEPTIONNE => 'Réceptionné',
            self::CLOTURE => 'Clôturé',
            self::ANNULE => 'Annulé',
        };
    }
}
