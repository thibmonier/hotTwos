<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Organization\OrgUnit;
use App\Domain\Organization\OrgUnitRepository;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-010 (T-010-06) — écran d'administration de la hiérarchie (adaptateur web).
 *
 * Réservé à l'admin (habilitation vérifiée en applicatif, ARC-106). Les actions (création,
 * désactivation, rattachement) passent par l'API via un contrôleur Stimulus. La page présente
 * l'arbre des unités (ordre hiérarchique, profondeur) et les libellés de niveaux.
 */
final class OrganizationPageController extends AbstractController
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly OrgUnitRepository $units,
    ) {
    }

    #[Route('/organisation', name: 'organization_admin', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): Response
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);

        return $this->render('organization/index.html.twig', [
            'rows' => $this->orderedRows($this->units->findByTenant($user->tenantId())),
        ]);
    }

    /**
     * Ordonne les unités en parcours préfixe (racines puis descendants), avec leur profondeur,
     * pour un affichage arborescent indenté.
     *
     * @param list<OrgUnit> $units
     *
     * @return list<array{id: string, name: string, active: bool, depth: int, indent: string}>
     */
    private function orderedRows(array $units): array
    {
        $childrenByParent = [];
        foreach ($units as $unit) {
            $childrenByParent[$unit->parentId() ?? ''][] = $unit;
        }

        $rows = [];
        $this->appendBranch($childrenByParent, '', 0, $rows);

        return $rows;
    }

    /**
     * @param array<string, list<OrgUnit>>                                                    $childrenByParent
     * @param list<array{id: string, name: string, active: bool, depth: int, indent: string}> $rows
     */
    private function appendBranch(array $childrenByParent, string $parentKey, int $depth, array &$rows): void
    {
        foreach ($childrenByParent[$parentKey] ?? [] as $unit) {
            $rows[] = [
                'id' => $unit->id(),
                'name' => $unit->name(),
                'active' => $unit->isActive(),
                'depth' => $depth,
                'indent' => str_repeat('— ', $depth),
            ];
            $this->appendBranch($childrenByParent, $unit->id(), $depth + 1, $rows);
        }
    }
}
