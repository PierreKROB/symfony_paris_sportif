<?php

namespace App\Service;

use App\Entity\Bet;
use App\Entity\Outcome;
use App\Entity\SportEvent;
use App\Entity\User;
use App\Repository\BetRepository;
use Doctrine\ORM\EntityManagerInterface;

class BettingService
{
    public function __construct(
        private EntityManagerInterface $em,
        private WalletService $walletService,
        private OddsCalculatorService $oddsCalculator,
        private ResponsibleGamingService $responsibleGaming,
    ) {}

    /**
     * Place un pari. Retourne les erreurs s'il y en a, tableau vide si ok.
     */
    public function placeBet(User $user, Outcome $outcome, float $amount): array
    {
        $event = $outcome->getEvent();
        $errors = [];

        // Vérifications métier
        if ($event->getStatus() !== SportEvent::STATUS_PUBLISHED) {
            $errors[] = 'Les paris ne sont plus acceptés pour cet événement.';
        }

        if ($event->getStartsAt() <= new \DateTime()) {
            $errors[] = 'L\'événement a déjà commencé.';
        }

        if (!$this->walletService->hasSufficientBalance($user, $amount)) {
            $errors[] = 'Solde insuffisant.';
        }

        $rgErrors = $this->responsibleGaming->canPlaceBet($user, $amount);
        $errors = array_merge($errors, $rgErrors);

        if (!empty($errors)) {
            return $errors;
        }

        // Crée le pari avec la cote actuelle figée
        $bet = new Bet();
        $bet->setUser($user);
        $bet->setOutcome($outcome);
        $bet->setAmount((string) $amount);
        $bet->setLockedOdds($outcome->getOdds());

        $this->em->persist($bet);

        // Débite le portefeuille
        $this->walletService->debit($user, $amount, 'Mise sur ' . $event->getName() . ' — ' . $outcome->getLabel());

        // Recalcule les cotes après ce nouveau pari
        $this->oddsCalculator->recalculate($event);

        $this->em->flush();

        return [];
    }

    /**
     * Résout les paris d'un événement une fois le résultat saisi.
     */
    public function resolveWinners(SportEvent $event): void
    {
        foreach ($event->getOutcomes() as $outcome) {
            foreach ($outcome->getBets() as $bet) {
                if ($bet->getStatus() !== Bet::STATUS_PENDING) {
                    continue;
                }

                if ($outcome->isWinner()) {
                    $gain = $bet->getPotentialGain();
                    $bet->setStatus(Bet::STATUS_WON);
                    $this->walletService->credit(
                        $bet->getUser(),
                        $gain,
                        'Gain — ' . $event->getName() . ' — ' . $outcome->getLabel()
                    );
                } else {
                    $bet->setStatus(Bet::STATUS_LOST);
                }
            }
        }

        $this->em->flush();
    }

    /**
     * Annule tous les paris en attente d'un événement (événement annulé).
     */
    public function cancelBetsForEvent(SportEvent $event): void
    {
        foreach ($event->getOutcomes() as $outcome) {
            foreach ($outcome->getBets() as $bet) {
                if ($bet->getStatus() !== Bet::STATUS_PENDING) {
                    continue;
                }

                $bet->setStatus(Bet::STATUS_CANCELLED);
                $this->walletService->refund(
                    $bet->getUser(),
                    (float) $bet->getAmount(),
                    'Remboursement — ' . $event->getName() . ' annulé'
                );
            }
        }

        $this->em->flush();
    }
}
