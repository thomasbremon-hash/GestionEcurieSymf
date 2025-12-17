<?php

namespace App\Controller\Admin;

use App\Entity\Entreprise;
use App\Form\EntrepriseType;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/entreprise')]
final class EntrepriseController extends AbstractController
{

    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(name: 'app_admin_entreprises')]
    public function index(EntrepriseRepository $entrepriseRepository): Response
    {
        return $this->render('admin/entreprise/list.html.twig', [
            'entreprises' => $entrepriseRepository->findAll(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/new', name: 'app_admin_entreprise_new')]
    #[Route('/edit/{id}', name: 'app_admin_entreprise_update')]
    public function form(Request $request, EntityManagerInterface $em, ?Entreprise $entreprise): Response
    {
        $isEdit = true;
        if (!$entreprise) {
            $entreprise = new Entreprise();
            $isEdit = false;
        }

        $form = $this->createForm(EntrepriseType::class, $entreprise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($entreprise); // persist l'entreprise (propriétaire de la relation ManyToMany)
            $em->flush();

            $this->addFlash('success', $isEdit ? 'Entreprise modifiée !' : 'Entreprise créée !');

            return $this->redirectToRoute('app_admin_entreprises');
        }

        return $this->render('admin/entreprise/entreprise.form.html.twig', [
            'formEntreprise' => $form,
            'entrepriseId' => $entreprise->getId(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/delete/{id}', name: 'app_admin_entreprise_delete')]
    public function adminEntreprisesRemove(?Entreprise $entreprise)
    {
        if (!$entreprise->getUsers()->isEmpty()) {

            $this->addFlash('danger', "Impossible de supprimer l'entreprise " . $entreprise->getNom() . " car il est affilié à un utilisateur !");
            return $this->redirectToRoute('app_admin_entreprises');
        }
        $this->em->remove($entreprise);
        $this->em->flush();
        $this->addFlash('success', "L'entreprise " . $entreprise->getNom() . " a bien été supprimée !");
        return $this->redirectToRoute('app_admin_entreprises');
    }
}
