<?php

namespace App\Controller;

use App\Entity\SportEvent;
use App\Repository\SportEventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(SportEventRepository $repo): Response
    {
        $events = $repo->findBy(
            ['status' => SportEvent::STATUS_PUBLISHED],
            ['startsAt' => 'ASC']
        );

        return $this->render('home/index.html.twig', [
            'events' => $events,
        ]);
    }
}
