<?php

declare(strict_types=1);

namespace App\UI\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Organization\AttachCollaborator;
use App\Domain\Organization\OrganizationException;
use App\Domain\Shared\EffectivePeriod;
use App\UI\Api\Resource\OrgMembershipResource;
use DateTimeImmutable;

/**
 * Rattache un collaborateur via le cas d'usage (US-010). Les dates d'effet (`Y-m-d`) sont
 * converties en {@see EffectivePeriod} ; l'habilitation et les règles métier (chevauchement,
 * unité active) sont portées par AttachCollaborator (403/422 via listeners).
 *
 * @implements ProcessorInterface<OrgMembershipResource, OrgMembershipResource>
 */
final readonly class AttachCollaboratorProcessor implements ProcessorInterface
{
    public function __construct(
        private AttachCollaborator $attach,
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): OrgMembershipResource
    {
        $user = $this->currentUser->require();
        $period = $this->buildPeriod($data->effectiveFrom, $data->effectiveTo);

        $id = $this->attach->attach($user->tenantId(), $user, $data->userId, $data->orgUnitId, $period);

        return new OrgMembershipResource(
            id: $id,
            userId: $data->userId,
            orgUnitId: $data->orgUnitId,
            effectiveFrom: $period->from()->format('Y-m-d'),
            effectiveTo: $period->to()?->format('Y-m-d'),
        );
    }

    private function buildPeriod(?string $from, ?string $to): EffectivePeriod
    {
        if (null === $from || '' === $from) {
            throw new OrganizationException('La date d\'effet (effectiveFrom) est obligatoire.');
        }

        $start = $this->parseDate($from);

        return null === $to || '' === $to
            ? EffectivePeriod::since($start)
            : EffectivePeriod::between($start, $this->parseDate($to));
    }

    private function parseDate(string $value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (false === $date) {
            throw new OrganizationException(sprintf('Date invalide (attendu Y-m-d) : %s.', $value));
        }

        return $date;
    }
}
