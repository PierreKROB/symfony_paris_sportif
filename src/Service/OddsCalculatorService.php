<?php

namespace App\Service;

use App\Entity\Outcome;
use App\Entity\SportEvent;
use App\Repository\BetRepository;

class OddsCalculatorService
{
    const MIN_ODDS = 1.10;
    const MAX_ODDS = 5.00;
    const DEFAULT_ODDS = 1.50;

    public function __construct(private BetRepository $betRepository) {}

    public function recalculate(SportEvent $event): void
    {
        $outcomes = $event->getOutcomes();
        if ($outcomes->isEmpty()) {
            return;
        }

        // Total misé sur tout l'événement
        $totalEvent = 0.0;
        foreach ($outcomes as $outcome) {
            $totalEvent += $this->getTotalBetOnOutcome($outcome);
        }

        // Pas encore de paris : on garde la cote par défaut
        if ($totalEvent === 0.0) {
            return;
        }

        foreach ($outcomes as $outcome) {
            $totalOnOutcome = $this->getTotalBetOnOutcome($outcome);

            if ($totalOnOutcome === 0.0) {
                $outcome->setOdds(self::MAX_ODDS);
                continue;
            }

            // Formule : cote = total_misé_sur_événement / total_misé_sur_issue
            $newOdds = $totalEvent / $totalOnOutcome;
            $newOdds = max(self::MIN_ODDS, min(self::MAX_ODDS, $newOdds));

            $outcome->setOdds(round($newOdds, 2));
        }
    }

    private function getTotalBetOnOutcome(Outcome $outcome): float
    {
        $bets = $this->betRepository->findBy([
            'outcome' => $outcome,
            'status'  => \App\Entity\Bet::STATUS_PENDING,
        ]);

        return array_sum(array_map(fn($b) => (float) $b->getAmount(), $bets));
    }
}
