<?php

namespace App\Controller\Admin;

use App\Entity\Bet;
use App\Entity\SportEvent;
use App\Entity\Transaction;
use App\Repository\BetRepository;
use App\Repository\SportEventRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/stats', name: 'admin_stats')]
class StatsController extends AbstractController
{
    #[Route('', name: '_index')]
    public function index(
        Request             $request,
        UserRepository      $userRepo,
        SportEventRepository $eventRepo,
        BetRepository       $betRepo,
    ): Response {
        // --- Chiffres globaux ---
        $totalUsers  = $userRepo->count([]);
        $totalEvents = $eventRepo->count([]);

        // Nombre d'événements par statut
        $eventsByStatus = [];
        foreach ([SportEvent::STATUS_DRAFT, SportEvent::STATUS_PUBLISHED, SportEvent::STATUS_CLOSED, SportEvent::STATUS_FINISHED, SportEvent::STATUS_CANCELLED] as $status) {
            $eventsByStatus[$status] = $eventRepo->count(['status' => $status]);
        }

        // Nombre de paris et montant total misé
        $totalBets   = $betRepo->count([]);
        $totalWagered = (float) ($betRepo->createQueryBuilder('b')
            ->select('SUM(b.amount)')
            ->where('b.status != :cancelled')
            ->setParameter('cancelled', Bet::STATUS_CANCELLED)
            ->getQuery()
            ->getSingleScalarResult() ?? 0);

        // Paris par statut
        $betsByStatus = [];
        foreach ([Bet::STATUS_PENDING, Bet::STATUS_WON, Bet::STATUS_LOST, Bet::STATUS_CANCELLED] as $status) {
            $betsByStatus[$status] = $betRepo->count(['status' => $status]);
        }

        // --- Liste paginée de tous les paris ---
        $page       = max(1, (int) $request->query->get('page', 1));
        $pagination = $betRepo->findAllPaginated($page);

        return $this->render('admin/stats/index.html.twig', [
            'totalUsers'     => $totalUsers,
            'totalEvents'    => $totalEvents,
            'eventsByStatus' => $eventsByStatus,
            'totalBets'      => $totalBets,
            'totalWagered'   => $totalWagered,
            'betsByStatus'   => $betsByStatus,
            'bets'           => $pagination['items'],
            'pagination'     => $pagination,
        ]);
    }
}
