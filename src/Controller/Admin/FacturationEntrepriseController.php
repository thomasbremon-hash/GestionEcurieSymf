<?php

namespace App\Controller\Admin;

use App\Entity\MoisDeGestion;
use App\Entity\FacturationEntreprise;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\MoisDeGestionRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use App\Repository\FacturationEntrepriseRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/facturation')]
final class FacturationController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    // Liste toutes les factures pour tous les mois
    #[Route('/liste', name: 'app_admin_facturation')]
    public function index(FacturationEntrepriseRepository $factureRepo): Response
    {
        return $this->render('admin/facturation/liste.html.twig', [
            'factures' => $factureRepo->findAll(),
        ]);
    }

    // Générer les factures d'un mois de gestion
    #[Route('/generer/{moisId}', name: 'app_admin_facturation_generer')]
    public function generer(
        int $moisId,
        MoisDeGestionRepository $moisRepo,
        FacturationEntrepriseRepository $factureRepo
    ): Response {
        $mois = $moisRepo->find($moisId);

        if (!$mois) {
            $this->addFlash('danger', "Mois de gestion introuvable.");
            return $this->redirectToRoute('app_admin_mois_gestion');
        }

        // Récupérer toutes les entreprises présentes dans ce mois
        $entreprises = [];
        foreach ($mois->getChevalProduits() as $cp) {
            $entreprises[$cp->getCheval()->getEntreprise()->getId()] = $cp->getCheval()->getEntreprise();
        }

        foreach ($entreprises as $entreprise) {

            // Vérifier si une facture existe déjà pour ce mois
            $facture = $factureRepo->findOneBy([
                'entreprise' => $entreprise,
                'moisDeGestion' => $mois
            ]);

            if (!$facture) {
                $facture = new FacturationEntreprise();
                $facture->setEntreprise($entreprise);
                $facture->setMoisDeGestion($mois);
            }

            // Calcul du total réel des chevaux de cette entreprise
            $total = 0;
            foreach ($mois->getChevalProduits() as $cp) {
                if ($cp->getCheval()->getEntreprise() === $entreprise) {
                    $total += $cp->getTotal();
                }
            }

            $facture->setTotal($total);
            $this->em->persist($facture);
        }

        $this->em->flush();

        $this->addFlash('success', 'Les factures ont été générées avec succès.');

        return $this->redirectToRoute('app_admin_facturation_liste');
    }

    // Afficher une facture
    #[Route('/show/{id}', name: 'app_admin_facturation_show')]
    public function show(?FacturationEntreprise $facture): Response
    {
        if (!$facture) {
            $this->addFlash('danger', "Facture introuvable.");
            return $this->redirectToRoute('app_admin_facturation_liste');
        }

        return $this->render('admin/facturation/show.html.twig', [
            'facture' => $facture,
            'chevalProduits' => $facture->getMoisDeGestion()->getChevalProduits(),
        ]);
    }

    // Supprimer une facture
    #[Route('/delete/{id}', name: 'app_admin_facturation_delete')]
    public function delete(?FacturationEntreprise $facture): Response
    {
        if (!$facture) {
            $this->addFlash('danger', "Facture introuvable.");
            return $this->redirectToRoute('app_admin_facturation_liste');
        }

        $this->em->remove($facture);
        $this->em->flush();

        $this->addFlash('success', 'La facture a été supprimée.');

        return $this->redirectToRoute('app_admin_facturation_liste');
    }
}
