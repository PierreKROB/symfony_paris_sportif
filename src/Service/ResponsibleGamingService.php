<?php

namespace App\Service;

use App\Entity\Bet;
use App\Entity\User;
use App\Repository\BetRepository;
use Doctrine\ORM\EntityManagerInterface;

class ResponsibleGamingService
{
    public function __construct(
        private BetRepository $betRepository,
        private EntityManagerInterface $em,
    ) {}

    public function canPlaceBet(User $user, float $amount): array
    {
        $errors = [];

        if ($user->isSuspended()) {
            $errors[] = 'Votre compte est suspendu.';
        }

        if ($user->isCurrentlySelfExcluded()) {
            $errors[] = 'Vous êtes en auto-exclusion jusqu\'au ' . $user->getSelfExcludedUntil()->format('d/m/Y');
        }

        if ($user->getDailyBetLimit() !== null) {
            $dailyTotal = $this->getDailyBetTotal($user);
            if ($dailyTotal + $amount > $user->getDailyBetLimit()) {
                $errors[] = 'Plafond de mise quotidien atteint (' . $user->getDailyBetLimit() . ' €).';
            }
        }

        if ($user->getWeeklyBetLimit() !== null) {
            $weeklyTotal = $this->getWeeklyBetTotal($user);
            if ($weeklyTotal + $amount > $user->getWeeklyBetLimit()) {
                $errors[] = 'Plafond de mise hebdomadaire atteint (' . $user->getWeeklyBetLimit() . ' €).';
            }
        }

        return $errors;
    }

    public function selfExclude(User $user, int $days): void
    {
        $until = new \DateTime('+' . $days . ' days');
        $user->setSelfExcludedUntil($until);
        $this->em->flush();
    }

    public function updateDailyBetLimit(User $user, ?float $newLimit): void
    {
        $current = $user->getDailyBetLimit();

        // Diminution ou suppression → immédiat
        if ($newLimit === null || $current === null || $newLimit <= $current) {
            $user->setDailyBetLimit($newLimit);
            $user->setPendingDailyBetLimit(null);
            $user->setPendingDailyBetLimitAt(null);
        } else {
            // Augmentation → dans 48h
            $user->setPendingDailyBetLimit($newLimit);
            $user->setPendingDailyBetLimitAt(new \DateTime());
        }

        $this->em->flush();
    }

    public function updateWeeklyBetLimit(User $user, ?float $newLimit): void
    {
        $current = $user->getWeeklyBetLimit();

        if ($newLimit === null || $current === null || $newLimit <= $current) {
            $user->setWeeklyBetLimit($newLimit);
            $user->setPendingWeeklyBetLimit(null);
            $user->setPendingWeeklyBetLimitAt(null);
        } else {
            $user->setPendingWeeklyBetLimit($newLimit);
            $user->setPendingWeeklyBetLimitAt(new \DateTime());
        }

        $this->em->flush();
    }

    // Applique les augmentations en attente si les 48h sont passées
    public function applyPendingLimits(User $user): void
    {
        $threshold = new \DateTime('-48 hours');

        if ($user->getPendingDailyBetLimit() !== null && $user->getPendingDailyBetLimitAt() <= $threshold) {
            $user->setDailyBetLimit($user->getPendingDailyBetLimit());
            $user->setPendingDailyBetLimit(null);
            $user->setPendingDailyBetLimitAt(null);
        }

        if ($user->getPendingWeeklyBetLimit() !== null && $user->getPendingWeeklyBetLimitAt() <= $threshold) {
            $user->setWeeklyBetLimit($user->getPendingWeeklyBetLimit());
            $user->setPendingWeeklyBetLimit(null);
            $user->setPendingWeeklyBetLimitAt(null);
        }

        $this->em->flush();
    }

    private function getDailyBetTotal(User $user): float
    {
        return $this->betRepository->getTotalBetForUserSince(
            $user,
            new \DateTime('today midnight')
        );
    }

    private function getWeeklyBetTotal(User $user): float
    {
        return $this->betRepository->getTotalBetForUserSince(
            $user,
            new \DateTime('monday this week midnight')
        );
    }
}
