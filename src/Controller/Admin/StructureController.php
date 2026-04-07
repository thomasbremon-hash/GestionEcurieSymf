<?php

namespace App\Controller\Admin;

use App\Entity\Structure;
use App\Form\StructureType;
use App\Repository\StructureRepository;
use App\Security\BackofficeAccessTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/structure')]
final class StructureController extends AbstractController
{
    use BackofficeAccessTrait;

    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/liste', name: 'app_admin_structures')]
    public function index(StructureRepository $structureRepository): Response
    {
        $this->requireBackofficeAccess();

        return $this->render('admin/structure/liste.html.twig', [
            'structures' => $structureRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_structure_new')]
    #[Route('/edit/{id}', name: 'app_admin_structure_update')]
    public function form(Request $request, EntityManagerInterface $em, ?Structure $structure = null): Response
    {
        $this->requireAdminAccess();

        $isEdit = $structure !== null;
        if (!$structure) $structure = new Structure();

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
            'structureId'   => $structure->getId(),
        ]);
    }

    #[Route('/delete/{id}', name: 'app_admin_structure_delete', methods: ['POST'])]
    public function delete(?Structure $structure, Request $request): Response
    {
        $this->requireAdminAccess();

        if (!$structure) {
            $this->addFlash('danger', 'Structure introuvable.');
            return $this->redirectToRoute('app_admin_structures');
        }

        if ($this->isCsrfTokenValid('delete'.$structure->getId(), $request->request->get('_token'))) {
            $this->em->remove($structure);
            $this->em->flush();
            $this->addFlash('success', "La structure « {$structure->getNom()} » a bien été supprimée !");
        }

        return $this->redirectToRoute('app_admin_structures');
    }

    #[Route('/delete-bulk', name: 'app_admin_structure_delete_bulk', methods: ['POST'])]
    public function deleteBulk(Request $request): Response
    {
        $this->requireAdminAccess();

        if (!$this->isCsrfTokenValid('bulk-delete', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $ids = $request->request->all('ids');
        $deleted = 0;

        foreach ($ids as $id) {
            $structure = $this->em->find(\App\Entity\Structure::class, (int) $id);
            if (!$structure) { continue; }
            $this->em->remove($structure);
            $deleted++;
        }
        $this->em->flush();

        if ($deleted > 0) {
            $this->addFlash('success', "$deleted structure(s) supprimée(s).");
        }

        return $this->redirectToRoute('app_admin_structures');
    }
}
