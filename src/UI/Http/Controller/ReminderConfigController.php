<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Reminder\ConfigureReminders;
use App\Domain\Reminder\ReminderChannel;
use App\Domain\Reminder\ReminderRule;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-056 (T-056-04) — API de paramétrage des relances du tenant. Habilitation `MANAGE_REMINDERS`
 * vérifiée dans la couche applicative (403 sinon, ARC-19). Valeurs invalides → 422 (listener).
 */
final class ReminderConfigController extends AbstractController
{
    public function __construct(private readonly ConfigureReminders $configure)
    {
    }

    #[Route('/api/reminders/rules', name: 'api_reminder_rules_get', methods: ['GET'])]
    public function show(#[CurrentUser] User $user): JsonResponse
    {
        return new JsonResponse($this->present($this->configure->current($user)));
    }

    #[Route('/api/reminders/rules', name: 'api_reminder_rules_put', methods: ['PUT'])]
    public function update(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        $initialDelayDays = filter_var($payload['initialDelayDays'] ?? null, \FILTER_VALIDATE_INT);
        $frequencyDays = filter_var($payload['frequencyDays'] ?? null, \FILTER_VALIDATE_INT);
        $channel = ReminderChannel::tryFrom(is_string($payload['channel'] ?? null) ? $payload['channel'] : '');
        $escalationEnabled = filter_var($payload['escalationEnabled'] ?? null, \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE);
        $active = filter_var($payload['active'] ?? null, \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE);

        if (false === $initialDelayDays || false === $frequencyDays || !$channel instanceof ReminderChannel || null === $escalationEnabled || null === $active) {
            return new JsonResponse(
                ['error' => 'Champs requis : initialDelayDays (entier), frequencyDays (entier), channel (in_app|email|both), escalationEnabled (booléen), active (booléen).'],
                JsonResponse::HTTP_BAD_REQUEST,
            );
        }

        $rule = $this->configure->update($user, $initialDelayDays, $frequencyDays, $channel, $escalationEnabled, $active);

        return new JsonResponse($this->present($rule));
    }

    /**
     * @return array<string, mixed>
     */
    private function present(ReminderRule $rule): array
    {
        return [
            'initialDelayDays' => $rule->initialDelayDays(),
            'frequencyDays' => $rule->frequencyDays(),
            'channel' => $rule->channel()->value,
            'escalationEnabled' => $rule->escalationEnabled(),
            'active' => $rule->isActive(),
        ];
    }
}
