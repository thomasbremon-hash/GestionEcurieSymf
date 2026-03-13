<?php

namespace App\Controller\Admin;

use App\Entity\FacturationUtilisateur;
use App\Entity\MoisDeGestion;
use App\Form\FacturationGenerationType;
use App\Repository\FacturationUtilisateurRepository;
use App\Repository\MoisDeGestionRepository;
use App\Service\DeplacementToChevalProduitService;
use App\Service\FactureCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
    public function index(FacturationUtilisateurRepository $repo, FactureCalculator $calculator): Response
    {
        $factures = $repo->findAll();

        // 🔽 Tri : Entreprise → Année DESC → Mois DESC → Utilisateur
        usort($factures, function ($a, $b) {
            // Entreprise facturante
            $cmp = strcmp(
                $a->getEntreprise()->getNom(),
                $b->getEntreprise()->getNom()
            );
            if ($cmp !== 0) {
                return $cmp;
            }

            // Année DESC
            $cmp = $b->getMoisDeGestion()->getAnnee() <=> $a->getMoisDeGestion()->getAnnee();
            if ($cmp !== 0) {
                return $cmp;
            }

            // Mois DESC
            $cmp = $b->getMoisDeGestion()->getMois() <=> $a->getMoisDeGestion()->getMois();
            if ($cmp !== 0) {
                return $cmp;
            }

            // Nom utilisateur ASC
            return strcmp(
                $a->getUtilisateur()->getNom(),
                $b->getUtilisateur()->getNom()
            );
        });

        $totauxTtc = [];

        foreach ($factures as $facture) {
            $data = $calculator->calculerFactureUtilisateur(
                $facture->getUtilisateur(),
                $facture->getMoisDeGestion()
            );

            $totauxTtc[$facture->getId()] = $data['totalTTC'];
        }

        return $this->render('admin/facturation/liste.html.twig', [
            'factures' => $factures,
            'totauxTtc' => $totauxTtc,
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
            if ($ligne['quantite'] <= 0) {
                continue;
            }
            $cheval = $ligne['cheval'];
            if (!isset($lignesParCheval[$cheval])) {
                $lignesParCheval[$cheval] = [];
            }
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

        // ✅ autoriser Dompdf à accéder au dossier public
        $options->set('chroot', $this->getParameter('kernel.project_dir') . '/public');

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

    #[Route('/payer/{id}', name: 'app_admin_facturation_payer')]
    public function payer(FacturationUtilisateur $facture): Response
    {
        $facture->setStatut('payee');

        $this->em->flush();

        $this->addFlash('success', 'Facture marquée comme payée.');

        return $this->redirectToRoute('app_admin_facturation_utilisateur');
    }




    #[Route('/generer-utilisateur', name: 'app_admin_facturation_generer_utilisateur')]
    public function genererUtilisateur(
        Request $request,
        MoisDeGestionRepository $moisRepo,
        DeplacementToChevalProduitService $deplacementService,
        FacturationUtilisateurRepository $factureRepo
    ): Response {
        $form = $this->createForm(FacturationGenerationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var MoisDeGestion $mois */
            $mois = $form->get('moisDeGestion')->getData();
            $deplacementService->genererPourMois($mois);
            $entreprise = $form->get('entreprise')->getData();
            if (!$mois) return $this->redirectToRoute('app_admin_facturation_utilisateur');

            // Récupérer tous les propriétaires pour ce mois
            $proprietaires = [];
            foreach ($mois->getChevalProduits() as $cp) {
                foreach ($cp->getCheval()->getChevalProprietaires() as $cprop) {
                    $proprietaires[$cprop->getProprietaire()->getId()] = $cprop->getProprietaire();
                }
            }

            // 🔹 Récupérer le dernier numéro global
            $dernierFacture = $factureRepo->createQueryBuilder('f')
                ->select('f.numFacture')
                ->orderBy('f.id', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            $dernierNumero = 0;
            if ($dernierFacture && isset($dernierFacture['numFacture'])) {
                // Extraire le compteur XXXX du format MM-YYYY-XXXX
                preg_match('/\d{4}$/', $dernierFacture['numFacture'], $matches);
                if (!empty($matches[0])) {
                    $dernierNumero = (int)$matches[0];
                }
            }

            $compteur = $dernierNumero;

            foreach ($proprietaires as $user) {
                $compteur++;

                $facture = new FacturationUtilisateur();
                $facture->setUtilisateur($user);
                $facture->setMoisDeGestion($mois);
                $facture->setEntreprise($entreprise);

                // Calcul du total
                $total = 0;
                foreach ($mois->getChevalProduits() as $cp) {
                    foreach ($cp->getCheval()->getChevalProprietaires() as $cprop) {
                        if ($cprop->getProprietaire() === $user) {
                            $total += $cp->getTotal() * ($cprop->getPourcentage() / 100);
                        }
                    }
                }
                $facture->setTotal($total);

                // 🔹 Numéro de facture global auto-incrémenté
                $numFacture = sprintf(
                    '%02d-%d-%04d',
                    $mois->getMois(),
                    $mois->getAnnee(),
                    $compteur
                );
                $facture->setNumFacture($numFacture);

                // Statut par défaut
                $facture->setStatut('impayee');

                $this->em->persist($facture);
            }

            $this->em->flush();
            $this->addFlash('success', 'Factures utilisateur générées avec succès.');
            return $this->redirectToRoute('app_admin_facturation_utilisateur');
        }

        return $this->render('admin/facturation/facturation.form.html.twig', ['form' => $form]);
    }

    #[Route('/envoyer-mail/{id}', name: 'app_admin_facturation_envoyer_mail')]
    public function envoyerMail(
        FacturationUtilisateur $facture,
        MailerInterface $mailer,
        FactureCalculator $calculator
    ): Response {
        if ($facture->isMailEnvoye()) {
            $this->addFlash('danger', 'Le mail a déjà été envoyé.');
            return $this->redirectToRoute('app_admin_facturation_utilisateur');
        }

        $user = $facture->getUtilisateur();
        $mois = $facture->getMoisDeGestion();

        $data = $calculator->calculerFactureUtilisateur($user, $mois);

        $lignesParCheval = [];
        foreach ($data['lignes'] as $ligne) {
            if ($ligne['quantite'] <= 0) continue;
            $cheval = $ligne['cheval'];
            $lignesParCheval[$cheval][] = $ligne;
        }

        $html = $this->renderView('admin/facturation/pdf.html.twig', [
            'user' => $user,
            'mois' => $mois,
            'lignesParCheval' => $lignesParCheval,
            'totalHT' => $data['totalHT'],
            'totalTVA' => $data['totalTVA'],
            'totalTTC' => $data['totalTTC'],
            'facture' => $facture,
        ]);

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', $this->getParameter('kernel.project_dir') . '/public');

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfContent = $dompdf->output();

        try {
            $email = (new \Symfony\Component\Mime\Email())
                ->from('facturation@monentreprise.com')
                ->to($user->getEmail())
                ->subject('Votre facture ' . $facture->getNumFacture())
                ->html($this->renderView('admin/facturation/mail.html.twig', [
                    'facture' => $facture,
                    'total' => $facture->getTotal(),
                    'facturePdfUrl' => $this->generateUrl('app_admin_facturation_pdf_utilisateur', ['id' => $facture->getId()])
                ]))
                ->attach($pdfContent, $facture->getNumFacture() . '.pdf', 'application/pdf');

            $mailer->send($email);

            $facture->setMailEnvoye(true);
            $this->em->flush();

            $this->addFlash('success', 'Mail envoyé avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de l’envoi du mail : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_facturation_utilisateur');
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
