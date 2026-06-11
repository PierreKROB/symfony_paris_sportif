<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Écoute chaque requête HTTP entrante.
 *
 * Si un utilisateur déjà connecté est suspendu ou auto-exclu
 * (situation possible si l'admin l'a suspendu après sa connexion),
 * on le déconnecte immédiatement et on le redirige vers la page de login.
 *
 * Le UserChecker ne tourne qu'à la connexion — ce subscriber protège
 * les utilisateurs qui sont déjà en session.
 */
class SuspendedUserSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private RouterInterface       $router,
    ) {}

    public static function getSubscribedEvents(): array
    {
        // KernelEvents::REQUEST est déclenché à chaque requête, avant le controller
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // On ignore les sous-requêtes (assets, profiler, etc.)
        if (!$event->isMainRequest()) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return;
        }

        if ($user->isSuspended() || $user->isCurrentlySelfExcluded()) {
            // Supprime le token de session = déconnexion immédiate
            $this->tokenStorage->setToken(null);

            // Redirige vers le login avec un message clair dans l'URL
            $loginUrl = $this->router->generate('app_login');
            $event->setResponse(new RedirectResponse($loginUrl));
        }
    }
}
