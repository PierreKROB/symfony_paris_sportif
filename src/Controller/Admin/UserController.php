<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/users')]
class UserController extends AbstractController
{
    #[Route('', name: 'admin_user_index')]
    public function index(UserRepository $repo): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'users' => $repo->findAll(),
        ]);
    }

    #[Route('/{id}/toggle-suspend', name: 'admin_user_suspend', methods: ['POST'])]
    public function toggleSuspend(User $user, EntityManagerInterface $em): Response
    {
        $user->setIsSuspended(!$user->isSuspended());
        $em->flush();

        $msg = $user->isSuspended() ? 'Compte suspendu.' : 'Compte réactivé.';
        $this->addFlash('success', $msg);
        return $this->redirectToRoute('admin_user_index');
    }

    #[Route('/{id}/set-manager', name: 'admin_user_set_manager', methods: ['POST'])]
    public function setManager(User $user, EntityManagerInterface $em): Response
    {
        $roles = $user->getRoles();
        if (in_array('ROLE_MANAGER', $roles)) {
            $user->setRoles(array_filter($roles, fn($r) => $r !== 'ROLE_MANAGER'));
        } else {
            $user->setRoles(array_unique([...$roles, 'ROLE_MANAGER']));
        }
        $em->flush();

        $this->addFlash('success', 'Rôle mis à jour.');
        return $this->redirectToRoute('admin_user_index');
    }
}
