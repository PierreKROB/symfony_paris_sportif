<?php

namespace App\Security\Voter;

use App\Entity\Bet;
use App\Entity\SportEvent;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class BetVoter extends Voter
{
    const VIEW   = 'BET_VIEW';
    const CANCEL = 'BET_CANCEL';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::CANCEL])
            && $subject instanceof Bet;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) return false;

        /** @var Bet $bet */
        $bet = $subject;

        $isAdmin = in_array('ROLE_ADMIN', $token->getRoleNames());

        return match($attribute) {
            self::VIEW   => $isAdmin || $bet->getUser() === $user,
            self::CANCEL => $bet->getUser() === $user
                         && $bet->getStatus() === Bet::STATUS_PENDING
                         && $bet->getOutcome()?->getEvent()?->getStatus() === SportEvent::STATUS_PUBLISHED,
            default      => false,
        };
    }
}
