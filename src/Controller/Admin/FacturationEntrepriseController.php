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


    // Formulaire + génération des factures
    #[Route('/pdf-utilisateur/{id}', name: 'app_admin_facturation_utilisateur_pdf')]
    public function pdfUtilisateur(
        FacturationUtilisateur $facture,
        FactureCalculator $calculator
    ): Response {
        if (!$facture) {
            throw $this->createNotFoundException('Facture utilisateur introuvable.');
        }

        $user = $facture->getUtilisateur();
        $mois = $facture->getMoisDeGestion();

        // 🔢 Appel EXACT à ton service
        $data = $calculator->calculerFactureUtilisateur($user, $mois);

        $html = $this->renderView('admin/facturation/pdf_user.html.twig', [
            'user'      => $user,
            'mois'      => $mois,
            'lignes'    => $data['lignes'],
            'totalHT'   => $data['totalHT'],
            'totalTVA'  => $data['totalTVA'],
            'totalTTC'  => $data['totalTTC'],
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = sprintf(
            'facture_%s_%02d_%d.pdf',
            $user->getNom(),
            $mois->getMois(),
            $mois->getAnnee()
        );

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }




    #[Route('/generer-utilisateur', name: 'app_admin_facturation_generer_utilisateur')]
    public function genererUtilisateur(Request $request, MoisDeGestionRepository $moisRepo): Response
    {
        $form = $this->createForm(FacturationGenerationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var MoisDeGestion $mois */
            $mois = $form->get('moisDeGestion')->getData();
            if (!$mois) return $this->redirectToRoute('app_admin_facturation_utilisateur');

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
            return $this->redirectToRoute('app_admin_facturation_utilisateur');
        }

        return $this->render('admin/facturation/facturation.form.html.twig', ['form' => $form]);
    }
}
