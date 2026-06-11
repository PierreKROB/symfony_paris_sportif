<?php

namespace App\Controller\Manager;

use App\Entity\Outcome;
use App\Entity\SportEvent;
use App\Service\LoLEsportsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/manager/import', name: 'manager_import_')]
#[IsGranted('ROLE_MANAGER')]
class ImportEsportsController extends AbstractController
{
    public function __construct(
        private LoLEsportsService $esports,
        private EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'index')]
    public function index(): Response
    {
        $upcoming = $this->esports->getUpcomingMatches();

        return $this->render('manager/import/index.html.twig', [
            'events' => $upcoming,
        ]);
    }

    #[Route('/save', name: 'save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        $matchId   = $request->request->get('matchId');
        $teamA     = $request->request->get('teamA');
        $teamB     = $request->request->get('teamB');
        $name      = $request->request->get('name');
        $tournament = $request->request->get('tournament');
        $teamAImage = $request->request->get('teamAImage');
        $teamBImage = $request->request->get('teamBImage');
        $startsAt   = $request->request->get('startsAt');

        if (!$matchId || !$teamA || !$teamB) {
            $this->addFlash('error', 'Données manquantes.');
            return $this->redirectToRoute('manager_import_index');
        }

        // Avoid duplicates
        $existing = $this->em->getRepository(SportEvent::class)->findOneBy(['riotMatchId' => $matchId]);
        if ($existing) {
            $this->addFlash('warning', 'Ce match a déjà été importé.');
            return $this->redirectToRoute('manager_event_index');
        }

        $event = (new SportEvent())
            ->setName($name ?: $teamA . ' vs ' . $teamB)
            ->setTournament($tournament ?: 'LoL Esports')
            ->setTeamA($teamA)
            ->setTeamB($teamB)
            ->setTeamALogoUrl($teamAImage ?: null)
            ->setTeamBLogoUrl($teamBImage ?: null)
            ->setStartsAt(new \DateTime($startsAt ?: 'now'))
            ->setSource(SportEvent::SOURCE_ESPORTS)
            ->setRiotMatchId($matchId)
            ->setStatus(SportEvent::STATUS_DRAFT);

        $oA = (new Outcome())->setLabel('Victoire ' . $teamA)->setOdds(1.50)->setEvent($event);
        $oB = (new Outcome())->setLabel('Victoire ' . $teamB)->setOdds(1.50)->setEvent($event);

        $this->em->persist($event);
        $this->em->persist($oA);
        $this->em->persist($oB);
        $this->em->flush();

        $this->addFlash('success', "Match importé : {$event->getName()}");
        return $this->redirectToRoute('manager_event_index');
    }
}
