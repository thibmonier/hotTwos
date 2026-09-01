<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Application\Reminder\ConfigureReminders;
use App\Application\Reminder\ReminderDecision;
use App\Application\Reminder\ScheduleReminders;
use App\Domain\Authorization\Permission;
use App\Domain\Reminder\ReminderChannel;
use App\Domain\Reminder\ReminderException;
use App\Domain\Reminder\ReminderLog;
use App\Domain\Reminder\ReminderLogRepository;
use App\Domain\User\User;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-056 (T-056-05) — écran de paramétrage des relances (adaptateur web). Réservé à
 * `MANAGE_REMINDERS` (ARC-106, 403 sinon). Trois sections : configuration de la règle du tenant,
 * prévisualisation des relances dues « maintenant » (moteur), historique des relances émises.
 * L'enregistrement suit le pattern POST-Redirect-Get avec jeton CSRF.
 */
final class ReminderPageController extends AbstractController
{
    private const int HISTORY_LIMIT = 50;

    /** Libellés métier des canaux (pas le jargon technique de l'enum). */
    private const array CHANNEL_LABELS = [
        'in_app' => 'Dans l\'application',
        'email' => 'E-mail',
        'both' => 'Application et e-mail',
    ];

    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly ConfigureReminders $configure,
        private readonly ScheduleReminders $engine,
        private readonly ReminderLogRepository $logs,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/relances', name: 'reminders_page', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): Response
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_REMINDERS);
        $rule = $this->configure->current($user);
        $now = $this->clock->now();

        return $this->render('reminder/index.html.twig', [
            'rule' => [
                'initialDelayDays' => $rule->initialDelayDays(),
                'frequencyDays' => $rule->frequencyDays(),
                'channel' => $rule->channel()->value,
                'escalationEnabled' => $rule->escalationEnabled(),
                'active' => $rule->isActive(),
            ],
            'channelLabels' => self::CHANNEL_LABELS,
            'active' => $rule->isActive(),
            'preview' => array_map(
                fn (ReminderDecision $d): array => [
                    'userId' => $d->userId,
                    'week' => $d->weekStart->format('Y-m-d'),
                    'rank' => $d->sequence,
                    'escalated' => $d->escalated,
                ],
                $rule->isActive() ? $this->engine->plan($user->tenantId(), $now) : [],
            ),
            'history' => array_map(
                fn (ReminderLog $log): array => [
                    'userId' => $log->userId(),
                    'week' => $log->weekStart()->format('Y-m-d'),
                    'channel' => self::CHANNEL_LABELS[$log->channel()->value],
                    'rank' => $log->sequence(),
                    'escalated' => $log->isEscalated(),
                    'sentAt' => $log->sentAt()->format('d/m/Y H:i'),
                ],
                $this->logs->findRecent($user->tenantId(), null, self::HISTORY_LIMIT),
            ),
        ]);
    }

    #[Route('/relances', name: 'reminders_update', methods: ['POST'])]
    public function update(#[CurrentUser] User $user, Request $request): RedirectResponse
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_REMINDERS);

        if (!$this->isCsrfTokenValid('configure_reminders', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('reminders_page');
        }

        $initialDelayDays = filter_var($request->request->get('initialDelayDays'), \FILTER_VALIDATE_INT);
        $frequencyDays = filter_var($request->request->get('frequencyDays'), \FILTER_VALIDATE_INT);
        $channel = ReminderChannel::tryFrom((string) $request->request->get('channel'));
        $escalationEnabled = $request->request->has('escalationEnabled');
        $active = $request->request->has('active');

        if (false === $initialDelayDays || false === $frequencyDays || !$channel instanceof ReminderChannel) {
            $this->addFlash('error', 'Paramètres invalides : vérifiez le délai, la fréquence et le canal.');

            return $this->redirectToRoute('reminders_page');
        }

        try {
            $this->configure->update($user, $initialDelayDays, $frequencyDays, $channel, $escalationEnabled, $active);
            $this->addFlash('success', $active
                ? 'Réglages enregistrés.'
                : 'Réglages enregistrés. Les relances automatiques sont désactivées.');
        } catch (ReminderException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('reminders_page');
    }
}
