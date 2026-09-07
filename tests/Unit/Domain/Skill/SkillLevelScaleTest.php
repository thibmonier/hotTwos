<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Skill;

use App\Domain\Skill\Skill;
use App\Domain\Skill\SkillCategory;
use App\Domain\Skill\SkillLevelScale;
use App\Domain\Tenant\TenantId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * US-013 (EF-REF-10/11) — échelle de niveaux paramétrable et normalisation des libellés de compétence.
 */
final class SkillLevelScaleTest extends TestCase
{
    public function testDefaultScaleHasFourLevels(): void
    {
        $scale = new SkillLevelScale(TenantId::generate());

        self::assertSame(4, $scale->count());
        self::assertTrue($scale->isValidLevel(1));
        self::assertTrue($scale->isValidLevel(4));
        self::assertFalse($scale->isValidLevel(0));
        self::assertFalse($scale->isValidLevel(5));
    }

    public function testReconfigureChangesLevels(): void
    {
        $scale = new SkillLevelScale(TenantId::generate());
        $scale->reconfigure(['Notions', 'Débutant', 'Intermédiaire', 'Avancé', 'Expert']);

        self::assertSame(5, $scale->count());
        self::assertTrue($scale->isValidLevel(5));
    }

    public function testEmptyScaleRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SkillLevelScale(TenantId::generate(), []);
    }

    public function testLabelNormalizationIsCaseInsensitive(): void
    {
        self::assertSame('javascript', Skill::normalize('  JavaScript '));
        $skill = new Skill(TenantId::generate(), SkillCategory::TECHNIQUE, '  React.js ');
        self::assertSame('React.js', $skill->label());
        self::assertTrue($skill->isActive());
    }
}
