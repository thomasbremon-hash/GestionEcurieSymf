<?php

namespace App\Controller\App;

use App\Repository\FacturationUtilisateurRepository;
use App\Service\FactureCalculator;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/comptabilite')]
final class ComptabiliteController extends AbstractController
{
    public function __construct(
        private FacturationUtilisateurRepository $facturationRepo,
        private FactureCalculator $calculator,
    ) {}

    #[Route('', name: 'app_comptabilite')]
    public function index(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $factures = $this->facturationRepo->findByUtilisateur($user);

        $totalPaye   = 0;
        $totalImpaye = 0;
        $nbPaye      = 0;
        $nbImpaye    = 0;

        // Calculer le vrai totalTTC via FactureCalculator pour chaque facture
        $totauxTtc = [];
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

        // Grouper par année pour l'affichage
        $facturesParAnnee = [];
        foreach ($factures as $facture) {
            $annee = $facture->getMoisDeGestion()?->getAnnee() ?? 'N/A';
            $facturesParAnnee[$annee][] = $facture;
        }
        krsort($facturesParAnnee);

        return $this->render('app/comptabilite/index.html.twig', [
            'factures'         => $factures,
            'facturesParAnnee' => $facturesParAnnee,
            'totauxTtc'        => $totauxTtc,
            'totalPaye'        => $totalPaye,
            'totalImpaye'      => $totalImpaye,
            'nbPaye'           => $nbPaye,
            'nbImpaye'         => $nbImpaye,
            'totalGeneral'     => $totalPaye + $totalImpaye,
        ]);
    }

    #[Route('/facture/{id}/pdf', name: 'app_comptabilite_facture_pdf')]
    public function downloadPdf(int $id): Response
    {
        /** @var \App\Entity\User $user */
        $user    = $this->getUser();
        $facture = $this->facturationRepo->find($id);

        // Sécurité : seul le propriétaire de la facture peut la télécharger
        if (!$facture || $facture->getUtilisateur() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $mois = $facture->getMoisDeGestion();
        $data = $this->calculator->calculerFactureUtilisateur($user, $mois);

        // Grouper les lignes par cheval (identique au controller admin)
        $lignesParCheval = [];
        foreach ($data['lignes'] as $ligne) {
            if ($ligne['quantite'] <= 0) {
                continue;
            }
            $cheval = $ligne['cheval'];
            if (!isset($lignesParCheval[$cheval])) {
                $lignesParCheval[$cheval] = [];
            }
            $lignesParCheval[$cheval][] = $ligne;
        }

        // Réutilisation du template PDF admin existant
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
}
