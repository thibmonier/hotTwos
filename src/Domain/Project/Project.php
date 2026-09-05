<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use InvalidArgumentException;

/**
 * Projet porté par tenant (INV-1). Depuis US-030, agrégat métier : client, responsable, budget,
 * contractualisation, dates et **cycle de vie** ({@see ProjectStatus}) qui conditionne les actions
 * permises (imputation « En cours » — CA-2 ; facturation — CA-3).
 *
 * Rétro-compatibilité : le constructeur conserve l'usage minimal (US-050 — code/nom) avec un statut
 * par défaut « En cours » ; les projets système (ligne « Absence ») et les jeux de test restent
 * imputables. Les projets métier passent par {@see createBusiness()} et démarrent « En préparation ».
 */
#[ORM\Entity]
#[ORM\Table(name: 'project')]
#[ORM\UniqueConstraint(name: 'uniq_project_tenant_code', columns: ['tenant_id', 'code'])]
#[ORM\Index(name: 'idx_project_tenant', columns: ['tenant_id'])]
class Project implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    #[ORM\Column(length: 50)]
    private string $code;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(name: 'closed_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $closedAt = null;

    #[ORM\Column(name: 'closed_by', type: 'guid', nullable: true)]
    private ?string $closedBy = null;

    public function __construct(
        TenantId $tenantId,
        string $code,
        string $name,
        #[ORM\Column(type: 'boolean')]
        private bool $active = true,
        /** Chef de projet responsable (US-055) : le seul habilité à valider les temps du projet. */
        #[ORM\Column(name: 'responsible_user_id', type: 'guid', nullable: true)]
        private ?string $responsibleUserId = null,
        #[ORM\Column(name: 'status', length: 30, enumType: ProjectStatus::class)]
        private ProjectStatus $status = ProjectStatus::EN_COURS,
        #[ORM\Column(name: 'client_name', length: 255, nullable: true)]
        private ?string $clientName = null,
        #[ORM\Column(name: 'budget_cents', type: 'integer', nullable: true)]
        private ?int $budgetCents = null,
        #[ORM\Column(name: 'contract_type', length: 20, nullable: true, enumType: ContractType::class)]
        private ?ContractType $contractType = null,
        #[ORM\Column(name: 'start_date', type: 'date_immutable', nullable: true)]
        private ?DateTimeImmutable $startDate = null,
        #[ORM\Column(name: 'end_date', type: 'date_immutable', nullable: true)]
        private ?DateTimeImmutable $endDate = null,
        /** CA cible prévisionnel (US-072, EF-FIN) — enveloppe de revenu, en centimes. Optionnel. */
        #[ORM\Column(name: 'revenue_budget_cents', type: 'integer', nullable: true)]
        private ?int $revenueBudgetCents = null,
    ) {
        if ('' === $code) {
            throw new InvalidArgumentException('Le code projet ne peut pas être vide.');
        }
        if ('' === $name) {
            throw new InvalidArgumentException('Le nom du projet ne peut pas être vide.');
        }

        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
        $this->code = $code;
        $this->name = $name;
    }

    /**
     * Crée un projet métier (US-030, CA-1) : client, responsable et budget obligatoires (RG-PRJ-1),
     * statut initial « En préparation ».
     */
    public static function createBusiness(
        TenantId $tenantId,
        string $code,
        string $name,
        string $clientName,
        string $responsibleUserId,
        int $budgetCents,
        ContractType $contractType,
        ?DateTimeImmutable $startDate,
        ?DateTimeImmutable $endDate,
        ?int $revenueBudgetCents = null,
    ): self {
        if ('' === trim($clientName)) {
            throw new ProjectException('Le client est obligatoire (RG-PRJ-1).');
        }
        if ('' === trim($responsibleUserId)) {
            throw new ProjectException('Le responsable est obligatoire (RG-PRJ-1).');
        }
        if ($budgetCents <= 0) {
            throw new ProjectException('Le budget est obligatoire (RG-PRJ-1).');
        }
        if (null !== $revenueBudgetCents && $revenueBudgetCents <= 0) {
            throw new ProjectException('Le CA cible, s\'il est défini, doit être strictement positif.');
        }
        if ($startDate instanceof DateTimeImmutable && $endDate instanceof DateTimeImmutable && $startDate > $endDate) {
            throw new ProjectException('La date de début doit précéder la date de fin.');
        }

        return new self($tenantId, $code, $name, true, $responsibleUserId, ProjectStatus::EN_PREPARATION, trim($clientName), $budgetCents, $contractType, $startDate, $endDate, $revenueBudgetCents);
    }

    /** Fait évoluer le statut selon les transitions autorisées (US-030, EF-PRJ-4). */
    public function changeStatus(ProjectStatus $target): void
    {
        if (!$this->status->canTransitionTo($target)) {
            throw new ProjectException(sprintf('Transition de statut non autorisée : « %s » → « %s ».', $this->status->label(), $target->label()));
        }
        $this->status = $target;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function code(): string
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function responsibleUserId(): ?string
    {
        return $this->responsibleUserId;
    }

    public function isResponsible(string $userId): bool
    {
        return null !== $this->responsibleUserId && $this->responsibleUserId === $userId;
    }

    /**
     * Clôture opérationnelle (US-038) : ferme les imputations. Les prérequis (imputations non
     * validées bloquantes, jalons/engagements en cours) sont vérifiés par le cas d'usage.
     */
    public function close(string $closedBy, DateTimeImmutable $at): void
    {
        if (ProjectStatus::CLOTURE === $this->status) {
            throw new ProjectException('Le projet est déjà clôturé.');
        }
        $this->status = ProjectStatus::CLOTURE;
        $this->closedAt = $at;
        $this->closedBy = $closedBy;
    }

    public function isClosed(): bool
    {
        return ProjectStatus::CLOTURE === $this->status;
    }

    /**
     * Garantit qu'une modification structurelle (lot, jalon, affectation, ouverture) est permise :
     * un projet clôturé est en lecture seule (US-038/CA-2, RG-PRJ-5). La réouverture (4-eyes) ne
     * rouvre que l'imputation de temps, pas l'édition structurelle.
     */
    public function assertModifiable(): void
    {
        if (!$this->isClosed()) {
            return;
        }
        $at = $this->closedAt instanceof DateTimeImmutable ? sprintf(' le %s', $this->closedAt->format('d/m/Y')) : '';
        throw new ProjectException(sprintf('Projet clôturé%s — modification impossible (lecture seule, RG-PRJ-5).', $at));
    }

    public function closedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function status(): ProjectStatus
    {
        return $this->status;
    }

    public function allowsImputation(): bool
    {
        return $this->status->allowsImputation();
    }

    public function clientName(): ?string
    {
        return $this->clientName;
    }

    public function budgetCents(): ?int
    {
        return $this->budgetCents;
    }

    public function revenueBudgetCents(): ?int
    {
        return $this->revenueBudgetCents;
    }

    /** Définit (ou efface) le budget de charge cible (US-072 / seed). `null` efface ; sinon > 0. */
    public function defineBudget(?int $budgetCents): void
    {
        if (null !== $budgetCents && $budgetCents <= 0) {
            throw new ProjectException('Le budget de charge, s\'il est défini, doit être strictement positif.');
        }
        $this->budgetCents = $budgetCents;
    }

    /** Rattache le projet à un client (dimension finance US-073 / seed). */
    public function defineClient(?string $clientName): void
    {
        $this->clientName = null !== $clientName && '' !== trim($clientName) ? trim($clientName) : null;
    }

    /**
     * Définit (ou efface) le CA cible prévisionnel (US-072). Planning financier : autorisé quel que
     * soit le statut. `null` efface la cible ; une valeur doit être strictement positive.
     */
    public function defineRevenueBudget(?int $revenueBudgetCents): void
    {
        if (null !== $revenueBudgetCents && $revenueBudgetCents <= 0) {
            throw new ProjectException('Le CA cible, s\'il est défini, doit être strictement positif.');
        }
        $this->revenueBudgetCents = $revenueBudgetCents;
    }

    public function contractType(): ?ContractType
    {
        return $this->contractType;
    }

    public function startDate(): ?DateTimeImmutable
    {
        return $this->startDate;
    }

    public function endDate(): ?DateTimeImmutable
    {
        return $this->endDate;
    }
}
