<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * US-021 (EF-REF-7) — régime de travail d'un collaborateur : sous-ensemble des jours ouvrés de la
 * semaine (ISO 1=lundi … 5=vendredi) pour le temps partiel. Sans régime, le collaborateur est à temps
 * plein (Lun-Ven). Consommé par {@see WorkingDaysCalculator} (variantes par utilisateur).
 */
#[ORM\Entity]
#[ORM\Table(name: 'work_schedule')]
#[ORM\UniqueConstraint(name: 'uniq_work_schedule_tenant_user', columns: ['tenant_id', 'user_id'])]
class WorkSchedule implements TenantOwned
{
    private const int LAST_WEEKDAY = 5;

    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    /**
     * @param list<int> $workingWeekdays jours ISO travaillés (1..5)
     */
    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'user_id', type: 'guid')]
        private string $userId,
        #[ORM\Column(name: 'working_weekdays', type: 'json')]
        private array $workingWeekdays,
    ) {
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
        $this->workingWeekdays = $this->clean($workingWeekdays);
    }

    /**
     * @param list<int> $workingWeekdays
     */
    public function reconfigure(array $workingWeekdays): void
    {
        $this->workingWeekdays = $this->clean($workingWeekdays);
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function userId(): string
    {
        return $this->userId;
    }

    /**
     * @return list<int>
     */
    public function workingWeekdays(): array
    {
        return $this->workingWeekdays;
    }

    public function worksOn(int $isoWeekday): bool
    {
        return in_array($isoWeekday, $this->workingWeekdays, true);
    }

    /**
     * @param list<int> $workingWeekdays
     *
     * @return list<int>
     */
    private function clean(array $workingWeekdays): array
    {
        $clean = array_values(array_unique(array_filter(
            $workingWeekdays,
            static fn (int $d): bool => $d >= 1 && $d <= self::LAST_WEEKDAY,
        )));
        sort($clean);
        if ([] === $clean) {
            throw new InvalidArgumentException('Le régime de travail doit comporter au moins un jour ouvré (lundi à vendredi).');
        }

        return $clean;
    }
}
