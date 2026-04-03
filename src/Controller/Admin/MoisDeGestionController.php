<?php

namespace App\Controller\Admin;

use App\Entity\ChevalProduit;
use App\Entity\MoisDeGestion;
use App\Form\MoisDeGestionType;
use App\Repository\ChevalRepository;
use App\Repository\MoisDeGestionRepository;
use App\Repository\ProduitRepository;
use App\Security\BackofficeAccessTrait;
use App\Service\DeplacementToChevalProduitService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/mois-gestion')]
final class MoisDeGestionController extends AbstractController
{
    use BackofficeAccessTrait;

    public function __construct(
        private EntityManagerInterface $em,
        private DeplacementToChevalProduitService $deplacementToChevalProduitService
    ) {}

    #[Route('/liste', name: 'app_admin_mois_gestion')]
    public function index(MoisDeGestionRepository $moisRepo): Response
    {
        $this->requireBackofficeAccess();

        return $this->render('admin/mois_gestion/liste.html.twig', [
            'moisGestion' => $moisRepo->findAll(),
        ]);
    }

    #[Route('/show/{id}', name: 'app_admin_mois_gestion_show')]
    public function show(?MoisDeGestion $moisDeGestion, DeplacementToChevalProduitService $deplacementService): Response
    {
        $this->requireBackofficeAccess();

        if (!$moisDeGestion) {
            $this->addFlash('danger', 'Mois de gestion introuvable.');
            return $this->redirectToRoute('app_admin_mois_gestion');
        }

        $deplacementService->genererPourMois($moisDeGestion);

        $chevaux = [];
        foreach ($moisDeGestion->getChevalProduits() as $cp) {
            if (!in_array($cp->getCheval(), $chevaux, true)) {
                $chevaux[] = $cp->getCheval();
            }
        }

        return $this->render('admin/mois_gestion/show.html.twig', [
            'moisDeGestion' => $moisDeGestion,
            'chevaux'       => $chevaux,
        ]);
    }

    #[Route('/new', name: 'app_admin_mois_gestion_new')]
    #[Route('/edit/{id}', name: 'app_admin_mois_gestion_update')]
    public function form(Request $request, ?MoisDeGestion $moisDeGestion = null, ChevalRepository $chevalRepo, ProduitRepository $produitRepo, EntityManagerInterface $em): Response
    {
        $this->requireAdminAccess();

        if (!$moisDeGestion) $moisDeGestion = new MoisDeGestion();

        $chevaux = $chevalRepo->findAll();
        $produits = $produitRepo->createQueryBuilder('p')
            ->where('p.nom != :nom')->setParameter('nom', 'Déplacement')
            ->getQuery()->getResult();

        if ($moisDeGestion->getId() === null) {
            foreach ($chevaux as $cheval) {
                foreach ($produits as $produit) {
                    $cp = new ChevalProduit();
                    $cp->setCheval($cheval);
                    $cp->setProduit($produit);
                    $cp->setMoisDeGestion($moisDeGestion);
                    $cp->setQuantite(null);
                    $cp->setPrixUnitaire($produit->getPrix());
                    $cp->setTotal(0);
                    $em->persist($cp);
                    $moisDeGestion->addChevalProduit($cp);
                }
            }
        }

        $form = $this->createForm(MoisDeGestionType::class, $moisDeGestion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($moisDeGestion->getChevalProduits() as $ligne) {
                $prixUnitaire = $ligne->getProduit()->getPrix();
                $ligne->setPrixUnitaire($prixUnitaire);
                $ligne->setTotal($prixUnitaire * ($ligne->getQuantite() ?? 0));
            }

            $this->em->persist($moisDeGestion);
            $this->em->flush();
            $this->deplacementToChevalProduitService->genererPourMois($moisDeGestion);
            $this->addFlash('success', 'Mois de gestion créé !');
            return $this->redirectToRoute('app_admin_mois_gestion');
        }

        return $this->render('admin/mois_gestion/mois_gestion.form.html.twig', [
            'formMoisDeGestion' => $form,
            'moisDeGestionId'   => $moisDeGestion->getId(),
            'chevaux'           => $chevaux,
            'produits'          => $produits,
        ]);
    }

    #[Route('/api/dernier-mois', name: 'app_admin_mois_gestion_api_dernier', methods: ['GET'])]
    public function apiDernierMois(MoisDeGestionRepository $moisRepo): JsonResponse
    {
        $this->requireAdminAccess();

        $dernierMois = $moisRepo->createQueryBuilder('m')
            ->orderBy('m.annee', 'DESC')
            ->addOrderBy('m.mois', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$dernierMois) {
            return new JsonResponse(['quantites' => [], 'label' => null]);
        }

        $quantites = [];
        foreach ($dernierMois->getChevalProduits() as $cp) {
            if ($cp->getProduit()->getNom() === 'Déplacement') {
                continue;
            }
            $key = $cp->getCheval()->getId() . '-' . $cp->getProduit()->getId();
            $quantites[$key] = $cp->getQuantite() ?? 0;
        }

        return new JsonResponse([
            'quantites' => $quantites,
            'label' => sprintf('%02d/%d', $dernierMois->getMois(), $dernierMois->getAnnee()),
        ]);
    }

    #[Route('/delete/{id}', name: 'app_admin_mois_gestion_delete', methods: ['POST'])]
    public function delete(?MoisDeGestion $moisDeGestion, Request $request): Response
    {
        $this->requireAdminAccess();

        if (!$moisDeGestion) {
            $this->addFlash('danger', 'Mois de gestion introuvable.');
            return $this->redirectToRoute('app_admin_mois_gestion');
        }

        if ($this->isCsrfTokenValid('delete'.$moisDeGestion->getId(), $request->request->get('_token'))) {
            $this->em->remove($moisDeGestion);
            $this->em->flush();
            $this->addFlash('success', 'Le mois de gestion a bien été supprimé !');
        }

        return $this->redirectToRoute('app_admin_mois_gestion');
    }
}
