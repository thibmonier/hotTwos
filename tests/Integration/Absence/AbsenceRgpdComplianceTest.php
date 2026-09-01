<?php

declare(strict_types=1);

namespace App\Tests\Integration\Absence;

use App\Domain\Absence\AbsenceRequest;
use App\Domain\Absence\AbsenceType;
use App\UI\Api\Resource\AbsenceBalanceResource;
use App\UI\Api\Resource\AbsenceResource;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * US-054 (T-054-07, HAB-3 / RGPD art. 9) — **gate de conformité** : aucune donnée de santé
 * (motif médical, diagnostic…) ne doit exister dans le mapping des entités d'absence ni dans le
 * DTO d'API. Ce test échoue et bloque la livraison si un tel champ apparaît.
 */
final class AbsenceRgpdComplianceTest extends KernelTestCase
{
    /** Fragments interdits dans tout nom de colonne / propriété lié aux absences (HAB-3). */
    private const array FORBIDDEN = ['medical', 'diagnos', 'sante', 'health', 'patholog', 'maladie_motif', 'symptom'];

    public function testNoHealthDataColumnOnAbsenceEntities(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        foreach ([AbsenceRequest::class, AbsenceType::class] as $entity) {
            $meta = $em->getClassMetadata($entity);
            foreach ($meta->getFieldNames() as $field) {
                $column = strtolower($meta->getColumnName($field));
                self::assertFalse($this->isForbidden($column), sprintf('Champ de santé interdit (HAB-3) : %s.%s', $entity, $column));
            }
        }
    }

    public function testNoHealthDataFieldOnApiResource(): void
    {
        foreach ([AbsenceResource::class, AbsenceBalanceResource::class] as $resource) {
            foreach (new ReflectionClass($resource)->getProperties() as $property) {
                self::assertFalse($this->isForbidden(strtolower($property->getName())), sprintf('Propriété d\'API de santé interdite (HAB-3) : %s::%s', $resource, $property->getName()));
            }
        }
    }

    private function isForbidden(string $name): bool
    {
        return array_any(self::FORBIDDEN, fn (string $fragment): bool => str_contains($name, $fragment));
    }
}
