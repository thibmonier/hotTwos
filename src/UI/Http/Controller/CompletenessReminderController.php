<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Application\Reminder\SendManualReminders;
use App\Domain\Authorization\Permission;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-092b (CPL-04) — relance inline depuis la grille de complétude (POST XHR, sans quitter l'écran).
 * Ordre des gardes : habilitation `MANAGE_REMINDERS` (403), jeton CSRF (403), sélection non vide (422),
 * puis relance manuelle immédiate des collaborateurs sélectionnés.
 */
final class CompletenessReminderController extends AbstractController
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly SendManualReminders $reminders,
    ) {
    }

    #[Route('/completude/relances', name: 'completeness_remind', methods: ['POST'])]
    public function remind(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        if (!$this->authorizer->can($user, Permission::MANAGE_REMINDERS)) {
            return $this->json(['error' => 'Vous n\'êtes pas autorisé à envoyer des relances.'], Response::HTTP_FORBIDDEN);
        }
        if (!$this->isCsrfTokenValid('remind_completeness', (string) $request->request->get('_token'))) {
            return $this->json(['error' => 'Jeton de sécurité invalide.'], Response::HTTP_FORBIDDEN);
        }

        /** @var list<string> $userIds */
        $userIds = array_values(array_filter(
            $request->request->all('userIds'),
            static fn (mixed $id): bool => is_string($id) && '' !== $id,
        ));
        if ([] === $userIds) {
            return $this->json(['error' => 'Sélectionnez au moins un collaborateur.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $sent = $this->reminders->send($user->tenantId(), $userIds);

        return $this->json([
            'sent' => $sent,
            'message' => 0 === $sent
                ? 'Aucune relance envoyée (collaborateurs déjà à jour).'
                : sprintf('%d relance%s envoyée%s.', $sent, $sent > 1 ? 's' : '', $sent > 1 ? 's' : ''),
        ]);
    }
}
