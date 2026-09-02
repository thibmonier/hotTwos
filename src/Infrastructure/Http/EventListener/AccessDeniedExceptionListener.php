<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\EventListener;

use App\Domain\Authorization\AccessDeniedException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Twig\Environment;

/**
 * Traduit un refus d'habilitation métier ({@see AccessDeniedException}) en 403 Forbidden.
 *
 * Le contrôle reste dans la couche applicative (ARC-19) ; ce listener ne fait que présenter le refus
 * **sans divulguer d'internes** (règle 11 §7 — mishandling of exceptional conditions) :
 * - navigation web → page 403 **habillée** (shell HotOnes) offrant une sortie ;
 * - client API/XHR → JSON 403 avec un message **générique** (le slug de permission ne fuite pas).
 *
 * Le motif réel (qui contient l'interne, ex. « view:project_financials ») est **journalisé côté
 * serveur** pour le diagnostic, jamais renvoyé au client.
 */
#[AsEventListener(event: ExceptionEvent::class)]
final readonly class AccessDeniedExceptionListener
{
    private const string CLIENT_MESSAGE = "Vous n'avez pas l'autorisation d'accéder à cette ressource.";

    public function __construct(
        private Environment $twig,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        if (!$throwable instanceof AccessDeniedException) {
            return;
        }

        $request = $event->getRequest();
        // Motif réel journalisé (contient l'interne), jamais exposé au client.
        $this->logger->warning('Accès refusé : {reason}', [
            'reason' => $throwable->getMessage(),
            'path' => $request->getPathInfo(),
        ]);

        if ($this->prefersJson($request)) {
            $event->setResponse(new JsonResponse(
                ['error' => self::CLIENT_MESSAGE],
                JsonResponse::HTTP_FORBIDDEN,
            ));

            return;
        }

        $event->setResponse(new Response(
            $this->twig->render('error/403.html.twig', ['message' => self::CLIENT_MESSAGE]),
            Response::HTTP_FORBIDDEN,
        ));
    }

    /** Un client API/XHR attend du JSON ; une navigation web attend une page HTML. */
    private function prefersJson(Request $request): bool
    {
        return str_starts_with($request->getPathInfo(), '/api')
            || $request->isXmlHttpRequest()
            || 'json' === $request->getPreferredFormat();
    }
}
