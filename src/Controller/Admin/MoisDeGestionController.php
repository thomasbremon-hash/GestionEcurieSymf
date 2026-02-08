<?php

namespace App\Controller\Admin;

use App\Entity\MoisDeGestion;
use App\Form\MoisDeGestionType;
use App\Entity\ChevalProduit;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\MoisDeGestionRepository;
use App\Repository\ChevalRepository;
use App\Repository\ProduitRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/mois-gestion')]
final class MoisDeGestionController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
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
        ProduitRepository $produitRepo
    ): Response {

        if (!$moisDeGestion) {
            $moisDeGestion = new MoisDeGestion();
        }

        $chevaux = $chevalRepo->findAll();
        $produits = $produitRepo->findAll();

        foreach ($chevaux as $cheval) {
            foreach ($produits as $produit) {

                $existe = false;

                foreach ($moisDeGestion->getChevalProduits() as $cp) {
                    if (
                        $cp->getCheval() === $cheval &&
                        $cp->getProduit() === $produit
                    ) {
                        $existe = true;
                        break;
                    }
                }

                if (!$existe) {
                    $cp = new ChevalProduit();
                    $cp->setCheval($cheval);
                    $cp->setProduit($produit);
                    $cp->setMoisDeGestion($moisDeGestion);
                    $cp->setQuantite(0);
                    $cp->setPrixUnitaire(0);
                    $cp->setTotal(0);

                    $this->em->persist($cp); // ⭐ TRÈS IMPORTANT
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
                $ligne->setTotal($prixUnitaire * $ligne->getQuantite());
            }

            $this->em->persist($moisDeGestion);
            $this->em->flush();

            return $this->redirectToRoute('app_admin_mois_gestion');
        }


        return $this->render('admin/mois_gestion/mois_gestion.form.html.twig', [
            'formMoisDeGestion' => $form,
            'moisDeGestionId' => $moisDeGestion->getId(),
            'chevaux' => $chevalRepo->findAll(),
            'produits' => $produitRepo->findAll(),
        ]);
    }

    #[Route('/show/{id}', name: 'app_admin_mois_gestion_show')]
    public function show(?MoisDeGestion $moisDeGestion, ChevalRepository $chevalRepo): Response
    {
        if (!$moisDeGestion) {
            $this->addFlash('danger', "Mois de gestion introuvable.");
            return $this->redirectToRoute('app_admin_mois_gestion');
        }

        // Récupérer tous les chevaux (ou seulement ceux qui ont des chevalProduits si tu veux)
        $chevaux = $chevalRepo->findAll();

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
