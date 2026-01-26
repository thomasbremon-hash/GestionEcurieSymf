<?php

namespace App\Controller\Admin;

use App\Entity\MoisDeGestion;
use App\Entity\FacturationEntreprise;
use App\Form\FacturationGenerationType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\MoisDeGestionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\FacturationEntrepriseRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/facturation')]
final class FacturationEntrepriseController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/liste', name: 'app_admin_facturation')]
    public function index(FacturationEntrepriseRepository $factureRepo): Response
    {
        $factures = $factureRepo->findAll(); // récupère toutes les factures

        // Trier par année puis mois en PHP
        usort($factures, function ($a, $b) {
            $moisA = $a->getMoisDeGestion();
            $moisB = $b->getMoisDeGestion();

            // comparer l'année
            if ($moisA->getAnnee() !== $moisB->getAnnee()) {
                return $moisB->getAnnee() <=> $moisA->getAnnee(); // DESC
            }

            // comparer le mois
            return $moisB->getMois() <=> $moisA->getMois(); // DESC
        });

        return $this->render('admin/facturation/liste.html.twig', [
            'factures' => $factures,
        ]);
    }



    // Formulaire + génération des factures
    #[Route('/generer', name: 'app_admin_facturation_generer')]
    public function genererForm(
        Request $request,
        FacturationEntrepriseRepository $factureRepo,
        MoisDeGestionRepository $moisRepo
    ): Response {
        $form = $this->createForm(FacturationGenerationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var MoisDeGestion $mois */
            $mois = $form->get('moisDeGestion')->getData();

            if (!$mois) {
                $this->addFlash('danger', "Mois de gestion introuvable.");
                return $this->redirectToRoute('app_admin_facturation');
            }

            // Vérifier si des factures existent déjà pour ce mois
            $existing = $factureRepo->findBy(['moisDeGestion' => $mois]);
            if (!empty($existing)) {
                $this->addFlash('danger', 'Les factures pour ce mois sont déjà générées.');
                return $this->redirectToRoute('app_admin_facturation');
            }

            // Récupérer toutes les entreprises présentes dans ce mois
            $entreprises = [];
            foreach ($mois->getChevalProduits() as $cp) {
                $entreprises[$cp->getCheval()->getEntreprise()->getId()] = $cp->getCheval()->getEntreprise();
            }

            if (empty($entreprises)) {
                $this->addFlash('danger', 'Aucune entreprise trouvée pour ce mois.');
                return $this->redirectToRoute('app_admin_facturation');
            }

            // Générer une facture pour chaque entreprise
            foreach ($entreprises as $entreprise) {
                $facture = new FacturationEntreprise();
                $facture->setEntreprise($entreprise);
                $facture->setMoisDeGestion($mois);

                // Calculer le total
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
            return $this->redirectToRoute('app_admin_facturation');
        }

        return $this->render('admin/facturation/facturation.form.html.twig', [
            'form' => $form,
        ]);
    }

    // Afficher une facture
    #[Route('/show/{id}', name: 'app_admin_facturation_show')]
    public function show(?FacturationEntreprise $facture): Response
    {
        if (!$facture) {
            $this->addFlash('danger', "Facture introuvable.");
            return $this->redirectToRoute('app_admin_facturation');
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
            return $this->redirectToRoute('app_admin_facturation');
        }

        $this->em->remove($facture);
        $this->em->flush();

        $this->addFlash('success', 'La facture a été supprimée.');

        return $this->redirectToRoute('app_admin_facturation');
    }
}
