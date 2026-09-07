<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Tenant\TenantId;
use App\Domain\Validation\AbsenceValidationCircuit;
use App\Domain\Validation\AbsenceValidationCircuitRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineAbsenceValidationCircuitRepository implements AbsenceValidationCircuitRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findForTenant(TenantId $tenant): ?AbsenceValidationCircuit
    {
        $circuit = $this->entityManager->createQuery(
            'SELECT c FROM '.AbsenceValidationCircuit::class.' c WHERE c.tenantId = :tenant',
        )
            ->setParameter('tenant', $tenant->toString())
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $circuit instanceof AbsenceValidationCircuit ? $circuit : null;
    }

    public function save(AbsenceValidationCircuit $circuit): void
    {
        $this->entityManager->persist($circuit);
        $this->entityManager->flush();
    }
}
