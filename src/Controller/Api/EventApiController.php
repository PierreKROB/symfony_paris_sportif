<?php

namespace App\Controller\Api;

use App\Entity\SportEvent;
use App\Repository\SportEventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class EventApiController extends AbstractController
{
    #[Route('/events', name: 'api_events', methods: ['GET'])]
    public function index(SportEventRepository $repo): JsonResponse
    {
        $events = $repo->findBy(['status' => SportEvent::STATUS_PUBLISHED]);

        return $this->json(array_map(fn($e) => $this->serialize($e), $events));
    }

    #[Route('/events/{id}', name: 'api_event_show', methods: ['GET'])]
    public function show(SportEvent $event): JsonResponse
    {
        return $this->json($this->serialize($event));
    }

    private function serialize(SportEvent $e): array
    {
        return [
            'id'         => $e->getId(),
            'name'       => $e->getName(),
            'tournament' => $e->getTournament(),
            'teamA'      => $e->getTeamA(),
            'teamB'      => $e->getTeamB(),
            'status'     => $e->getStatus(),
            'startsAt'   => $e->getStartsAt()?->format('Y-m-d H:i'),
            'outcomes'   => $e->getOutcomes()->map(fn($o) => [
                'id'    => $o->getId(),
                'label' => $o->getLabel(),
                'odds'  => $o->getOdds(),
            ])->toArray(),
        ];
    }
}
