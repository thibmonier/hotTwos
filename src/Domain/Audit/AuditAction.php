<?php

declare(strict_types=1);

namespace App\Domain\Audit;

enum AuditAction: string
{
    case CREATION = 'creation';
    case MODIFICATION = 'modification';
    case SUPPRESSION = 'suppression';
    case DESACTIVATION = 'desactivation';

    public function label(): string
    {
        return match ($this) {
            self::CREATION => 'Création',
            self::MODIFICATION => 'Modification',
            self::SUPPRESSION => 'Suppression',
            self::DESACTIVATION => 'Désactivation',
        };
    }
}
