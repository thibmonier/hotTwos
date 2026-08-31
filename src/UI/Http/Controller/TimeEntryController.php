<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Timesheet\RecordTimeEntry;
use App\Domain\Timesheet\TimesheetException;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * US-050 — saisie d'une imputation de temps. Un collaborateur saisit **pour lui-même** :
 * l'utilisateur et le tenant proviennent de l'authentification (ARC-61), jamais du corps
 * de la requête. Les règles métier sont appliquées dans le cas d'usage (ARC-19).
 */
final class TimeEntryController extends AbstractController
{
    public function __construct(private readonly RecordTimeEntry $recordTimeEntry)
    {
    }

    #[Route('/api/time-entries', name: 'api_time_entry_record', methods: ['POST'])]
    public function record(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        $projectId = is_string($payload['projectId'] ?? null) ? $payload['projectId'] : '';
        $rawDate = is_string($payload['date'] ?? null) ? $payload['date'] : '';
        $minutes = filter_var($payload['minutes'] ?? null, \FILTER_VALIDATE_INT);
        $comment = is_string($payload['comment'] ?? null) ? $payload['comment'] : null;

        if ('' === $projectId || false === $minutes) {
            return new JsonResponse(['error' => 'Champs requis : projectId, date (YYYY-MM-DD), minutes (entier).'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $rawDate);
        if (false === $date) {
            return new JsonResponse(['error' => 'Date invalide (format attendu : YYYY-MM-DD).'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $this->recordTimeEntry->record($user->tenantId(), $user->id(), $projectId, $date, $minutes, $comment);
        } catch (TimesheetException|InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(['recorded' => true], JsonResponse::HTTP_CREATED);
    }
}
