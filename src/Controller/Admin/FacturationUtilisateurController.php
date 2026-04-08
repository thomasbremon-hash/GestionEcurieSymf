<?php

namespace App\Controller\Admin;

use App\Entity\FacturationUtilisateur;
use App\Entity\MoisDeGestion;
use App\Form\FacturationGenerationType;
use App\Form\FacturationUtilisateurType;
use App\Repository\FacturationUtilisateurRepository;
use App\Repository\MoisDeGestionRepository;
use App\Security\BackofficeAccessTrait;
use App\Service\DeplacementToChevalProduitService;
use App\Service\FactureCalculator;
use App\Service\FacturXService;
use App\Service\InvoiceNumberService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/facturation')]
class FacturationUtilisateurController extends AbstractController
{
    use BackofficeAccessTrait;

    public function __construct(
        private EntityManagerInterface $em,
        private InvoiceNumberService $invoiceNumberService,
    ) {}

    #[Route('/liste', name: 'app_admin_facturation_utilisateur')]
    public function index(FacturationUtilisateurRepository $repo, FactureCalculator $calculator): Response
    {
        $this->requireBackofficeAccess();

        $factures = $repo->findAll();

        usort($factures, function ($a, $b) {
            $cmp = strcmp($a->getEntreprise()->getNom(), $b->getEntreprise()->getNom());
            if ($cmp !== 0) return $cmp;
            $cmp = $b->getMoisDeGestion()->getAnnee() <=> $a->getMoisDeGestion()->getAnnee();
            if ($cmp !== 0) return $cmp;
            $cmp = $b->getMoisDeGestion()->getMois() <=> $a->getMoisDeGestion()->getMois();
            if ($cmp !== 0) return $cmp;
            return strcmp($a->getUtilisateur()->getNom(), $b->getUtilisateur()->getNom());
        });

        $totauxTtc = [];
        foreach ($factures as $facture) {
            if ($facture->getType() === 'avoir') {
                $totauxTtc[$facture->getId()] = $facture->getTotal();
                continue;
            }
            $data = $calculator->calculerFactureUtilisateur($facture->getUtilisateur(), $facture->getMoisDeGestion());
            $totauxTtc[$facture->getId()] = $data['totalTTC'];
        }

        return $this->render('admin/facturation/liste.html.twig', [
            'factures'  => $factures,
            'totauxTtc' => $totauxTtc,
        ]);
    }

    #[Route('/pdf/{id}', name: 'app_admin_facturation_pdf_utilisateur')]
    public function pdf(FacturationUtilisateur $facture, FactureCalculator $calculator): Response
    {
        return $this->generatePdf($facture, $calculator, 'attachment');
    }

    #[Route('/voir/{id}', name: 'app_admin_facturation_voir_utilisateur')]
    public function voir(FacturationUtilisateur $facture, FactureCalculator $calculator): Response
    {
        return $this->generatePdf($facture, $calculator, 'inline');
    }

