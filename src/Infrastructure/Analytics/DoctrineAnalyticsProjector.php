<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics;

use App\Domain\Analytics\AnalyticsProjector;
use App\Domain\Analytics\DimPeriod;
use App\Domain\Analytics\EventStore;
use App\Domain\Analytics\FactProjectRevenue;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

/**
 * Projecteur analytique Doctrine (ADR-9, ARC-111, ARC-114).
 *
 * Reconstruit le modèle en étoile d'un tenant par « clear + replay » : purge des faits
 * et dimensions du tenant, puis rejeu déterministe de son flux d'événements. Idempotent
 * (rejouer les mêmes événements produit exactement les mêmes agrégats — CA-1) et borné
 * au tenant (les autres ne sont pas touchés — ARC-114).
 *
 * L'écriture s'effectue dans une transaction marquée `app.projector_active` : c'est le
 * seul contexte où la protection anti-écriture directe des tables de faits (CA-6) laisse
 * passer les INSERT.
 */
final readonly class DoctrineAnalyticsProjector implements AnalyticsProjector
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventStore $eventStore,
    ) {
    }

    public function rebuild(TenantId $tenant): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            // Contexte d'écriture : `projector_active` lève la protection anti-écriture
            // directe (CA-6) ; `current_tenant` satisfait la barrière RLS (WITH CHECK) en
            // production où le rôle applicatif n'est pas superutilisateur (CA-4).
            $connection->executeStatement("SET LOCAL app.projector_active = 'on'");
            $connection->executeStatement(sprintf("SET LOCAL app.current_tenant = '%s'", $tenant->toString()));

            $this->purge($tenant);
            $this->replay($tenant);

            $this->entityManager->flush();
            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }
    }

    private function purge(TenantId $tenant): void
    {
        foreach ([FactProjectRevenue::class, DimPeriod::class] as $entity) {
            $this->entityManager->createQuery(
                'DELETE FROM '.$entity.' t WHERE t.tenantId = :tenant',
            )->setParameter('tenant', $tenant->toString())->execute();
        }
    }

    private function replay(TenantId $tenant): void
    {
        /** @var array<string, array<string, int>> $totals period => (projectRef => amountCents) */
        $totals = [];

        foreach ($this->eventStore->streamFor($tenant) as $event) {
            if ('revenue_recognized' !== $event->name()) {
                continue;
            }

            $payload = $event->payload();
            $period = (string) $payload['period'];
            $projectRef = (string) $payload['project_ref'];
            $amount = (int) $payload['amount_cents'];

            $totals[$period][$projectRef] = ($totals[$period][$projectRef] ?? 0) + $amount;
        }

        foreach ($totals as $period => $byProject) {
            $this->entityManager->persist(new DimPeriod($tenant, $period));

            foreach ($byProject as $projectRef => $amount) {
                $this->entityManager->persist(new FactProjectRevenue($tenant, $period, $projectRef, $amount));
            }
        }
    }
}
