<?php

namespace App\Controller\User;

use App\Entity\Outcome;
use App\Form\BetType;
use App\Repository\BetRepository;
use App\Service\BettingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user/bets')]
class BetController extends AbstractController
{
    #[Route('', name: 'user_bet_index')]
    public function index(BetRepository $repo): Response
    {
        return $this->render('user/bet/index.html.twig', [
            'bets' => $repo->findBy(['user' => $this->getUser()], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/place/{id}', name: 'user_bet_place', methods: ['GET', 'POST'])]
    public function place(Outcome $outcome, Request $request, BettingService $betting): Response
    {
        $form = $this->createForm(BetType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $amount = (float) $form->get('amount')->getData();
            $errors = $betting->placeBet($this->getUser(), $outcome, $amount);

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            } else {
                $this->addFlash('success', 'Pari enregistré ! Gain potentiel : ' . round($amount * $outcome->getOdds(), 2) . ' €');
                return $this->redirectToRoute('app_home');
            }
        }

        return $this->render('user/bet/place.html.twig', [
            'outcome' => $outcome,
            'form'    => $form,
        ]);
    }
}
