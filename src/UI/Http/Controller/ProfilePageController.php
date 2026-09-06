<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Application\Pricing\DefineSellingRate;
use App\Domain\Authorization\Permission;
use App\Domain\Client\Client;
use App\Domain\Client\ClientRepository;
use App\Domain\Pricing\PricingException;
use App\Domain\Pricing\Profile;
use App\Domain\Pricing\ProfileRepository;
use App\Domain\Pricing\RateScope;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectRepository;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\User\User;
use App\Domain\User\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use DateTimeImmutable;

/**
 * US-011 (T-011-06) — écran d'administration des profils et de leurs tarifs (adaptateur web).
 *
 * Réservé à l'admin (habilitation applicative, ARC-106). Les actions (création, désactivation,
 * définition d'un tarif) et l'historique tarifaire passent par l'API via un contrôleur Stimulus.
 */
final class ProfilePageController extends AbstractController
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly ProfileRepository $profiles,
        private readonly UserRepository $users,
        private readonly ClientRepository $clients,
        private readonly ProjectRepository $projectsRepo,
        private readonly DefineSellingRate $defineSellingRate,
    ) {
    }

    #[Route('/profils', name: 'pricing_admin', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): Response
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_PRICING);

        $rows = array_map(
            static fn (Profile $profile): array => [
                'id' => $profile->id(),
                'name' => $profile->name(),
                'mode' => $profile->calculationMode()->value,
                'active' => $profile->isActive(),
            ],
            $this->profiles->findByTenant($user->tenantId()),
        );

        $collaborators = $this->users->findDisplayNamesByIds(
            $user->tenantId(),
            $this->users->findIdsByTenant($user->tenantId()),
        );
        asort($collaborators);

        return $this->render('pricing/index.html.twig', [
            'rows' => $rows,
            'collaborators' => $collaborators,
            // US-015 — surcharges de taux de vente (client/projet).
            'clients' => array_map(
                static fn (Client $c): array => ['id' => $c->id(), 'name' => $c->name()],
                $this->clients->findAllByTenant($user->tenantId()),
            ),
            'projects' => array_map(
                static fn (Project $p): array => ['id' => $p->id(), 'name' => $p->name()],
                $this->projectsRepo->findAllByTenant($user->tenantId()),
            ),
        ]);
    }

    #[Route('/profils/taux-vente', name: 'pricing_selling_rate_define', methods: ['POST'])]
    public function defineSellingRate(#[CurrentUser] User $user, Request $request): RedirectResponse
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_PRICING);
        if (!$this->isCsrfTokenValid('selling_rate', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('pricing_admin');
        }

        $profileId = trim((string) $request->request->get('profileId'));
        $scope = RateScope::tryFrom((string) $request->request->get('scope'));
        $scopeRefId = trim((string) $request->request->get('scopeRefId'));
        $euros = filter_var($request->request->get('sellingEuros'), \FILTER_VALIDATE_INT);
        $from = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $request->request->get('from'));

        if (!$scope instanceof RateScope || '' === $profileId || '' === $scopeRefId || false === $euros || false === $from) {
            $this->addFlash('error', 'Surcharge de taux : profil, niveau, cible, date et montant requis.');

            return $this->redirectToRoute('pricing_admin');
        }

        try {
            $this->defineSellingRate->define(
                $user->tenantId(),
                $user,
                $profileId,
                $scope,
                $scopeRefId,
                EffectivePeriod::since($from),
                $euros * 100,
                $request->request->has('confirmRetroactive'),
            );
            $this->addFlash('success', sprintf('Taux de vente (%s) enregistré.', $scope->label()));
        } catch (PricingException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('pricing_admin');
    }
}
