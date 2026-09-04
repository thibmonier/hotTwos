<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User;

use App\Application\User\PasswordPolicy;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\MetadataInterface;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * US-068 (T-068-11) — politique de mot de passe : longueur 12–128 + non-compromission (déléguée au
 * validateur Symfony). Messages génériques, sans révélation.
 */
final class PasswordPolicyTest extends TestCase
{
    public function testRejectsTooShort(): void
    {
        self::assertNotNull($this->policy()->violation('court'));
    }

    public function testRejectsTooLong(): void
    {
        self::assertNotNull($this->policy()->violation(str_repeat('a', 129)));
    }

    public function testRejectsCompromisedPassword(): void
    {
        $list = new ConstraintViolationList([
            new ConstraintViolation('compromised', null, [], '', null, 'motdepasse-fuite'),
        ]);

        $violation = $this->policy($list)->violation('motdepasse-longueur-ok');

        self::assertNotNull($violation);
        self::assertStringContainsString('fuite de données', $violation);
    }

    public function testAcceptsValidPassword(): void
    {
        self::assertNull($this->policy(new ConstraintViolationList())->violation('motdepasse-solide'));
    }

    private function policy(?ConstraintViolationList $result = null): PasswordPolicy
    {
        return new PasswordPolicy($this->validatorReturning($result ?? new ConstraintViolationList()));
    }

    private function validatorReturning(ConstraintViolationList $result): ValidatorInterface
    {
        return new readonly class ($result) implements ValidatorInterface {
            public function __construct(private ConstraintViolationList $result)
            {
            }

            public function validate(mixed $value, Constraint|array|null $constraints = null, string|GroupSequence|array|null $groups = null): ConstraintViolationListInterface
            {
                return $this->result;
            }

            public function validateProperty(object $object, string $propertyName, string|GroupSequence|array|null $groups = null): ConstraintViolationListInterface
            {
                throw new LogicException('Non utilisé par la politique de mot de passe.');
            }

            public function validatePropertyValue(object|string $objectOrClass, string $propertyName, mixed $value, string|GroupSequence|array|null $groups = null): ConstraintViolationListInterface
            {
                throw new LogicException('Non utilisé par la politique de mot de passe.');
            }

            public function startContext(): ContextualValidatorInterface
            {
                throw new LogicException('Non utilisé par la politique de mot de passe.');
            }

            public function inContext(ExecutionContextInterface $context): ContextualValidatorInterface
            {
                throw new LogicException('Non utilisé par la politique de mot de passe.');
            }

            public function getMetadataFor(mixed $value): MetadataInterface
            {
                throw new LogicException('Non utilisé par la politique de mot de passe.');
            }

            public function hasMetadataFor(mixed $value): bool
            {
                throw new LogicException('Non utilisé par la politique de mot de passe.');
            }
        };
    }
}
