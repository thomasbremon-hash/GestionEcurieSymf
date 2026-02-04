<?php

namespace App\Controller\Admin;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Service\FactureCalculator;
use App\Entity\FacturationUtilisateur;
use App\Form\FacturationGenerationType;
use App\Entity\MoisDeGestion;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\MoisDeGestionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\FacturationUtilisateurRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/facturation')]
class FacturationUtilisateurController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/liste', name: 'app_admin_facturation_utilisateur')]
    public function index(FacturationUtilisateurRepository $repo): Response
    {
        $factures = $repo->findAll();

        // Tri par année/mois DESC, puis par utilisateur nom ASC
        usort($factures, function ($a, $b) {
            $aMois = $a->getMoisDeGestion();
            $bMois = $b->getMoisDeGestion();

            // Trier par année descendante
            $cmp = $bMois->getAnnee() <=> $aMois->getAnnee();
            if ($cmp !== 0) return $cmp;

            // Puis par mois descendante
            $cmp = $bMois->getMois() <=> $aMois->getMois();
            if ($cmp !== 0) return $cmp;

            // Enfin par nom d'utilisateur ascendant
            return strcmp($a->getUtilisateur()->getNom(), $b->getUtilisateur()->getNom());
        });

        return $this->render('admin/facturation/liste.html.twig', [
            'factures' => $factures,
        ]);
    }

    #[Route('/pdf/{id}', name: 'app_admin_facturation_pdf_utilisateur')]
    public function pdf(FacturationUtilisateur $facture, FactureCalculator $calculator): Response
    {
        $user = $facture->getUtilisateur();
        $mois = $facture->getMoisDeGestion();

        $data = $calculator->calculerFactureUtilisateur($user, $mois);

        $lignesParCheval = [];

        foreach ($data['lignes'] as $ligne) {

            // ❌ On ignore les produits non consommés
            if ($ligne['quantite'] <= 0) {
                continue;
            }

            $cheval = $ligne['cheval'];

            if (!isset($lignesParCheval[$cheval])) {
                $lignesParCheval[$cheval] = [];
            }

            $lignesParCheval[$cheval][] = $ligne;
        }


        $lignes = array_filter($data['lignes'], function ($ligne) {
            return isset($ligne['quantite']) && $ligne['quantite'] > 0;
        });


        $html = $this->renderView('admin/facturation/pdf.html.twig', [
            'user'      => $user,
            'mois'      => $mois,
            'lignesParCheval' => $lignesParCheval,
            'totalHT'   => $data['totalHT'],
            'totalTVA'  => $data['totalTVA'],
            'totalTTC'  => $data['totalTTC'],
            'facture'   => $facture,
        ]);


        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = sprintf('facture_%s_%02d_%d.pdf', $user->getNom(), $mois->getMois(), $mois->getAnnee());

        return new Response($dompdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
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



    #[Route('/delete/{id}', name: 'app_admin_facturation_delete_utilisateur')]
    public function delete(FacturationUtilisateur $facture): Response
    {
        $this->em->remove($facture);
        $this->em->flush();

        $this->addFlash('success', 'La facture utilisateur a été supprimée.');
        return $this->redirectToRoute('app_admin_facturation_utilisateur');
    }
}
