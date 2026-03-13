<?php

namespace App\Controller\App;

use App\Repository\ChevalRepository;
use App\Repository\FacturationUtilisateurRepository;
use App\Repository\DeplacementRepository;
use App\Service\FactureCalculator;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/client')]
final class ClientController extends AbstractController
{
    public function __construct(
        private FactureCalculator $calculator,
    ) {}

    #[Route('', name: 'app_client')]
    public function index(
        ChevalRepository                 $chevalRepository,
        FacturationUtilisateurRepository $facturationRepository,
        DeplacementRepository            $deplacementRepository,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $chevaux             = $chevalRepository->findByUserWithPourcentage($user);
        $factures            = $facturationRepository->findByUtilisateur($user);
        $facturesImpayees    = $facturationRepository->findImpayeesByUtilisateur($user);
        $deplacementsRecents = $deplacementRepository->findRecentByUser($user, 5);

        // Calcul TTC réel via FactureCalculator
        $totauxTtc   = [];
        $totalPaye   = 0;
        $totalImpaye = 0;
        $nbPaye      = 0;
        $nbImpaye    = 0;

        foreach ($factures as $facture) {
            $mois = $facture->getMoisDeGestion();
            if ($mois) {
                $data = $this->calculator->calculerFactureUtilisateur($user, $mois);
                $totauxTtc[$facture->getId()] = $data['totalTTC'];
            } else {
                $totauxTtc[$facture->getId()] = $facture->getTotal();
            }

            if ($facture->getStatut() === 'impayee') {
                $totalImpaye += $totauxTtc[$facture->getId()];
                $nbImpaye++;
            } else {
                $totalPaye += $totauxTtc[$facture->getId()];
                $nbPaye++;
            }
        }

        // Grouper par année
        $facturesParAnnee = [];
        foreach ($factures as $facture) {
            $annee = $facture->getMoisDeGestion()?->getAnnee() ?? 'N/A';
            $facturesParAnnee[$annee][] = $facture;
        }
        krsort($facturesParAnnee);

        return $this->render('app/client/index.html.twig', [
            'chevaux'             => $chevaux,
            'factures'            => $factures,
            'facturesImpayees'    => $facturesImpayees,
            'facturesParAnnee'    => $facturesParAnnee,
            'totauxTtc'           => $totauxTtc,
            'totalPaye'           => $totalPaye,
            'totalImpaye'         => $totalImpaye,
            'nbPaye'              => $nbPaye,
            'nbImpaye'            => $nbImpaye,
            'totalGeneral'        => $totalPaye + $totalImpaye,
            'deplacementsRecents' => $deplacementsRecents,
        ]);
    }

    #[Route('/facture/{id}/pdf', name: 'app_client_facture_pdf')]
    public function downloadPdf(int $id, FacturationUtilisateurRepository $facturationRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user    = $this->getUser();
        $facture = $facturationRepository->find($id);

        if (!$facture || $facture->getUtilisateur() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $mois = $facture->getMoisDeGestion();
        $data = $this->calculator->calculerFactureUtilisateur($user, $mois);

        $lignesParCheval = [];
        foreach ($data['lignes'] as $ligne) {
            if ($ligne['quantite'] <= 0) continue;
            $cheval = $ligne['cheval'];
            $lignesParCheval[$cheval][] = $ligne;
        }

        $html = $this->renderView('admin/facturation/pdf.html.twig', [
            'user'            => $user,
            'mois'            => $mois,
            'lignesParCheval' => $lignesParCheval,
            'totalHT'         => $data['totalHT'],
            'totalTVA'        => $data['totalTVA'],
            'totalTTC'        => $data['totalTTC'],
            'facture'         => $facture,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', $this->getParameter('kernel.project_dir') . '/public');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = sprintf('facture_%s_%02d_%d.pdf', $user->getNom(), $mois->getMois(), $mois->getAnnee());

        return new Response($dompdf->output(), Response::HTTP_OK, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
