<?php

namespace App\Controller\Admin;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\User;
use App\Entity\MoisDeGestion;
use App\Service\FactureCalculator;
use App\Entity\FacturationEntreprise;
use App\Entity\FacturationUtilisateur;
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

    #[Route('/pdf/{id}', name: 'app_admin_facturation_pdf')]
    public function pdfUtilisateur(
        User $user,
        int $moisId,
        MoisDeGestionRepository $moisRepo,
        FactureCalculator $calculator
    ): Response {
        $mois = $moisRepo->find($moisId);
        if (!$mois) {
            throw $this->createNotFoundException('Mois de gestion introuvable.');
        }

        $data = $calculator->calculerFactureUtilisateur($user, $mois);

        $html = $this->renderView('admin/facturation/pdf_user.html.twig', [
            'user' => $user,
            'mois' => $mois,
            'lignes' => $data['lignes'],
            'totalHT' => $data['totalHT'],
            'totalTVA' => $data['totalTVA'],
            'totalTTC' => $data['totalTTC'],
        ]);

        $dompdf = new Dompdf(new Options(['defaultFont' => 'DejaVu Sans']));
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = sprintf(
            'facture_%s_%02d_%d.pdf',
            $user->getNom(),
            $mois->getMois(),
            $mois->getAnnee()
        );

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
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

    #[Route('/generer-utilisateur', name: 'app_admin_facturation_generer_utilisateur')]
public function genererUtilisateur(Request $request, MoisDeGestionRepository $moisRepo): Response
{
    $form = $this->createForm(FacturationGenerationType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        /** @var MoisDeGestion $mois */
        $mois = $form->get('moisDeGestion')->getData();
        if (!$mois) return $this->redirectToRoute('app_admin_facturation');

        // Récupérer tous les propriétaires pour ce mois
        $proprietaires = [];
        foreach ($mois->getChevalProduits() as $cp) {
            foreach ($cp->getCheval()->getChevalProprietaires() as $cprop) {
                $proprietaires[$cprop->getProprietaire()->getId()] = $cprop->getProprietaire();
            }
        }

        foreach ($proprietaires as $user) {
            $facture = new FacturationUtilisateur();
            $facture->setUtilisateur($user);
            $facture->setMoisDeGestion($mois);

            $total = 0;
            foreach ($mois->getChevalProduits() as $cp) {
                foreach ($cp->getCheval()->getChevalProprietaires() as $cprop) {
                    if ($cprop->getProprietaire() === $user) {
                        $total += $cp->getTotal() * ($cprop->getPourcentage() / 100);
                    }
                }
            }
            $facture->setTotal($total);

            $this->em->persist($facture);
        }

        $this->em->flush();
        $this->addFlash('success', 'Factures utilisateur générées avec succès.');
        return $this->redirectToRoute('app_admin_facturation');
    }

    return $this->render('admin/facturation/facturation.form.html.twig', ['form' => $form]);
}
}