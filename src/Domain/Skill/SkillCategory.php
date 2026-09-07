<?php

declare(strict_types=1);

namespace App\Domain\Skill;

enum SkillCategory: string
{
    case TECHNIQUE = 'technique';
    case FONCTIONNEL = 'fonctionnel';
    case METHODOLOGIQUE = 'methodologique';
    case LINGUISTIQUE = 'linguistique';
    case SECTORIEL = 'sectoriel';

    public function label(): string
    {
        return match ($this) {
            self::TECHNIQUE => 'Technique',
            self::FONCTIONNEL => 'Fonctionnel',
            self::METHODOLOGIQUE => 'Méthodologique',
            self::LINGUISTIQUE => 'Linguistique',
            self::SECTORIEL => 'Sectoriel',
        };
    }
}
