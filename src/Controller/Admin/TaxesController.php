<?php

namespace App\Controller\Admin;


use App\Entity\Taxes;
use App\Form\TaxesType;
use App\Repository\TaxesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/taxes')]
final class TaxesController extends AbstractController
{

    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/liste', name: 'app_admin_taxes')]
    public function index(taxesRepository $taxesRepository): Response
    {
        return $this->render('admin/taxes/liste.html.twig', [
            'taxes' => $taxesRepository->findAll(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/new', name: 'app_admin_taxes_new')]
    #[Route('/edit/{id}', name: 'app_admin_taxes_update')]
    public function form(Request $request, EntityManagerInterface $em, ?Taxes $taxes): Response
    {
        $isEdit = true;
        if (!$taxes) {
            $taxes = new Taxes();
            $isEdit = false;
        }

        $form = $this->createForm(TaxesType::class, $taxes);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($taxes);
            $em->flush();

            $this->addFlash('success', $isEdit ? 'taxes modifiée !' : 'taxes créée !');

            return $this->redirectToRoute('app_admin_taxes');
        }

        return $this->render('admin/taxes/taxes.form.html.twig', [
            'formTaxes' => $form,
            'taxesId' => $taxes->getId(),
        ]);
    }

     #[IsGranted('ROLE_ADMIN')]
    #[Route('/delete/{id}', name: 'app_admin_taxes_delete')]
    public function adminChevauxRemove(?taxes $taxes)
    {
        if (!$taxes) {
            $this->addFlash('danger', "taxes introuvable.");
            return $this->redirectToRoute('app_admin_taxes');
        }

        $this->em->remove($taxes);
        $this->em->flush();

        $this->addFlash(
            'success',
            "La taxe « " . $taxes->getLibelle() . " » a bien été supprimé !"
        );

        return $this->redirectToRoute('app_admin_taxes');
    }


}
