<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Vérifié à chaque tentative de connexion.
 * Lance une exception si le compte ne peut pas se connecter.
 */
class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->isSuspended()) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte a été suspendu. Contactez le support.'
            );
        }

        if ($user->isCurrentlySelfExcluded()) {
            $until = $user->getSelfExcludedUntil()->format('d/m/Y');
            throw new CustomUserMessageAccountStatusException(
                "Vous êtes en auto-exclusion jusqu'au {$until}. Connexion impossible."
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Rien à vérifier après l'authentification
    }
}
