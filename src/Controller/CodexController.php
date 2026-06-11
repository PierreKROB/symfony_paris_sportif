<?php

namespace App\Controller;

use App\Service\DataDragonService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/codex', name: 'codex_')]
class CodexController extends AbstractController
{
    public function __construct(
        private DataDragonService $dataDragon,
    ) {}

    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $champions = $this->dataDragon->getAllChampions();
        $version   = $this->dataDragon->getLatestVersion();

        $search = strtolower(trim($request->query->get('q', '')));
        if ($search !== '') {
            $champions = array_filter(
                $champions,
                fn($c) => str_contains(strtolower($c['name']), $search)
                       || str_contains(strtolower($c['id']), $search)
            );
        }

        // Sort alphabetically
        uasort($champions, fn($a, $b) => strcmp($a['name'], $b['name']));

        return $this->render('codex/index.html.twig', [
            'champions' => $champions,
            'version'   => $version,
            'search'    => $search,
        ]);
    }

    #[Route('/{championId}', name: 'show')]
    public function show(string $championId): Response
    {
        $champion = $this->dataDragon->getChampion($championId);

        if (!$champion) {
            throw $this->createNotFoundException("Champion introuvable : $championId");
        }

        $version = $this->dataDragon->getLatestVersion();

        return $this->render('codex/show.html.twig', [
            'champion' => $champion,
            'version'  => $version,
            'splashUrl' => $this->dataDragon->getSplashUrl($championId),
        ]);
    }
}
