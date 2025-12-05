<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/admin/user')]
final class UserController extends AbstractController
{

    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route(name: 'app_admin_users')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/user/list.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_user_new')]
    #[Route('/update/{id}', name: 'app_admin_user_update')]
    public function new(Request $request, ?User $user): Response
    {
        if (!$user) {
            $user = new User();
        }

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $txt = 'modifié';
            if (!$user->getId()) {
                $txt = 'enregistrée';
                $user->setCreatedAt(new \DateTimeImmutable());
            }

            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash('success', "L'utilisateur' a été $txt !");

            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/user/form.html.twig', [
            'formUser' => $user,
            'userId' => $user->getId(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_user_show')]
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }


    #[Route('/delete/{id}', name: 'app_admin_user_delete')]
    public function adminCategoryRemove(?User $user, Request $request, UserRepository $userRepository)
    {
        if (!$user->getEntreprise()->isEmpty()) {

            $this->addFlash('danger', "Impossible de supprimer l'utilisateur ' " . $user->getNom() . " car il est affilié à une entreprise !");
            return $this->redirectToRoute('app_admin_user');
        }
        $this->em->remove($user);
        $this->em->flush();
        $this->addFlash('success', "L'utilisateur ' " . $user->getNom() . " a bien été supprimée !");
        return $this->redirectToRoute('app_admin_user');
    }
}
