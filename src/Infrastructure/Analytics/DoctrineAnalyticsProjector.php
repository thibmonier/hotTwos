<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics;

use App\Domain\Analytics\AnalyticsProjector;
use App\Domain\Analytics\DimPeriod;
use App\Domain\Analytics\EventStore;
use App\Domain\Analytics\FactProjectRevenue;
use App\Domain\Analytics\RecognizedRevenue;
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
            // Paramètre **lié** via set_config (aucune interpolation) ; `is_local=true` = portée
            // transaction, équivalent à `SET LOCAL`.
            $connection->executeStatement("SELECT set_config('app.current_tenant', ?, true)", [$tenant->toString()]);

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
        // Sémantique de projection centralisée (US-060) : partagée avec le vérificateur de
        // non-divergence pour garantir l'égalité source/modèle par construction.
        $totals = RecognizedRevenue::byPeriodAndProject($this->eventStore->streamFor($tenant));

        foreach ($totals as $period => $byProject) {
            $this->entityManager->persist(new DimPeriod($tenant, $period));

            foreach ($byProject as $projectRef => $amount) {
                $this->entityManager->persist(new FactProjectRevenue($tenant, $period, $projectRef, $amount));
            }
        }
    }
}
