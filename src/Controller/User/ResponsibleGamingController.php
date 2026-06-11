<?php

namespace App\Controller\User;

use App\Service\ResponsibleGamingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/responsible-gaming', name: 'user_rg_')]
#[IsGranted('ROLE_USER')]
class ResponsibleGamingController extends AbstractController
{
    public function __construct(private ResponsibleGamingService $rg) {}

    #[Route('', name: 'index')]
    public function index(): Response
    {
        $this->rg->applyPendingLimits($this->getUser());

        return $this->render('user/responsible_gaming/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/self-exclude', name: 'self_exclude', methods: ['POST'])]
    public function selfExclude(Request $request): Response
    {
        $days = (int) $request->request->get('days', 30);
        $days = max(1, min(365, $days));

        $this->rg->selfExclude($this->getUser(), $days);
        $this->addFlash('success', "Auto-exclusion activée pour {$days} jours.");

        return $this->redirectToRoute('user_rg_index');
    }

    #[Route('/bet-limit', name: 'bet_limit', methods: ['POST'])]
    public function betLimit(Request $request): Response
    {
        $daily   = $request->request->get('daily_bet_limit');
        $weekly  = $request->request->get('weekly_bet_limit');

        if ($daily !== null && $daily !== '') {
            $this->rg->updateDailyBetLimit($this->getUser(), (float) $daily);
        } elseif ($request->request->has('remove_daily_bet')) {
            $this->rg->updateDailyBetLimit($this->getUser(), null);
        }

        if ($weekly !== null && $weekly !== '') {
            $this->rg->updateWeeklyBetLimit($this->getUser(), (float) $weekly);
        } elseif ($request->request->has('remove_weekly_bet')) {
            $this->rg->updateWeeklyBetLimit($this->getUser(), null);
        }

        $this->addFlash('success', 'Limites de mise mises à jour.');
        return $this->redirectToRoute('user_rg_index');
    }

    #[Route('/deposit-limit', name: 'deposit_limit', methods: ['POST'])]
    public function depositLimit(Request $request): Response
    {
        $daily  = $request->request->get('daily_deposit_limit');
        $weekly = $request->request->get('weekly_deposit_limit');

        if ($daily !== null && $daily !== '') {
            $this->rg->updateDepositLimit($this->getUser(), 'daily', (float) $daily);
        } elseif ($request->request->has('remove_daily_deposit')) {
            $this->rg->updateDepositLimit($this->getUser(), 'daily', null);
        }

        if ($weekly !== null && $weekly !== '') {
            $this->rg->updateDepositLimit($this->getUser(), 'weekly', (float) $weekly);
        } elseif ($request->request->has('remove_weekly_deposit')) {
            $this->rg->updateDepositLimit($this->getUser(), 'weekly', null);
        }

        $this->addFlash('success', 'Limites de dépôt mises à jour.');
        return $this->redirectToRoute('user_rg_index');
    }
}
