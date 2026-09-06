<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Application\Currency\ConfigureCurrency;
use App\Domain\Authorization\Permission;
use App\Domain\Currency\CurrencyException;
use App\Domain\Currency\ExchangeRateRepository;
use App\Domain\Currency\ReferenceCurrency;
use App\Domain\Currency\ReferenceCurrencyRepository;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use DateTimeImmutable;

/**
 * US-016 (T-016-04) — paramétrage des devises : devise de référence du tenant + taux de change datés.
 * Réservé à l'administrateur (`MANAGE_ORGANIZATION`).
 */
final class CurrencyConfigController extends AbstractController
{
    private const array KNOWN_CURRENCIES = ['EUR', 'USD', 'GBP', 'CHF', 'CAD', 'JPY'];

    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly ReferenceCurrencyRepository $references,
        private readonly ExchangeRateRepository $rates,
        private readonly ConfigureCurrency $configure,
    ) {
    }

    #[Route('/finance/config-devises', name: 'currency_config', methods: ['GET'])]
    public function edit(#[CurrentUser] User $user): Response
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);

        $reference = $this->references->findForTenant($user->tenantId())?->code() ?? ReferenceCurrency::DEFAULT_CODE;
        $rowsByCurrency = [];
        foreach (self::KNOWN_CURRENCIES as $code) {
            if ($code === $reference) {
                continue;
            }
            foreach ($this->rates->findForCurrency($user->tenantId(), $code) as $rate) {
                $rowsByCurrency[] = [
                    'code' => $rate->code(),
                    'from' => $rate->period()->from()->format('d/m/Y'),
                    'rate' => number_format($rate->rateToReferenceMillis() / 1000, 3, ',', ' '),
                ];
            }
        }

        return $this->render('finance/currency-config.html.twig', [
            'reference' => $reference,
            'currencies' => self::KNOWN_CURRENCIES,
            'rates' => $rowsByCurrency,
        ]);
    }

    #[Route('/finance/config-devises/reference', name: 'currency_config_reference', methods: ['POST'])]
    public function setReference(#[CurrentUser] User $user, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('currency_config', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('currency_config');
        }

        try {
            $this->configure->setReferenceCurrency($user, (string) $request->request->get('code'));
            $this->addFlash('success', 'Devise de référence enregistrée.');
        } catch (CurrencyException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('currency_config');
    }

    #[Route('/finance/config-devises/taux', name: 'currency_config_rate', methods: ['POST'])]
    public function defineRate(#[CurrentUser] User $user, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('currency_config', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('currency_config');
        }

        $code = (string) $request->request->get('code');
        $rate = filter_var($request->request->get('rate'), \FILTER_VALIDATE_FLOAT);
        $from = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $request->request->get('from'));

        if (false === $rate || false === $from) {
            $this->addFlash('error', 'Taux de change : devise, taux et date requis.');

            return $this->redirectToRoute('currency_config');
        }

        try {
            $this->configure->defineExchangeRate($user, $code, EffectivePeriod::since($from), (int) round($rate * 1000));
            $this->addFlash('success', sprintf('Taux de change %s enregistré.', strtoupper($code)));
        } catch (CurrencyException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('currency_config');
    }
}
