<?php

namespace App\Controller\Admin;

use App\Entity\Structure;
use App\Form\StructureType;
use App\Repository\StructureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/structure')]
final class StructureController extends AbstractController
{

    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/liste', name: 'app_admin_structures')]
    public function index(StructureRepository $structureRepository): Response
    {
        return $this->render('admin/structure/liste.html.twig', [
            'structures' => $structureRepository->findAll(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/new', name: 'app_admin_structure_new')]
    #[Route('/edit/{id}', name: 'app_admin_structure_update')]
    public function form(Request $request, EntityManagerInterface $em, ?Structure $structure): Response
    {
        $isEdit = true;
        if (!$structure) {
            $structure = new Structure();
            $isEdit = false;
        }

        $form = $this->createForm(StructureType::class, $structure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($structure);
            $em->flush();

            $this->addFlash('success', $isEdit ? 'Structure modifiée !' : 'Structure créée !');

            return $this->redirectToRoute('app_admin_structures');
        }

        return $this->render('admin/structure/structure.form.html.twig', [
            'formStructure' => $form,
            'structureId' => $structure->getId(),
        ]);
    }

     #[IsGranted('ROLE_ADMIN')]
    #[Route('/delete/{id}', name: 'app_admin_structure_delete')]
    public function adminChevauxRemove(?Structure $structure)
    {
        if (!$structure) {
            $this->addFlash('danger', "Structure introuvable.");
            return $this->redirectToRoute('app_admin_structures');
        }

        // Vérification si un propriétaire est associé
        // if ($structure->getProprietaire() !== null) {
        //     $this->addFlash(
        //         'danger',
        //         "Impossible de supprimer le structure « " . $structure->getNom() . " » car il est associé à un propriétaire."
        //     );
        //     return $this->redirectToRoute('app_admin_chevaux');
        // }

        $this->em->remove($structure);
        $this->em->flush();

        $this->addFlash(
            'success',
            "La strcucture « " . $structure->getNom() . " » a bien été supprimé !"
        );

        return $this->redirectToRoute('app_admin_structures');
    }


}
