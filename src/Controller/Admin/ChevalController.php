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

    #[Route('/delete/{id}', name: 'app_admin_cheval_delete')]
    public function delete(?Cheval $cheval): Response
    {
        $this->requireAdminAccess();

        if (!$cheval) {
            $this->addFlash('danger', 'Cheval introuvable.');
            return $this->redirectToRoute('app_admin_chevaux');
        }

        if (!$cheval->getChevalProprietaires()->isEmpty()) {
            $this->addFlash('danger', "Impossible de supprimer « {$cheval->getNom()} » car il est associé à un propriétaire.");
            return $this->redirectToRoute('app_admin_chevaux');
        }

        $this->em->remove($cheval);
        $this->em->flush();
        $this->addFlash('success', "Le cheval « {$cheval->getNom()} » a bien été supprimé !");
        return $this->redirectToRoute('app_admin_chevaux');
    }
}
