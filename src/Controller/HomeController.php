<?php

namespace App\Controller;

use App\Entity\SportEvent;
use App\Repository\SportEventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, SportEventRepository $repo): Response
    {
        $page       = max(1, (int) $request->query->get('page', 1));
        $pagination = $repo->findPaginated($page, 9, ['status' => SportEvent::STATUS_PUBLISHED], ['startsAt' => 'ASC']);

        return $this->render('home/index.html.twig', [
            'events'     => $pagination['items'],
            'pagination' => $pagination,
        ]);
    }
}
