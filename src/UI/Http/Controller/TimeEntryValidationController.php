<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Timesheet\ValidateTimeEntries;
use App\Domain\Timesheet\TimesheetException;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-055 — validation/refus des imputations par lot. L'habilitation (permission +
 * périmètre « ses projets ») est faite dans le cas d'usage (ARC-19) : un refus métier
 * devient 422, un refus d'habilitation 403 (via le listener), aucune décision côté UI.
 */
final class TimeEntryValidationController extends AbstractController
{
    public function __construct(private readonly ValidateTimeEntries $validateTimeEntries)
    {
    }

    #[Route('/api/time-entries/validate', name: 'api_time_entry_validate', methods: ['POST'])]
    public function decide(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        $rawIds = $payload['entryIds'] ?? null;
        if (!is_array($rawIds) || [] === $rawIds) {
            return new JsonResponse(['error' => 'Champ requis : entryIds (liste non vide).'], JsonResponse::HTTP_BAD_REQUEST);
        }
        $entryIds = array_values(array_filter(array_map(
            static fn (mixed $id): string => is_string($id) ? $id : '',
            $rawIds,
        )));

        $decision = is_string($payload['decision'] ?? null) ? $payload['decision'] : '';
        $reason = is_string($payload['reason'] ?? null) ? $payload['reason'] : '';

        try {
            $decided = match ($decision) {
                'validate' => $this->validateTimeEntries->validate($user->tenantId(), $user, $entryIds),
                'reject' => $this->validateTimeEntries->reject($user->tenantId(), $user, $entryIds, $reason),
                default => throw new TimesheetException('Décision invalide (attendu : "validate" ou "reject").'),
            };
        } catch (TimesheetException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(['decided' => $decided]);
    }
}
