<?php

namespace App\Controller\Admin;

use App\Repository\ChevalRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\UserRepository;
use App\Repository\DeplacementRepository;
use App\Repository\FacturationUtilisateurRepository;
use App\Security\BackofficeAccessTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class AdminController extends AbstractController
{
    use BackofficeAccessTrait;

    #[Route('/admin/dashboard', name: 'app_admin')]
    public function getAll(
        EntrepriseRepository             $entrepriseRepository,
        ChevalRepository                 $chevalRepository,
        UserRepository                   $userRepository,
        DeplacementRepository            $deplacementRepository,
        FacturationUtilisateurRepository $factureRepository
    ): Response {
        $this->requireBackofficeAccess();

        $entreprises = $entrepriseRepository->findAll();
        $chevaux     = $chevalRepository->findAll();
        $users       = $userRepository->findAll();

        $deplacements = $deplacementRepository->findByCurrentMonth();
        $deplacementsParEntreprise = [];
        foreach ($deplacements as $dep) {
            $nomEntreprise = $dep->getEntreprise()?->getNom() ?? 'Sans entreprise';
            $deplacementsParEntreprise[$nomEntreprise] = ($deplacementsParEntreprise[$nomEntreprise] ?? 0) + 1;
        }

        $totalCA          = array_sum(array_map(fn($f) => $f->getTotal(), $factureRepository->findAll()));
        $facturesImpayees = $factureRepository->findBy(['statut' => 'impayee']);

        return $this->render('admin/dashboard.html.twig', [
            'entreprises'               => $entreprises,
            'chevaux'                   => $chevaux,
            'users'                     => $users,
            'deplacements'              => $deplacements,
            'deplacementsParEntreprise' => $deplacementsParEntreprise,
            'totalCA'                   => $totalCA,
            'facturesImpayees'          => $facturesImpayees,
        ]);
    }
}
