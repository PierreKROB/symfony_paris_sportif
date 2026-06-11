<?php

namespace App\Service;

use App\Entity\SportEvent;
use Doctrine\ORM\EntityManagerInterface;

class SportEventService
{
    public function __construct(
        private EntityManagerInterface $em,
        private BettingService $bettingService,
    ) {}

    public function publish(SportEvent $event): void
    {
        $event->setStatus(SportEvent::STATUS_PUBLISHED);
        $this->em->flush();
    }

    public function closeBets(SportEvent $event): void
    {
        $event->setStatus(SportEvent::STATUS_CLOSED);
        $this->em->flush();
    }

    public function setResult(SportEvent $event, int $winnerOutcomeId): void
    {
        foreach ($event->getOutcomes() as $outcome) {
            $outcome->setIsWinner($outcome->getId() === $winnerOutcomeId);
        }

        $event->setStatus(SportEvent::STATUS_FINISHED);
        $this->bettingService->resolveWinners($event);
    }

    public function cancel(SportEvent $event): void
    {
        $event->setStatus(SportEvent::STATUS_CANCELLED);
        $this->bettingService->cancelBetsForEvent($event);
    }

    public function canDelete(SportEvent $event): bool
    {
        return $event->getStatus() === SportEvent::STATUS_DRAFT;
    }
}
