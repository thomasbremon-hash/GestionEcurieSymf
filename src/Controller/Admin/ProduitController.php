<?php

namespace App\Controller\Admin;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use App\Security\BackofficeAccessTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/produit')]
final class ProduitController extends AbstractController
{
    use BackofficeAccessTrait;

    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/liste', name: 'app_admin_produits')]
    public function index(ProduitRepository $produitRepository): Response
    {
        $this->requireBackofficeAccess();

        return $this->render('admin/produit/liste.html.twig', [
            'produits' => $produitRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_produit_new')]
    #[Route('/edit/{id}', name: 'app_admin_produit_update')]
    public function form(Request $request, EntityManagerInterface $em, ?Produit $produit = null): Response
    {
        $this->requireAdminAccess();

        $isEdit = $produit !== null;
        if (!$produit) $produit = new Produit();

        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($produit);
            $em->flush();
            $this->addFlash('success', $isEdit ? 'Produit modifié !' : 'Produit créé !');
            return $this->redirectToRoute('app_admin_produits');
        }

        return $this->render('admin/produit/produit.form.html.twig', [
            'formProduit' => $form,
            'produitId'   => $produit->getId(),
        ]);
    }

    #[Route('/delete/{id}', name: 'app_admin_produit_delete', methods: ['POST'])]
    public function delete(?Produit $produit, Request $request): Response
    {
        $this->requireAdminAccess();

        if (!$produit) {
            $this->addFlash('danger', 'Produit introuvable.');
            return $this->redirectToRoute('app_admin_produits');
        }

        if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->request->get('_token'))) {
            $this->em->remove($produit);
            $this->em->flush();
            $this->addFlash('success', "Le produit « {$produit->getNom()} » a bien été supprimé !");
        }

        return $this->redirectToRoute('app_admin_produits');
    }
}
