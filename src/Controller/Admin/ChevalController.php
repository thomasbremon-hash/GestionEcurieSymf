<?php

namespace App\Controller\Admin;

use App\Entity\Cheval;
use App\Entity\ChevalProprietaire;
use App\Form\ChevalType;
use App\Repository\ChevalRepository;
use App\Security\BackofficeAccessTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/cheval')]
final class ChevalController extends AbstractController
{
    use BackofficeAccessTrait;

    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/liste', name: 'app_admin_chevaux')]
    public function index(ChevalRepository $chevalRepository): Response
    {
        $this->requireBackofficeAccess();

        return $this->render('admin/cheval/list.html.twig', [
            'chevaux' => $chevalRepository->findAll(),
        ]);
    }

    #[Route('/show/{id}', name: 'app_admin_cheval_show')]
    public function show(Cheval $cheval): Response
    {
        $this->requireBackofficeAccess();

        return $this->render('admin/cheval/show.html.twig', [
            'cheval' => $cheval,
        ]);
    }

    #[Route('/new', name: 'app_admin_cheval_new')]
    #[Route('/edit/{id}', name: 'app_admin_cheval_update')]
    public function form(Request $request, EntityManagerInterface $em, ?Cheval $cheval = null): Response
    {
        $this->requireAdminAccess();

        $isEdit = $cheval !== null;
        if (!$cheval) {
            $cheval = new Cheval();
        }

        if ($cheval->getChevalProprietaires()->isEmpty()) {
            $cheval->addChevalProprietaire(new ChevalProprietaire());
        }

        $form = $this->createForm(ChevalType::class, $cheval);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($cheval);
            $em->flush();
            $this->addFlash('success', $isEdit ? 'Cheval modifié !' : 'Cheval créé !');
            return $this->redirectToRoute('app_admin_chevaux');
        }

        return $this->render('admin/cheval/cheval.form.html.twig', [
            'formCheval' => $form,
            'chevalId'   => $cheval->getId(),
        ]);
    }

    #[Route('/delete/{id}', name: 'app_admin_cheval_delete', methods: ['POST'])]
    public function delete(?Cheval $cheval, Request $request): Response
    {
        $this->requireAdminAccess();

        if (!$cheval) {
            $this->addFlash('danger', 'Cheval introuvable.');
            return $this->redirectToRoute('app_admin_chevaux');
        }

        if ($this->isCsrfTokenValid('delete'.$cheval->getId(), $request->request->get('_token'))) {
            if (!$cheval->getChevalProprietaires()->isEmpty()) {
                $this->addFlash('danger', "Impossible de supprimer « {$cheval->getNom()} » car il est associé à un propriétaire.");
                return $this->redirectToRoute('app_admin_chevaux');
            }

            $this->em->remove($cheval);
            $this->em->flush();
            $this->addFlash('success', "Le cheval « {$cheval->getNom()} » a bien été supprimé !");
        }

        return $this->redirectToRoute('app_admin_chevaux');
    }

    #[Route('/delete-bulk', name: 'app_admin_cheval_delete_bulk', methods: ['POST'])]
    public function deleteBulk(Request $request): Response
    {
        $this->requireAdminAccess();

        if (!$this->isCsrfTokenValid('bulk-delete', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $ids = $request->request->all('ids');
        $deleted = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            $cheval = $this->em->find(Cheval::class, (int) $id);
            if (!$cheval) { continue; }
            if (!$cheval->getChevalProprietaires()->isEmpty()) { $skipped++; continue; }
            $this->em->remove($cheval);
            $deleted++;
        }
        $this->em->flush();

        if ($deleted > 0) {
            $this->addFlash('success', "$deleted cheval(x) supprimé(s).");
        }
        if ($skipped > 0) {
            $this->addFlash('danger', "$skipped cheval(x) non supprimé(s) car associé(s) à un propriétaire.");
        }

        return $this->redirectToRoute('app_admin_chevaux');
    }
}