    #[Route('/facturx/{id}', name: 'app_admin_facturation_facturx')]
    public function facturx(FacturationUtilisateur $facture, FacturXService $facturXService): Response
    {
        $this->requireBackofficeAccess();

        if ($facture->getType() !== 'facture') {
            $this->addFlash('danger', 'Le format Factur-X n\'est disponible que pour les factures (pas les avoirs).');
            return $this->redirectToRoute('app_admin_facturation_utilisateur');
        }

        $xml      = $facturXService->generateXml($facture);
        $filename = sprintf('facturx_%s.xml', $facture->getNumFacture());

        return new Response($xml, Response::HTTP_OK, [
            'Content-Type'        => 'application/xml; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    private function generatePdf(FacturationUtilisateur $facture, FactureCalculator $calculator, string $disposition): Response
    {
        $this->requireBackofficeAccess();

        $user = $facture->getUtilisateur();
        $mois = $facture->getMoisDeGestion();
        $data = $calculator->calculerFactureUtilisateur($user, $mois);

        $lignesParCheval = [];
        foreach ($data['lignes'] as $ligne) {
            if ($ligne['quantite'] <= 0) continue;
            $lignesParCheval[$ligne['cheval']][] = $ligne;
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
            'Content-Disposition' => sprintf('%s; filename="%s"', $disposition, $filename),
        ]);
    }

    #[Route('/payer/{id}', name: 'app_admin_facturation_payer')]
    public function payer(FacturationUtilisateur $facture): Response
    {
        $this->requireAdminAccess();

        $facture->setStatut('payee');
        $facture->setDatePaiement(new \DateTimeImmutable());
        $this->em->flush();
        $this->addFlash('success', 'Facture marquée comme payée.');
        return $this->redirectToRoute('app_admin_facturation_utilisateur');
    }

    #[Route('/edit/{id}', name: 'app_admin_facturation_edit', methods: ['GET', 'POST'])]
    public function edit(FacturationUtilisateur $facture, Request $request, FactureCalculator $calculator): Response
    {
        $this->requireAdminAccess();

        if ($facture->isMailEnvoye() || $facture->getType() !== 'facture') {
            $this->addFlash('danger', 'Cette facture ne peut pas être modifiée directement.');
            return $this->redirectToRoute('app_admin_facturation_utilisateur');
        }

        $form = $this->createForm(FacturationUtilisateurType::class, $facture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $calculator->calculerFactureUtilisateur($facture->getUtilisateur(), $facture->getMoisDeGestion());
            $facture->setTotal($data['totalTTC']);
            $facture->setDateEmission(new \DateTimeImmutable());
            $this->em->flush();

            $this->addFlash('success', 'Facture modifiée avec succès.');
            return $this->redirectToRoute('app_admin_facturation_utilisateur');
        }

        return $this->render('admin/facturation/facturation.edit.html.twig', [
            'form'    => $form,
            'facture' => $facture,
        ]);
    }

    #[Route('/corriger/{id}', name: 'app_admin_facturation_corriger', methods: ['GET', 'POST'])]
    public function corriger(
        FacturationUtilisateur $facture,
        Request $request,
        FactureCalculator $calculator,
    ): Response {
        $this->requireAdminAccess();

        if (!$facture->isMailEnvoye() || $facture->getType() !== 'facture' || $facture->getStatut() === 'annulee') {
            $this->addFlash('danger', 'Cette facture ne peut pas être corrigée via un avoir.');
            return $this->redirectToRoute('app_admin_facturation_utilisateur');
        }

        $form = $this->createForm(FacturationUtilisateurType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $now = new \DateTimeImmutable();

            // 1. Créer l'avoir d'annulation
            $avoir = new FacturationUtilisateur();
            $avoir->setType('avoir');
            $avoir->setTotal(-$facture->getTotal());
            $avoir->setUtilisateur($facture->getUtilisateur());
            $avoir->setMoisDeGestion($facture->getMoisDeGestion());
            $avoir->setEntreprise($facture->getEntreprise());
            $avoir->setNumFacture('AV-' . $facture->getNumFacture());
            $avoir->setStatut('impayee');
            $avoir->setDateEmission($now);
            $avoir->setCreatedAt($now);
            $avoir->setMailEnvoye(false);
            $avoir->setFactureOrigine($facture);
            $this->em->persist($avoir);

            // 2. Marquer la facture originale comme annulée
            $facture->setStatut('annulee');

            // 3. Créer la nouvelle facture corrigée
            $nouveauNumero = $this->invoiceNumberService->reserveNumbers(1);

            $nouvelleFacture = new FacturationUtilisateur();
            $nouvelleFacture->setType('facture');
            $nouvelleFacture->setUtilisateur($form->get('utilisateur')->getData());
            $nouvelleFacture->setMoisDeGestion($form->get('moisDeGestion')->getData());
            $nouvelleFacture->setEntreprise($form->get('entreprise')->getData());

            $moisCorrige = $form->get('moisDeGestion')->getData();
            $data = $calculator->calculerFactureUtilisateur(
                $form->get('utilisateur')->getData(),
                $moisCorrige
            );
            $nouvelleFacture->setTotal($data['totalTTC']);
            $nouvelleFacture->setNumFacture(sprintf('%d-%02d-%04d', $moisCorrige->getAnnee(), $moisCorrige->getMois(), $nouveauNumero));
            $nouvelleFacture->setStatut('impayee');
            $nouvelleFacture->setDateEmission($now);
            $nouvelleFacture->setCreatedAt($now);
            $nouvelleFacture->setMailEnvoye(false);
            $this->em->persist($nouvelleFacture);

            $this->em->flush();

            $this->addFlash('success', sprintf(
                'Avoir %s créé. Facture originale annulée. Nouvelle facture %s créée.',
                $avoir->getNumFacture(),
                $nouvelleFacture->getNumFacture()
            ));
            return $this->redirectToRoute('app_admin_facturation_utilisateur');
        }

        return $this->render('admin/facturation/facturation.corriger.html.twig', [
            'form'    => $form,
            'facture' => $facture,
        ]);
    }

    #[Route('/generer-utilisateur', name: 'app_admin_facturation_generer_utilisateur')]
    public function genererUtilisateur(Request $request, MoisDeGestionRepository $moisRepo, DeplacementToChevalProduitService $deplacementService): Response
    {
        $this->requireAdminAccess();

        $form = $this->createForm(FacturationGenerationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $mois       = $form->get('moisDeGestion')->getData();
            $entreprise = $form->get('entreprise')->getData();

            $deplacementService->genererPourMois($mois);
            if (!$mois) return $this->redirectToRoute('app_admin_facturation_utilisateur');

            $proprietaires = [];
            foreach ($mois->getChevalProduits() as $cp) {
                foreach ($cp->getCheval()->getChevalProprietaires() as $cprop) {
                    $proprietaires[$cprop->getProprietaire()->getId()] = $cprop->getProprietaire();
                }
            }

            $count    = count($proprietaires);
            $compteur = $count > 0 ? $this->invoiceNumberService->reserveNumbers($count) - 1 : 0;
            foreach ($proprietaires as $user) {
                $compteur++;
                $facture = new FacturationUtilisateur();
                $facture->setUtilisateur($user)->setMoisDeGestion($mois)->setEntreprise($entreprise);

                $total = 0;
                foreach ($mois->getChevalProduits() as $cp) {
                    foreach ($cp->getCheval()->getChevalProprietaires() as $cprop) {
                        if ($cprop->getProprietaire() === $user) {
                            $total += $cp->getTotal() * ($cprop->getPourcentage() / 100);
                        }
                    }
                }

                $now = new \DateTimeImmutable();
                $facture->setTotal($total)
                    ->setNumFacture(sprintf('%d-%02d-%04d', $mois->getAnnee(), $mois->getMois(), $compteur))
                    ->setStatut('impayee')
                    ->setDateEmission($now)
                    ->setCreatedAt($now);

                $this->em->persist($facture);
            }

            $this->em->flush();
            $this->addFlash('success', 'Factures utilisateur générées avec succès.');
            return $this->redirectToRoute('app_admin_facturation_utilisateur');
        }

        return $this->render('admin/facturation/facturation.form.html.twig', ['form' => $form]);
    }

    #[Route('/envoyer-mail/{id}', name: 'app_admin_facturation_envoyer_mail')]
    public function envoyerMail(FacturationUtilisateur $facture, MailerInterface $mailer, FactureCalculator $calculator): Response
    {
        $this->requireAdminAccess();

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
            $lignesParCheval[$ligne['cheval']][] = $ligne;
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

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', $this->getParameter('kernel.project_dir') . '/public');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        try {
            $mailer->send((new \Symfony\Component\Mime\Email())
                    ->from('facturation@monentreprise.com')
                    ->to($user->getEmail())
                    ->subject('Votre facture ' . $facture->getNumFacture())
                    ->html($this->renderView('admin/facturation/mail.html.twig', [
                        'facture' => $facture,
                        'total' => $facture->getTotal(),
                        'facturePdfUrl' => $this->generateUrl('app_admin_facturation_pdf_utilisateur', ['id' => $facture->getId()]),
                    ]))
                    ->attach($dompdf->output(), $facture->getNumFacture() . '.pdf', 'application/pdf')
            );

            $facture->setMailEnvoye(true);
            $this->em->flush();
            $this->addFlash('success', 'Mail envoyé avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de l\'envoi du mail : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_facturation_utilisateur');
    }

    #[Route('/delete/{id}', name: 'app_admin_facturation_delete_utilisateur', methods: ['POST'])]
    public function delete(FacturationUtilisateur $facture, Request $request): Response
    {
        $this->requireAdminAccess();

        $this->addFlash('danger', 'La suppression de factures est interdite (obligation légale de conservation 10 ans — Article L123-22 du Code de Commerce).');

        return $this->redirectToRoute('app_admin_facturation_utilisateur');
    }
}
