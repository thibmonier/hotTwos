<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Fec\FecConfiguration;
use App\Domain\Fec\FecConfigurationRepository;
use App\Domain\User\User;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-074 (T-074-06) — configuration comptable FEC par tenant (SIREN + journal + mapping des comptes).
 *
 * Paramétrage administrateur (deny-by-default via `MANAGE_ORGANIZATION`). Prérequis de l'export FEC.
 */
final class FecConfigurationController extends AbstractController
{
    /** Champs du formulaire (name => setter/constructeur arg). */
    private const array FIELDS = [
        'siren', 'journalCode', 'journalLib', 'revenueAccountNum', 'revenueAccountLib',
        'receivableAccountNum', 'receivableAccountLib', 'costAccountNum', 'costAccountLib',
        'costCounterpartAccountNum', 'costCounterpartAccountLib',
    ];

    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly FecConfigurationRepository $configurations,
    ) {
    }

    #[Route('/finance/config-fec', name: 'fec_config', methods: ['GET'])]
    public function edit(#[CurrentUser] User $user): Response
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);

        return $this->render('finance/fec-config.html.twig', [
            'config' => $this->configurations->findForTenant($user->tenantId()),
        ]);
    }

    #[Route('/finance/config-fec', name: 'fec_config_save', methods: ['POST'])]
    public function save(#[CurrentUser] User $user, Request $request): RedirectResponse
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);
        if (!$this->isCsrfTokenValid('fec_config', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('fec_config');
        }

        $values = [];
        foreach (self::FIELDS as $field) {
            $values[$field] = trim((string) $request->request->get($field));
        }

        try {
            $existing = $this->configurations->findForTenant($user->tenantId());
            $config = $existing instanceof FecConfiguration
                ? $this->apply($existing, $values)
                : new FecConfiguration(
                    $user->tenantId(),
                    $values['siren'],
                    $values['journalCode'],
                    $values['journalLib'],
                    $values['revenueAccountNum'],
                    $values['revenueAccountLib'],
                    $values['receivableAccountNum'],
                    $values['receivableAccountLib'],
                    $values['costAccountNum'],
                    $values['costAccountLib'],
                    $values['costCounterpartAccountNum'],
                    $values['costCounterpartAccountLib'],
                );
            $this->configurations->save($config);
            $this->addFlash('success', 'Configuration comptable FEC enregistrée.');
        } catch (InvalidArgumentException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('fec_config');
    }

    /**
     * @param array<string, string> $v
     */
    private function apply(FecConfiguration $config, array $v): FecConfiguration
    {
        $config->reconfigure(
            $v['siren'],
            $v['journalCode'],
            $v['journalLib'],
            $v['revenueAccountNum'],
            $v['revenueAccountLib'],
            $v['receivableAccountNum'],
            $v['receivableAccountLib'],
            $v['costAccountNum'],
            $v['costAccountLib'],
            $v['costCounterpartAccountNum'],
            $v['costCounterpartAccountLib'],
        );

        return $config;
    }
}
