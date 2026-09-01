<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Reminder\SetReminderPreference;
use App\Domain\Reminder\ReminderPreference;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-056 (T-056-04, CA-2) — préférence de relance du collaborateur **courant**. L'utilisateur et le
 * tenant proviennent de l'authentification (ARC-61), jamais du corps : un administrateur ne peut pas
 * agir sur la préférence d'un tiers (opt-out non forçable).
 */
final class ReminderPreferenceController extends AbstractController
{
    public function __construct(private readonly SetReminderPreference $preference)
    {
    }

    #[Route('/api/me/reminder-preference', name: 'api_reminder_preference_get', methods: ['GET'])]
    public function show(#[CurrentUser] User $user): JsonResponse
    {
        return new JsonResponse($this->present($this->preference->current($user)));
    }

    #[Route('/api/me/reminder-preference', name: 'api_reminder_preference_put', methods: ['PUT'])]
    public function update(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        $optedOut = filter_var($payload['optedOut'] ?? null, \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE);
        if (null === $optedOut) {
            return new JsonResponse(['error' => 'Champ requis : optedOut (booléen).'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $preference = $optedOut ? $this->preference->optOut($user) : $this->preference->optIn($user);

        return new JsonResponse($this->present($preference));
    }

    /**
     * @return array<string, mixed>
     */
    private function present(ReminderPreference $preference): array
    {
        return [
            'optedOut' => $preference->isOptedOut(),
            'updatedAt' => $preference->updatedAt()->format(\DATE_ATOM),
        ];
    }
}
