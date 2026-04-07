<?php

namespace App\Controller\Admin;

use App\Entity\Entreprise;
use App\Form\EntrepriseType;
use App\Repository\EntrepriseRepository;
use App\Security\BackofficeAccessTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/entreprise')]
final class EntrepriseController extends AbstractController
{
    use BackofficeAccessTrait;

    public function __construct(private EntityManagerInterface $em) {}

    #[Route(name: 'app_admin_entreprises')]
    public function index(EntrepriseRepository $entrepriseRepository): Response
    {
        $this->requireBackofficeAccess();

        return $this->render('admin/entreprise/list.html.twig', [
            'entreprises' => $entrepriseRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_entreprise_new')]
    #[Route('/edit/{id}', name: 'app_admin_entreprise_update')]
    public function form(Request $request, EntityManagerInterface $em, ?Entreprise $entreprise = null): Response
    {
        $this->requireAdminAccess();

        $isEdit = $entreprise !== null;
        if (!$entreprise) $entreprise = new Entreprise();

        $form = $this->createForm(EntrepriseType::class, $entreprise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($entreprise);
            $em->flush();
            $this->addFlash('success', $isEdit ? 'Entreprise modifiée !' : 'Entreprise créée !');
            return $this->redirectToRoute('app_admin_entreprises');
        }

        return $this->render('admin/entreprise/entreprise.form.html.twig', [
            'formEntreprise' => $form,
            'entrepriseId'   => $entreprise->getId(),
        ]);
    }

    #[Route('/show/{id}', name: 'app_admin_entreprise_show', methods: ['GET'])]
    public function show(Entreprise $entreprise): Response
    {
        $this->requireBackofficeAccess();

        return $this->render('admin/entreprise/show.html.twig', [
            'entreprise' => $entreprise,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_admin_entreprise_delete', methods: ['POST'])]
    public function delete(?Entreprise $entreprise, Request $request): Response
    {
        $this->requireAdminAccess();

        if ($this->isCsrfTokenValid('delete'.$entreprise->getId(), $request->request->get('_token'))) {
            if (!$entreprise->getUsers()->isEmpty()) {
                $this->addFlash('danger', "Impossible de supprimer « {$entreprise->getNom()} » car elle est affiliée à un utilisateur !");
                return $this->redirectToRoute('app_admin_entreprises');
            }

            $this->em->remove($entreprise);
            $this->em->flush();
            $this->addFlash('success', "L'entreprise « {$entreprise->getNom()} » a bien été supprimée !");
        }

        return $this->redirectToRoute('app_admin_entreprises');
    }

    #[Route('/delete-bulk', name: 'app_admin_entreprise_delete_bulk', methods: ['POST'])]
    public function deleteBulk(Request $request): Response
    {
        $this->requireAdminAccess();

        if (!$this->isCsrfTokenValid('bulk-delete', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $ids = $request->request->all('ids');
        $deleted = 0;

        foreach ($ids as $id) {
            $entreprise = $this->em->find(Entreprise::class, (int) $id);
            if (!$entreprise) { continue; }
            $this->em->remove($entreprise);
            $deleted++;
        }
        $this->em->flush();

        if ($deleted > 0) {
            $this->addFlash('success', "$deleted entreprise(s) supprimée(s).");
        }

        return $this->redirectToRoute('app_admin_entreprises');
    }
}
