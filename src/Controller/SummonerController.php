<?php

namespace App\Controller;

use App\Entity\Outcome;
use App\Entity\SportEvent;
use App\Repository\SportEventRepository;
use App\Service\DataDragonService;
use App\Service\RiotApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/summoner', name: 'summoner_')]
class SummonerController extends AbstractController
{
    public function __construct(
        private RiotApiService       $riot,
        private DataDragonService    $dataDragon,
        private EntityManagerInterface $em,
        private SportEventRepository $eventRepo,
    ) {}

    #[Route('', name: 'search')]
    public function search(Request $request): Response
    {
        $name    = trim($request->query->get('name', ''));
        $summoner = null;
        $error    = null;

        if ($name !== '') {
            $summoner = $this->riot->resolveSummoner($name);
            if (!$summoner) {
                if (str_contains($name, '#')) {
                    $error = "Joueur « {$name} » introuvable. Le compte Riot existe peut-être mais sans summoner LoL sur EUW — il faut avoir joué au moins une partie.";
                } else {
                    $error = "Joueur « {$name} » introuvable sur EUW. Utilise le format Pseudo#TAG (ex: Caps#EUW).";
                }
            }
        }

        return $this->render('summoner/search.html.twig', [
            'name'     => $name,
            'summoner' => $summoner,
            'error'    => $error,
        ]);
    }

    #[Route('/profile/{encodedName}', name: 'profile')]
    public function profile(string $encodedName): Response
    {
        $name     = urldecode($encodedName);
        $summoner = $this->riot->resolveSummoner($name);

        if (!$summoner || empty($summoner['puuid'])) {
            throw $this->createNotFoundException("Summoner introuvable : $name");
        }

        $ranked   = $this->riot->getRankedStats($summoner['puuid']);
        $matchIds = $this->riot->getMatchIds($summoner['puuid'], 5);
        $matches  = array_filter(array_map(fn($id) => $this->riot->getMatch($id), $matchIds));
        $version  = $this->dataDragon->getLatestVersion();
        $iconUrl  = $this->dataDragon->getProfileIconUrl($summoner['profileIconId']);

        // Cherche si un pari en cours existe déjà pour ce puuid
        $existingBet = $this->eventRepo->findOneBy([
            'riotPuuid' => $summoner['puuid'],
            'status'    => SportEvent::STATUS_PUBLISHED,
        ]);

        // Résume les matchs pour l'affichage
        $matchSummaries = [];
        foreach ($matches as $match) {
            $info = $match['info'];
            $me   = null;
            foreach ($info['participants'] as $p) {
                if ($p['puuid'] === $summoner['puuid']) {
                    $me = $p;
                    break;
                }
            }
            if (!$me) continue;
            $matchSummaries[] = [
                'champion'  => $me['championName'],
                'iconUrl'   => $this->dataDragon->getChampionIconUrl($me['championName']),
                'kills'     => $me['kills'],
                'deaths'    => $me['deaths'],
                'assists'   => $me['assists'],
                'win'       => $me['win'],
                'duration'  => gmdate('G\hi\m\i\n', $info['gameDuration']),
                'matchId'   => $match['metadata']['matchId'],
            ];
        }

        return $this->render('summoner/profile.html.twig', [
            'summoner'       => $summoner,
            'ranked'         => $ranked,
            'matchSummaries' => $matchSummaries,
            'iconUrl'        => $iconUrl,
            'version'        => $version,
            'existingBet'    => $existingBet,
            'encodedName'    => $encodedName,
        ]);
    }

    #[Route('/profile/{encodedName}/create-bet', name: 'create_bet', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createBet(string $encodedName): Response
    {
        $name     = urldecode($encodedName);
        $summoner = $this->riot->resolveSummoner($name);

        if (!$summoner) {
            throw $this->createNotFoundException();
        }

        // Évite les doublons
        $existing = $this->eventRepo->findOneBy([
            'riotPuuid' => $summoner['puuid'],
            'status'    => SportEvent::STATUS_PUBLISHED,
        ]);
        if ($existing) {
            $this->addFlash('warning', 'Un pari existe déjà pour ce joueur.');
            return $this->redirectToRoute('summoner_profile', ['encodedName' => $encodedName]);
        }

        $ranked    = $this->riot->getRankedStats($summoner['puuid']);
        $soloQueue = null;
        foreach ($ranked as $queue) {
            if ($queue['queueType'] === 'RANKED_SOLO_5x5') {
                $soloQueue = $queue;
                break;
            }
        }
        $rankLabel = $soloQueue
            ? $soloQueue['tier'] . ' ' . $soloQueue['rank']
            : 'Unranked';

        $event = (new SportEvent())
            ->setName("{$summoner['name']} — Prochaine ranked")
            ->setTournament("Ranked Solo/Duo · {$rankLabel}")
            ->setTeamA($summoner['name'])
            ->setTeamB('Adversaires')
            ->setSource(SportEvent::SOURCE_RIOT)
            ->setRiotPuuid($summoner['puuid'])
            ->setSummonerName($summoner['name'])
            ->setStartsAt(new \DateTime('+30 minutes'))
            ->setStatus(SportEvent::STATUS_PUBLISHED);

        $oWin  = (new Outcome())->setLabel('Victoire')->setOdds(1.50)->setEvent($event);
        $oLose = (new Outcome())->setLabel('Défaite')->setOdds(1.50)->setEvent($event);

        $this->em->persist($event);
        $this->em->persist($oWin);
        $this->em->persist($oLose);
        $this->em->flush();

        $this->addFlash('success', "Pari créé pour {$summoner['name']} — il apparaît maintenant sur la home !");
        return $this->redirectToRoute('summoner_profile', ['encodedName' => $encodedName]);
    }
}
