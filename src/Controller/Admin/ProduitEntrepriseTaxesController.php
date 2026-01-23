<?php

namespace App\Controller\Admin;

use App\Entity\ProduitEntrepriseTaxes;
use App\Form\ProduitEntrepriseTaxesType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProduitEntrepriseTaxesRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/produit-entreprise')]
final class ProduitEntrepriseTaxesController extends AbstractController
{
    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/liste', name: 'app_admin_produit_entreprise')]
    public function index(ProduitEntrepriseTaxesRepository $taxesRepository): Response
    {
        return $this->render('admin/produit_entreprise/liste.html.twig', [
            'produitEntreprise' => $taxesRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_produit_entreprise_new')]
    #[Route('/edit/{id}', name: 'app_admin_produit_entreprise_update')]
    public function form(Request $request, EntityManagerInterface $em, ?ProduitEntrepriseTaxes $produitEntreprise): Response
    {
        $isEdit = true;
        if (!$produitEntreprise) {
            $produitEntreprise = new ProduitEntrepriseTaxes();
            $isEdit = false;
        }

        $form = $this->createForm(ProduitEntrepriseTaxesType::class, $produitEntreprise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($produitEntreprise);
            $em->flush();

            $this->addFlash('success', $isEdit ? 'Produit | Entreprise | Taxes mis à jour !' : 'Produit | Entreprise | Taxes créée !');

            return $this->redirectToRoute('app_admin_produit_entreprise');
        }

        return $this->render('admin/produit_entreprise/produitEntreprise.form.html.twig', [
            'formProduitEntreprise' => $form,
            'produitEntrepriseId' => $produitEntreprise->getId(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/delete/{id}', name: 'app_admin_produit_entreprise_delete')]
    public function adminChevauxRemove(?ProduitEntrepriseTaxes $produitEntreprise): Response
    {
        if (!$produitEntreprise) {
            $this->addFlash('danger', "Produit | Entreprise | Taxes introuvable.");
            return $this->redirectToRoute('app_admin_produit_entreprise');
        }

        $this->em->remove($produitEntreprise);
        $this->em->flush();

        $this->addFlash(
            'success',
            "Produit | Entreprise | Taxes a bien été supprimé !"
        );

        return $this->redirectToRoute('app_admin_produit_entreprise');
    }
}
