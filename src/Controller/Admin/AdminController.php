<?php

namespace App\Controller\Admin;

use App\Repository\ChevalRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class AdminController extends AbstractController
{
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/dashboard', name: 'app_admin')]
    public function getAll(EntrepriseRepository $entrepriseRepository, ChevalRepository $chevalRepository, UserRepository $userRepository): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'entreprises' => $entrepriseRepository->findAll(),
            'chevaux' => $chevalRepository->findAll(),
            'users' => $userRepository->findAll()
        ]);
    }
}
