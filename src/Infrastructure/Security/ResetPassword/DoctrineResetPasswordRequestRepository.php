<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\ResetPassword;

use Doctrine\ORM\EntityManagerInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Persistence\ResetPasswordRequestRepositoryInterface;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * US-068 (T-068-05) — persistance des demandes de réinitialisation (DIP, style du projet : port du
 * bundle implémenté sur `EntityManagerInterface`, sans `ServiceEntityRepository`).
 */
final readonly class DoctrineResetPasswordRequestRepository implements ResetPasswordRequestRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function createResetPasswordRequest(object $user, DateTimeInterface $expiresAt, string $selector, string $hashedToken): ResetPasswordRequestInterface
    {
        \assert($user instanceof \App\Domain\User\User);

        return new ResetPasswordRequest($user, $expiresAt, $selector, $hashedToken);
    }

    public function getUserIdentifier(object $user): string
    {
        $identifier = $this->entityManager->getUnitOfWork()->getSingleIdentifierValue($user);

        return is_scalar($identifier) ? (string) $identifier : '';
    }

    public function persistResetPasswordRequest(ResetPasswordRequestInterface $resetPasswordRequest): void
    {
        $this->entityManager->persist($resetPasswordRequest);
        $this->entityManager->flush();
    }

    public function findResetPasswordRequest(string $selector): ?ResetPasswordRequestInterface
    {
        return $this->entityManager->getRepository(ResetPasswordRequest::class)
            ->findOneBy(['selector' => $selector]);
    }

    public function getMostRecentNonExpiredRequestDate(object $user): ?DateTimeInterface
    {
        $request = $this->entityManager->createQuery(
            'SELECT r FROM '.ResetPasswordRequest::class.' r WHERE r.user = :user ORDER BY r.requestedAt DESC',
        )
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if ($request instanceof ResetPasswordRequestInterface && !$request->isExpired()) {
            return $request->getRequestedAt();
        }

        return null;
    }

    public function removeResetPasswordRequest(ResetPasswordRequestInterface $resetPasswordRequest): void
    {
        // Invalide toutes les demandes du même utilisateur (single-use effectif).
        $this->entityManager->createQuery(
            'DELETE FROM '.ResetPasswordRequest::class.' r WHERE r.user = :user',
        )
            ->setParameter('user', $resetPasswordRequest->getUser())
            ->execute();
    }

    public function removeExpiredResetPasswordRequests(): int
    {
        $removed = $this->entityManager->createQuery(
            'DELETE FROM '.ResetPasswordRequest::class.' r WHERE r.expiresAt <= :threshold',
        )
            ->setParameter('threshold', new DateTimeImmutable('-1 week'))
            ->execute();

        return is_int($removed) ? $removed : 0;
    }
}
