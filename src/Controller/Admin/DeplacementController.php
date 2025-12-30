<?php

namespace App\Controller\Admin;

use App\Entity\Deplacement;
use App\Form\DeplacementType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\DeplacementRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/deplacement')]
final class DeplacementController extends AbstractController
{

     private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/liste', name: 'app_admin_deplacements')]
    public function index(DeplacementRepository $deplacementRepository): Response
    {
        return $this->render('admin/deplacement/liste.html.twig', [
            'deplacements' => $deplacementRepository->findAll(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/new', name: 'app_admin_deplacement_new')]
    #[Route('/edit/{id}', name: 'app_admin_deplacement_update')]
    public function form(Request $request, EntityManagerInterface $em, ?Deplacement $deplacement): Response
    {
        $isEdit = true;
        if (!$deplacement) {
            $deplacement = new Deplacement();
            $isEdit = false;
        }

        $form = $this->createForm(DeplacementType::class, $deplacement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($deplacement);
            $em->flush();

            $this->addFlash('success', $isEdit ? 'deplacement modifiée !' : 'deplacement créée !');

            return $this->redirectToRoute('app_admin_deplacements');
        }

        return $this->render('admin/deplacement/deplacement.form.html.twig', [
            'formDeplacement' => $form,
            'deplacementId' => $deplacement->getId(),
        ]);
    }

     #[IsGranted('ROLE_ADMIN')]
    #[Route('/delete/{id}', name: 'app_admin_deplacement_delete')]
    public function adminChevauxRemove(?Deplacement $deplacement)
    {
        if (!$deplacement) {
            $this->addFlash('danger', "deplacement introuvable.");
            return $this->redirectToRoute('app_admin_deplacements');
        }

        // Vérification si un cheval est associé
         if ($deplacement->getCheval() !== null) {
             $this->addFlash(
                'danger',
                 "Impossible de supprimer le deplacement « " . $deplacement->getNom() . " » car il est associé à un cheval."
             );
             return $this->redirectToRoute('app_admin_deplacements');
         }

         // Vérification si une structure est associé
         if ($deplacement->getStructure() !== null) {
             $this->addFlash(
                'danger',
                 "Impossible de supprimer le deplacement « " . $deplacement->getNom() . " » car il est associé à une structure."
             );
             return $this->redirectToRoute('app_admin_deplacements');
         }

        $this->em->remove($deplacement);
        $this->em->flush();

        $this->addFlash(
            'success',
            "Le déplacement « " . $deplacement->getNom() . " » a bien été supprimé !"
        );

        return $this->redirectToRoute('app_admin_deplacements');
    }
}
