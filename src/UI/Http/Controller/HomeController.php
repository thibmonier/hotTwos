<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Pricing\ProfileRepository;
use App\Domain\Project\ProjectRepository;
use App\Domain\Timesheet\TimeEntryRepository;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class HomeController extends AbstractController
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly ProfileRepository $profiles,
        private readonly ProjectRepository $projects,
        private readonly TimeEntryRepository $timeEntries,
    ) {
    }

    #[Route('/', name: 'home', methods: ['GET'])]
    public function __invoke(#[CurrentUser] ?User $user): Response
    {
        return $this->render('home/index.html.twig', [
            'appName' => 'HotOnes',
            'onboarding' => $this->onboardingChecklist($user),
        ]);
    }

    /**
     * US-019 (EF-REF-29, CA-3) — checklist de mise en route, affichée tant qu'elle n'est pas complète,
     * pour un utilisateur habilité à créer des projets. `null` sinon (pas de bandeau).
     *
     * @return array{profileReady: bool, projectCreated: bool, timeSubmitted: bool, complete: bool}|null
     */
    private function onboardingChecklist(?User $user): ?array
    {
        if (!$user instanceof User || !$this->authorizer->can($user, Permission::CREATE_PROJECT)) {
            return null;
        }

        $tenant = $user->tenantId();
        $profileReady = [] !== $this->profiles->findByTenant($tenant);
        $projectCreated = $this->projects->countByTenant($tenant) > 0;
        $timeSubmitted = $this->timeEntries->countByTenant($tenant) > 0;
        $complete = $profileReady && $projectCreated && $timeSubmitted;

        if ($complete) {
            return null;
        }

        return [
            'profileReady' => $profileReady,
            'projectCreated' => $projectCreated,
            'timeSubmitted' => $timeSubmitted,
            'complete' => false,
        ];
    }
}
