<?php

namespace App\Controller\User;

use App\Form\DepositType;
use App\Repository\TransactionRepository;
use App\Service\WalletService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user/wallet')]
class WalletController extends AbstractController
{
    #[Route('', name: 'user_wallet_index')]
    public function index(TransactionRepository $repo): Response
    {
        return $this->render('user/wallet/index.html.twig', [
            'transactions' => $repo->findBy(['user' => $this->getUser()], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/deposit', name: 'user_wallet_deposit', methods: ['GET', 'POST'])]
    public function deposit(Request $request, WalletService $wallet): Response
    {
        $form = $this->createForm(DepositType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $amount = $form->get('amount')->getData();
            $wallet->deposit($this->getUser(), (float) $amount);
            $this->addFlash('success', $amount . ' € ajoutés à votre portefeuille.');
            return $this->redirectToRoute('user_wallet_index');
        }

        return $this->render('user/wallet/deposit.html.twig', ['form' => $form]);
    }
}
