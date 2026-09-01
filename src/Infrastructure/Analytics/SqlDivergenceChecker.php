<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics;

use App\Domain\Analytics\Divergence;
use App\Domain\Analytics\DivergenceChecker;
use App\Domain\Analytics\EventStore;
use App\Domain\Analytics\FactProjectRevenue;
use App\Domain\Analytics\RecognizedRevenue;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Vérificateur de non-divergence (US-005, ARC-119).
 *
 * Recalcule l'indicateur « chiffre d'affaires par période » **directement depuis la
 * source** (le flux d'événements, sommé en PHP) — chemin de code indépendant de celui
 * du projecteur — et le compare aux faits matérialisés. Un bug de projection (coefficient
 * appliqué deux fois, événement non rejoué…) produit alors un écart détecté.
 */
final readonly class SqlDivergenceChecker implements DivergenceChecker
{
    private const string INDICATOR = 'project_revenue';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventStore $eventStore,
    ) {
    }

    public function check(TenantId $tenant): array
    {
        $expected = $this->expectedFromSource($tenant);
        $actual = $this->actualFromModel($tenant);

        $divergences = [];
        foreach ($this->periods($expected, $actual) as $period) {
            if ('' === $period) {
                continue;
            }

            $expectedCents = $expected[$period] ?? 0;
            $actualCents = $actual[$period] ?? 0;

            if ($expectedCents !== $actualCents) {
                $divergences[] = new Divergence(self::INDICATOR, $period, $expectedCents, $actualCents);
            }
        }

        return $divergences;
    }

    /**
     * @return array<string, int> période => CA attendu (recalcul source)
     */
    private function expectedFromSource(TenantId $tenant): array
    {
        // Même réduction que le projecteur (US-060) : chemin de code distinct (recalcul PHP
        // vs matérialisation SQL) mais règle métier unique — un écart ne peut venir que d'un
        // bug de matérialisation, jamais d'une divergence de définition de l'indicateur.
        $expected = [];
        foreach (RecognizedRevenue::byPeriodAndProject($this->eventStore->streamFor($tenant)) as $period => $byProject) {
            $expected[$period] = array_sum($byProject);
        }

        return $expected;
    }

    /**
     * @return array<string, int> période => CA matérialisé dans les faits
     */
    private function actualFromModel(TenantId $tenant): array
    {
        /** @var list<array{period: string, total: int|string|null}> $rows */
        $rows = $this->entityManager->createQuery(
            'SELECT f.period AS period, SUM(f.amountCents) AS total FROM '.FactProjectRevenue::class.' f'
            .' WHERE f.tenantId = :tenant GROUP BY f.period',
        )->setParameter('tenant', $tenant->toString())->getResult();

        $actual = [];
        foreach ($rows as $row) {
            $actual[$row['period']] = (int) $row['total'];
        }

        return $actual;
    }

    /**
     * @param array<string, int> $expected
     * @param array<string, int> $actual
     *
     * @return list<string>
     */
    private function periods(array $expected, array $actual): array
    {
        return array_values(array_unique([...array_keys($expected), ...array_keys($actual)]));
    }
}
