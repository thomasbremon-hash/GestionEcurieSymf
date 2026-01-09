<?php

namespace App\Controller\Admin;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/produit')]
final class ProduitController extends AbstractController
{
    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/liste', name: 'app_admin_produits')]
    public function index(ProduitRepository $produitRepository): Response
    {
        return $this->render('admin/produit/liste.html.twig', [
            'produits' => $produitRepository->findAll(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/new', name: 'app_admin_produit_new')]
    #[Route('/edit/{id}', name: 'app_admin_produit_update')]
    public function form(Request $request, EntityManagerInterface $em, ?Produit $produit): Response
    {
        $isEdit = true;
        if (!$produit) {
            $produit = new produit();
            $isEdit = false;
        }

        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($produit);
            $em->flush();

            $this->addFlash('success', $isEdit ? 'produit modifiée !' : 'produit créée !');

            return $this->redirectToRoute('app_admin_produits');
        }

        return $this->render('admin/produit/produit.form.html.twig', [
            'formProduit' => $form,
            'produitId' => $produit->getId(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/delete/{id}', name: 'app_admin_produit_delete')]
    public function adminChevauxRemove(?produit $produit)
    {
        if (!$produit) {
            $this->addFlash('danger', "produit introuvable.");
            return $this->redirectToRoute('app_admin_produits');
        }

        $this->em->remove($produit);
        $this->em->flush();

        $this->addFlash(
            'success',
            "Le produit « " . $produit->getNom() . " » a bien été supprimé !"
        );

        return $this->redirectToRoute('app_admin_produits');
    }
}
