<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Domain\Audit\ConfigAuditEntry;
use App\Domain\Audit\ConfigAuditRecorder;
use App\Domain\Authorization\Permission;
use App\Domain\User\User;
use App\Domain\User\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-020 (EF-REF-33, HAB-6) — consultation du journal d'audit du paramétrage. Réservé à
 * `VIEW_AUDIT_LOG` (administrateur / dirigeant).
 */
final class AuditLogController extends AbstractController
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly ConfigAuditRecorder $audit,
        private readonly UserRepository $users,
    ) {
    }

    #[Route('/parametrage/audit', name: 'audit_log_index', methods: ['GET'])]
    public function index(#[CurrentUser] User $user, Request $request): Response
    {
        $this->authorizer->ensureCan($user, Permission::VIEW_AUDIT_LOG);
        $tenant = $user->tenantId();

        $objectType = trim((string) $request->query->get('objectType', ''));
        $actor = trim((string) $request->query->get('actor', ''));

        $entries = $this->audit->findForTenant($tenant, '' !== $objectType ? $objectType : null, '' !== $actor ? $actor : null);
        $actorIds = array_values(array_unique(array_map(static fn (ConfigAuditEntry $e): string => $e->actorUserId(), $entries)));
        $names = $this->users->findDisplayNamesByIds($tenant, $actorIds);

        return $this->render('parametrage/audit.html.twig', [
            'entries' => array_map(
                static fn (ConfigAuditEntry $e): array => [
                    'at' => $e->recordedAt()->format('d/m/Y H:i'),
                    'actor' => $names[$e->actorUserId()] ?? $e->actorUserId(),
                    'action' => $e->action()->label(),
                    'objectType' => $e->objectType(),
                    'objectLabel' => $e->objectLabel(),
                    'field' => $e->field(),
                    'before' => $e->valueBefore(),
                    'after' => $e->valueAfter(),
                ],
                $entries,
            ),
            'objectType' => $objectType,
            'actor' => $actor,
        ]);
    }
}
