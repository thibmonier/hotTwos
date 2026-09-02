<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Styleguide du design system HotOnes (US-061, T-061-05).
 *
 * Page de démonstration interne : tokens, composants, bascule de thème.
 * Route accessible uniquement en environnement non-production
 * pour éviter d'exposer un endpoint inutile en prod.
 *
 * Adaptateur web uniquement — aucune logique métier (ARC-15).
 */
#[Route('/styleguide', name: 'styleguide', methods: ['GET'])]
final class StyleguideController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('styleguide/index.html.twig');
    }
}
