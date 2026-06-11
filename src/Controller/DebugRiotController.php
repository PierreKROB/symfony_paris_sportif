<?php

namespace App\Controller;

use App\Service\RiotApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/debug/riot', name: 'debug_riot_')]
class DebugRiotController extends AbstractController
{
    public function __construct(private RiotApiService $riot) {}

    #[Route('/summoner', name: 'summoner')]
    public function summoner(Request $request): JsonResponse
    {
        $name = $request->query->get('name', '');
        if (!$name) {
            return $this->json(['error' => 'Passe ?name=PseudoJoueur#TAG']);
        }

        $result = $this->riot->resolveSummoner($name);

        return $this->json([
            'input'    => $name,
            'result'   => $result,
            'has_id'   => isset($result['id']),
            'has_puuid'=> isset($result['puuid']),
        ]);
    }
}
