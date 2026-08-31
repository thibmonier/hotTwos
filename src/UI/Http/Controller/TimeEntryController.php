<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Timesheet\CellError;
use App\Application\Timesheet\DuplicatePreviousWeek;
use App\Application\Timesheet\RecordTimeEntry;
use App\Application\Timesheet\RecordWeek;
use App\Application\Timesheet\WeekCell;
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
    public function __construct(
        private readonly RecordTimeEntry $recordTimeEntry,
        private readonly RecordWeek $recordWeek,
        private readonly DuplicatePreviousWeek $duplicatePreviousWeek,
    ) {
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

    /**
     * US-051 — enregistrement d'une semaine complète en une requête (≤ 2 min). Best-effort :
     * les cellules valides sont enregistrées, les refus sont détaillés cellule par cellule.
     */
    #[Route('/api/time-entries/week', name: 'api_time_entry_record_week', methods: ['POST'])]
    public function recordWeek(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        $rawEntries = $payload['entries'] ?? null;
        if (!is_array($rawEntries)) {
            return new JsonResponse(['error' => 'Champ requis : entries (liste de {projectId, date, minutes}).'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $cells = [];
        foreach ($rawEntries as $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $projectId = is_string($raw['projectId'] ?? null) ? $raw['projectId'] : '';
            $minutes = filter_var($raw['minutes'] ?? null, \FILTER_VALIDATE_INT);
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', is_string($raw['date'] ?? null) ? $raw['date'] : '');
            $comment = is_string($raw['comment'] ?? null) ? $raw['comment'] : null;
            if ('' === $projectId || false === $minutes || false === $date) {
                continue;
            }
            $cells[] = new WeekCell($projectId, $date, $minutes, $comment);
        }

        $errors = $this->recordWeek->record($user->tenantId(), $user->id(), $cells);

        return new JsonResponse([
            'recorded' => count($cells) - count($errors),
            'errors' => array_map(
                static fn (CellError $error): array => [
                    'projectId' => $error->projectId,
                    'date' => $error->date,
                    'message' => $error->message,
                ],
                $errors,
            ),
        ]);
    }

    /**
     * US-051 — duplique la semaine précédente dans la semaine cible (levier ≤ 2 min).
     */
    #[Route('/api/time-entries/duplicate-week', name: 'api_time_entry_duplicate_week', methods: ['POST'])]
    public function duplicateWeek(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        $weekStart = DateTimeImmutable::createFromFormat('!Y-m-d', is_string($payload['weekStart'] ?? null) ? $payload['weekStart'] : '');
        if (false === $weekStart) {
            return new JsonResponse(['error' => 'Champ requis : weekStart (YYYY-MM-DD, lundi de la semaine cible).'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $monday = $weekStart->modify('monday this week');
        $errors = $this->duplicatePreviousWeek->into($user->tenantId(), $user->id(), $monday);

        return new JsonResponse(['errors' => count($errors)]);
    }
}
