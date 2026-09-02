<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Styleguide du design system HotOnes (US-061, T-061-05).
 *
 * Page de démonstration interne : tokens, composants, bascule de thème.
 * **Désactivée en production** (garde d'environnement) pour ne pas exposer un endpoint
 * inutile en prod. On garde la restriction côté contrôleur plutôt qu'une `condition:` de
 * route, qui exigerait la dépendance `symfony/expression-language` (non installée).
 *
 * Adaptateur web uniquement — aucune logique métier (ARC-15).
 */
#[Route('/styleguide', name: 'styleguide', methods: ['GET'])]
final class StyleguideController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
    ) {
    }

    public function __invoke(): Response
    {
        if ('prod' === $this->environment) {
            throw $this->createNotFoundException();
        }

        return $this->render('styleguide/index.html.twig');
    }
}
