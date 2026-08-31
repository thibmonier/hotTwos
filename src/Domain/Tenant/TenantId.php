<?php

declare(strict_types=1);

namespace App\Domain\Tenant;

use Symfony\Component\Uid\Uuid;
use InvalidArgumentException;

/**
 * Identifiant de tenant (INV-1) — value object immuable adossé à un UUID v7.
 * Domaine pur : dépend uniquement de symfony/uid, pas du framework applicatif.
 */
final readonly class TenantId
{
    private function __construct(
        private string $value,
    ) {
    }

    public static function generate(): self
    {
        return new self(Uuid::v7()->toRfc4122());
    }

    public static function fromString(string $value): self
    {
        if (!Uuid::isValid($value)) {
            throw new InvalidArgumentException(sprintf('Identifiant de tenant invalide : "%s".', $value));
        }

        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
