<?php

declare(strict_types=1);

namespace App\Application\Timesheet;

/**
 * Échec d'enregistrement d'une cellule dans un lot hebdomadaire (US-051), avec son motif
 * métier — permet un retour cellule par cellule sans interrompre le reste du lot.
 */
final readonly class CellError
{
    public function __construct(
        public string $projectId,
        public string $date,
        public string $message,
    ) {
    }
}
