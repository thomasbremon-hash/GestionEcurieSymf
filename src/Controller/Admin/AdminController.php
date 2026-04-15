<?php

namespace App\Controller\Admin;

use App\Repository\ChevalRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\UserRepository;
use App\Repository\DeplacementRepository;
use App\Repository\FacturationUtilisateurRepository;
use App\Security\BackofficeAccessTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
        $montantImpaye    = array_sum(array_map(fn($f) => $f->getTotal(), $facturesImpayees));

        $dernieresFactures     = $factureRepository->findBy([], ['createdAt' => 'DESC'], 5);
        $derniersDeplacements  = $deplacementRepository->findBy([], ['date' => 'DESC'], 5);

        return $this->render('admin/dashboard.html.twig', [
            'entreprises'               => $entreprises,
            'chevaux'                   => $chevaux,
            'users'                     => $users,
            'deplacements'              => $deplacements,
            'deplacementsParEntreprise' => $deplacementsParEntreprise,
            'totalCA'                   => $totalCA,
            'facturesImpayees'          => $facturesImpayees,
            'montantImpaye'             => $montantImpaye,
            'dernieresFactures'         => $dernieresFactures,
            'derniersDeplacements'      => $derniersDeplacements,
        ]);
    }

    #[Route('/admin/search', name: 'app_admin_search')]
    public function search(
        Request                          $request,
        ChevalRepository                 $chevalRepository,
        UserRepository                   $userRepository,
        EntrepriseRepository             $entrepriseRepository,
        FacturationUtilisateurRepository $factureRepository
    ): JsonResponse {
        $this->requireBackofficeAccess();

        $q = trim($request->query->get('q', ''));
        if (mb_strlen($q) < 2) {
            return $this->json(['results' => []]);
        }

        $results = [];

        foreach ($chevalRepository->searchByNom($q, 5) as $cheval) {
            $results[] = [
                'type'  => 'cheval',
                'icon'  => 'mdi-chess-knight',
                'label' => $cheval->getNom(),
                'sub'   => 'Cheval',
                'url'   => $this->generateUrl('app_admin_cheval_show', ['id' => $cheval->getId()]),
            ];
        }

        foreach ($userRepository->searchByNomPrenomEmail($q, 5) as $user) {
            $results[] = [
                'type'  => 'user',
                'icon'  => 'mdi-account-circle',
                'label' => $user->getNom() . ' ' . $user->getPrenom(),
                'sub'   => $user->getEmail(),
                'url'   => $this->generateUrl('app_admin_user_show', ['id' => $user->getId()]),
            ];
        }

        foreach ($entrepriseRepository->searchByNom($q, 3) as $entreprise) {
            $results[] = [
                'type'  => 'entreprise',
                'icon'  => 'mdi-domain',
                'label' => $entreprise->getNom(),
                'sub'   => 'Entreprise',
                'url'   => $this->generateUrl('app_admin_entreprise_show', ['id' => $entreprise->getId()]),
            ];
        }

        foreach ($factureRepository->searchByNumFacture($q, 5) as $facture) {
            $results[] = [
                'type'  => 'facture',
                'icon'  => 'mdi-file-document-outline',
                'label' => $facture->getNumFacture(),
                'sub'   => $facture->getUtilisateur()?->getNom() . ' ' . $facture->getUtilisateur()?->getPrenom(),
                'url'   => $this->generateUrl('app_admin_facturation_voir_utilisateur', ['id' => $facture->getId()]),
            ];
        }

        return $this->json(['results' => $results]);
    }
}
