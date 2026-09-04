<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * US-068 — écrans web d'authentification. La page de connexion (`/login`) est aussi le `check_path`
 * du pare-feu `main` (form_login, CSRF) ; la soumission POST est interceptée par le pare-feu.
 * Le message d'erreur reste générique (pas de révélation, CA-1/CA-3).
 */
final class LoginController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            return $this->redirectToRoute('home');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'has_error' => $authenticationUtils->getLastAuthenticationError() instanceof \Symfony\Component\Security\Core\Exception\AuthenticationException,
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(): never
    {
        throw new LogicException('Interceptée par le pare-feu (logout).');
    }
}
