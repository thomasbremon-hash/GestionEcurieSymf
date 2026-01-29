<?php

namespace App\Controller\Admin;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Service\FactureCalculator;
use App\Entity\FacturationUtilisateur;
use Doctrine\ORM\EntityManagerInterface;

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

        $html = $this->renderView('admin/facturation/pdf.html.twig', [
            'user' => $user,
            'mois' => $mois,
            'lignes' => $data['lignes'],
            'totalHT' => $data['totalHT'],
            'totalTVA' => $data['totalTVA'],
            'totalTTC' => $data['totalTTC'],
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



    #[Route('/delete/{id}', name: 'app_admin_facturation_delete_utilisateur')]
    public function delete(FacturationUtilisateur $facture): Response
    {
        $this->em->remove($facture);
        $this->em->flush();

        $this->addFlash('success', 'La facture utilisateur a été supprimée.');
        return $this->redirectToRoute('app_admin_facturation_utilisateur');
    }
}
