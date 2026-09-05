<?php

declare(strict_types=1);

namespace App\Application\Fec;

/**
 * Résultat d'un export FEC (US-074) : nom de fichier normé + contenu prêt au téléchargement.
 */
final readonly class FecExport
{
    public function __construct(
        public string $fileName,
        public string $content,
    ) {
    }
}
