<?php

namespace App\Controller\Admin;

use App\Entity\Cheval;
use App\Form\ChevalType;
use App\Repository\ChevalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/cheval')]
final class ChevalController extends AbstractController
{

    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/liste', name: 'app_admin_chevaux')]
    public function index(ChevalRepository $chevalRepository): Response
    {
        return $this->render('admin/cheval/list.html.twig', [
            'chevaux' => $chevalRepository->findAll(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/new', name: 'app_admin_cheval_new')]
    #[Route('/edit/{id}', name: 'app_admin_cheval_update')]
    public function form(Request $request, EntityManagerInterface $em, ?Cheval $cheval): Response
    {
        $isEdit = true;
        if (!$cheval) {
            $cheval = new Cheval();
            $isEdit = false;
        }

        $form = $this->createForm(ChevalType::class, $cheval);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($cheval);
            $em->flush();

            $this->addFlash('success', $isEdit ? 'Cheval modifiée !' : 'Cheval créée !');

            return $this->redirectToRoute('app_admin_chevaux');
        }

        return $this->render('admin/cheval/cheval.form.html.twig', [
            'formCheval' => $form,
            'chevalId' => $cheval->getId(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/delete/{id}', name: 'app_admin_cheval_delete')]
    public function adminChevauxRemove(?Cheval $cheval)
    {
        if (!$cheval) {
            $this->addFlash('danger', "Cheval introuvable.");
            return $this->redirectToRoute('app_admin_chevaux');
        }

        // Vérification si un propriétaire est associé
        if ($cheval->getProprietaire() !== null) {
            $this->addFlash(
                'danger',
                "Impossible de supprimer le cheval « " . $cheval->getNom() . " » car il est associé à un propriétaire."
            );
            return $this->redirectToRoute('app_admin_chevaux');
        }

        $this->em->remove($cheval);
        $this->em->flush();

        $this->addFlash(
            'success',
            "Le cheval « " . $cheval->getNom() . " » a bien été supprimé !"
        );

        return $this->redirectToRoute('app_admin_chevaux');
    }
}