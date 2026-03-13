<?php

namespace App\Controller\Admin;

use App\Repository\ChevalRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\UserRepository;
use App\Repository\DeplacementRepository;
use App\Repository\FacturationUtilisateurRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class AdminController extends AbstractController
{
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/dashboard', name: 'app_admin')]
    public function getAll(
        EntrepriseRepository $entrepriseRepository,
        ChevalRepository $chevalRepository,
        UserRepository $userRepository,
        DeplacementRepository $deplacementRepository,
        FacturationUtilisateurRepository $factureRepository
    ): Response {
        $entreprises = $entrepriseRepository->findAll();
        $chevaux = $chevalRepository->findAll();
        $users = $userRepository->findAll();

        // Déplacements du mois
        $deplacements = $deplacementRepository->findByCurrentMonth();
        $deplacementsParEntreprise = [];
        foreach ($deplacements as $dep) {
            $nomEntreprise = $dep->getEntreprise()?->getNom() ?? 'Sans entreprise';
            $deplacementsParEntreprise[$nomEntreprise] = ($deplacementsParEntreprise[$nomEntreprise] ?? 0) + 1;
        }

        // Chiffre d'affaires total
        $totalCA = array_sum(array_map(fn($facture) => $facture->getTotal(), $factureRepository->findAll()));

        // Factures impayées
        $facturesImpayees = $factureRepository->findBy(['statut' => 'impayee']);

        return $this->render('admin/dashboard.html.twig', [
            'entreprises' => $entreprises,
            'chevaux' => $chevaux,
            'users' => $users,
            'deplacements' => $deplacements,
            'deplacementsParEntreprise' => $deplacementsParEntreprise,
            'totalCA' => $totalCA,
            'facturesImpayees' => $facturesImpayees
        ]);
    }
}
