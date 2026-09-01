<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Période de validité à date d'effet — value object immuable, partagé par les référentiels
 * historisés (US-010 rattachements, US-011 tarifs).
 *
 * Intervalle semi-ouvert [from, to) : borne basse **incluse**, borne haute **exclue**. Ce choix
 * rend deux périodes consécutives (…, avr) et [avr, …) non chevauchantes et sans trou, et
 * s'aligne sur la résolution du tarif en vigueur à une date (`from <= date < to`, RateResolver
 * US-011). `to = null` signifie « en cours » (aucune fin).
 *
 * Domaine pur : ne dépend d'aucun framework.
 */
final readonly class EffectivePeriod
{
    private function __construct(
        private DateTimeImmutable $from,
        private ?DateTimeImmutable $to,
    ) {
    }

    /**
     * Période ouverte démarrant à {@see $from} et sans fin (« en cours »).
     */
    public static function since(DateTimeImmutable $from): self
    {
        return new self($from, null);
    }

    /**
     * Période bornée [from, to). La borne haute est exclue et doit être strictement postérieure
     * à la borne basse (une période vide ou inversée n'a pas de sens métier).
     */
    public static function between(DateTimeImmutable $from, ?DateTimeImmutable $to): self
    {
        if ($to instanceof DateTimeImmutable && $to <= $from) {
            throw new InvalidArgumentException(sprintf('La fin de période (%s) doit être strictement postérieure au début (%s).', $to->format('Y-m-d'), $from->format('Y-m-d')));
        }

        return new self($from, $to);
    }

    public function from(): DateTimeImmutable
    {
        return $this->from;
    }

    public function to(): ?DateTimeImmutable
    {
        return $this->to;
    }

    public function isOpenEnded(): bool
    {
        return !$this->to instanceof DateTimeImmutable;
    }

    /**
     * Vrai si {@see $date} appartient à la période : from <= date < to (ou to = « en cours »).
     */
    public function contains(DateTimeImmutable $date): bool
    {
        return $date >= $this->from && (!$this->to instanceof DateTimeImmutable || $date < $this->to);
    }

    /**
     * Vrai si les deux périodes se recouvrent sur au moins un instant. Deux périodes adjacentes
     * (la fin de l'une = le début de l'autre) ne se chevauchent pas (borne haute exclue).
     */
    public function overlaps(self $other): bool
    {
        return $this->startsBefore($other->to) && $other->startsBefore($this->to);
    }

    public function equals(self $other): bool
    {
        return $this->from == $other->from && $this->to == $other->to;
    }

    /**
     * Vrai si la période commence strictement avant {@see $bound} (une borne « en cours » = +∞).
     */
    private function startsBefore(?DateTimeImmutable $bound): bool
    {
        return !$bound instanceof DateTimeImmutable || $this->from < $bound;
    }
}
