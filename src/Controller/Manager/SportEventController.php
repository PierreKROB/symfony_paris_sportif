<?php

namespace App\Controller\Manager;

use App\Entity\Outcome;
use App\Entity\SportEvent;
use App\Form\SportEventType;
use App\Repository\SportEventRepository;
use App\Service\SportEventService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/manager/events')]
class SportEventController extends AbstractController
{
    #[Route('', name: 'manager_event_index')]
    public function index(Request $request, SportEventRepository $repo): Response
    {
        $page       = max(1, (int) $request->query->get('page', 1));
        $pagination = $repo->findPaginated($page, 15);

        return $this->render('manager/event/index.html.twig', [
            'events'     => $pagination['items'],
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'manager_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $event = new SportEvent();
        $form  = $this->createForm(SportEventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion des logos uploadés
            $this->handleLogoUpload($form->get('logoAFile')->getData(), $event, 'A', $slugger);
            $this->handleLogoUpload($form->get('logoBFile')->getData(), $event, 'B', $slugger);

            // Crée automatiquement les 2 issues (Victoire teamA / Victoire teamB)
            $outcomeA = (new Outcome())->setLabel('Victoire ' . $event->getTeamA())->setEvent($event);
            $outcomeB = (new Outcome())->setLabel('Victoire ' . $event->getTeamB())->setEvent($event);
            $em->persist($event);
            $em->persist($outcomeA);
            $em->persist($outcomeB);
            $em->flush();

            $this->addFlash('success', 'Événement créé en brouillon.');
            return $this->redirectToRoute('manager_event_index');
        }

        return $this->render('manager/event/new.html.twig', ['form' => $form]);
    }

    /**
     * Déplace le fichier uploadé dans public/uploads/logos/
     * et enregistre l'URL dans l'entité.
     */
    private function handleLogoUpload(?UploadedFile $file, SportEvent $event, string $team, SluggerInterface $slugger): void
    {
        if (!$file) {
            return;
        }

        // Nom unique pour éviter les collisions : slug-du-nom + identifiant aléatoire
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName     = $slugger->slug($originalName);
        $fileName     = $safeName . '-' . uniqid() . '.' . $file->guessExtension();

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/logos';
        $file->move($uploadDir, $fileName);

        $url = '/uploads/logos/' . $fileName;

        if ($team === 'A') {
            $event->setTeamALogoUrl($url);
        } else {
            $event->setTeamBLogoUrl($url);
        }
    }

    #[Route('/{id}/publish', name: 'manager_event_publish', methods: ['POST'])]
    public function publish(SportEvent $event, SportEventService $service): Response
    {
        if ($event->getStatus() !== SportEvent::STATUS_DRAFT) {
            $this->addFlash('error', 'Seul un brouillon peut être publié.');
            return $this->redirectToRoute('manager_event_index');
        }

        $service->publish($event);
        $this->addFlash('success', 'Événement publié.');
        return $this->redirectToRoute('manager_event_index');
    }

    #[Route('/{id}/close', name: 'manager_event_close', methods: ['POST'])]
    public function close(SportEvent $event, SportEventService $service): Response
    {
        $service->closeBets($event);
        $this->addFlash('success', 'Paris fermés.');
        return $this->redirectToRoute('manager_event_index');
    }

    #[Route('/{id}/result', name: 'manager_event_result', methods: ['POST'])]
    public function setResult(SportEvent $event, Request $request, SportEventService $service): Response
    {
        $winnerId = (int) $request->request->get('winner_outcome_id');
        $service->setResult($event, $winnerId);
        $this->addFlash('success', 'Résultat enregistré, gains calculés.');
        return $this->redirectToRoute('manager_event_index');
    }

    #[Route('/{id}/cancel', name: 'manager_event_cancel', methods: ['POST'])]
    public function cancel(SportEvent $event, SportEventService $service): Response
    {
        $service->cancel($event);
        $this->addFlash('success', 'Événement annulé, paris remboursés.');
        return $this->redirectToRoute('manager_event_index');
    }

    #[Route('/{id}/delete', name: 'manager_event_delete', methods: ['POST'])]
    public function delete(SportEvent $event, SportEventService $service, EntityManagerInterface $em): Response
    {
        if (!$service->canDelete($event)) {
            $this->addFlash('error', 'Seul un brouillon peut être supprimé.');
            return $this->redirectToRoute('manager_event_index');
        }

        $em->remove($event);
        $em->flush();
        $this->addFlash('success', 'Événement supprimé.');
        return $this->redirectToRoute('manager_event_index');
    }
}
