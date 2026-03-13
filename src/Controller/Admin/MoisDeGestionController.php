<?php

namespace App\Controller\Admin;

use App\Entity\ChevalProduit;
use App\Entity\Deplacement;
use App\Entity\MoisDeGestion;
use App\Form\MoisDeGestionType;
use App\Repository\ChevalRepository;
use App\Repository\MoisDeGestionRepository;
use App\Repository\ProduitRepository;
use App\Service\DeplacementToChevalProduitService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/mois-gestion')]
final class MoisDeGestionController extends AbstractController
{
    private EntityManagerInterface $em;
    private DeplacementToChevalProduitService $deplacementToChevalProduitService;

    public function __construct(EntityManagerInterface $em, DeplacementToChevalProduitService $deplacementToChevalProduitService)
    {
        $this->em = $em;
        $this->deplacementToChevalProduitService = $deplacementToChevalProduitService;
    }

    #[Route('/liste', name: 'app_admin_mois_gestion')]
    public function index(MoisDeGestionRepository $moisRepo): Response
    {
        return $this->render('admin/mois_gestion/liste.html.twig', [
            'moisGestion' => $moisRepo->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_mois_gestion_new')]
    #[Route('/edit/{id}', name: 'app_admin_mois_gestion_update')]
    public function form(
        Request $request,
        ?MoisDeGestion $moisDeGestion,
        ChevalRepository $chevalRepo,
        ProduitRepository $produitRepo,
        EntityManagerInterface $em
    ): Response {

        if (!$moisDeGestion) {
            $moisDeGestion = new MoisDeGestion();
        }

        $chevaux = $chevalRepo->findAll();

        $produits = $produitRepo->createQueryBuilder('p')
            ->where('p.nom != :nom')
            ->setParameter('nom', 'Déplacement')
            ->getQuery()
            ->getResult();

        // Génération ChevalProduit pour chaque cheval x produit (uniquement nouveau mois)
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

        // Récupérer le formulaire
        $form = $this->createForm(MoisDeGestionType::class, $moisDeGestion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mise à jour des ChevalProduit "classiques"
            foreach ($moisDeGestion->getChevalProduits() as $ligne) {
                $prixUnitaire = $ligne->getProduit()->getPrix();
                $ligne->setPrixUnitaire($prixUnitaire);
                $ligne->setTotal($prixUnitaire * ($ligne->getQuantite() ?? 0));
            }

            $this->em->persist($moisDeGestion);
            $this->em->flush();

            // Génération des ChevalProduit pour les déplacements
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



    #[Route('/show/{id}', name: 'app_admin_mois_gestion_show')]
    public function show(
        ?MoisDeGestion $moisDeGestion,
        ChevalRepository $chevalRepo,
        DeplacementToChevalProduitService $deplacementService
    ): Response {
        if (!$moisDeGestion) {
            $this->addFlash('danger', "Mois de gestion introuvable.");
            return $this->redirectToRoute('app_admin_mois_gestion');
        }

        // 🔹 Générer les ChevalProduit pour les déplacements si ce n'est pas déjà fait
        $deplacementService->genererPourMois($moisDeGestion);

        // Récupérer tous les chevaux qui ont au moins un ChevalProduit pour ce mois
        $chevaux = [];
        foreach ($moisDeGestion->getChevalProduits() as $cp) {
            if (!in_array($cp->getCheval(), $chevaux, true)) {
                $chevaux[] = $cp->getCheval();
            }
        }

        return $this->render('admin/mois_gestion/show.html.twig', [
            'moisDeGestion' => $moisDeGestion,
            'chevaux' => $chevaux,
        ]);
    }



    #[Route('/delete/{id}', name: 'app_admin_mois_gestion_delete')]
    public function delete(?MoisDeGestion $moisDeGestion)
    {
        if (!$moisDeGestion) {
            $this->addFlash('danger', "Mois de gestion introuvable.");
            return $this->redirectToRoute('app_admin_mois_gestion');
        }

        // Optionnel : vérifier s’il y a des validations ou exports, sinon supprimer directement
        $this->em->remove($moisDeGestion);
        $this->em->flush();

        $this->addFlash(
            'success',
            "Le mois de gestion a bien été supprimé !"
        );

        return $this->redirectToRoute('app_admin_mois_gestion');
    }
}
