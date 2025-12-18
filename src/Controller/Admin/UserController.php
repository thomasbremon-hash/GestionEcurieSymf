<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Cheval;
use App\Form\UserType;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/user')]
final class UserController extends AbstractController
{

    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(name: 'app_admin_users')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/user/list.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/new', name: 'app_admin_user_new')]
    #[Route('/update/{id}', name: 'app_admin_user_update')]
    public function new(Request $request, UserPasswordHasherInterface $passwordHasher, ?User $user): Response
    {
        $isEdit = $user !== null;

        if (!$user) {
            $user = new User();
        }

        $form = $this->createForm(RegistrationFormType::class, $user, [
            'is_edit' => $isEdit
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // On hash seulement si le champ existe (donc en création)
            if (!$isEdit) {
                $plainPassword = $form->get('password')->getData();
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $this->em->persist($user);
            $this->em->flush();

            $txt = $isEdit ? 'modifié' : 'enregistré';
            $this->addFlash('success', "L'utilisateur a été $txt avec succès !");

            return $this->redirectToRoute('app_admin_users');
        }


        return $this->render('admin/user/user.form.html.twig', [
            'formUser' => $form,
            'userId' => $user->getId()
        ]);
    }


    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'app_admin_user_show')]
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }


    #[IsGranted('ROLE_ADMIN')]
    #[Route('/delete/{id}', name: 'app_admin_user_delete')]
    public function adminCategoryRemove(?User $user)
    {
        if (!$user->getEntreprise()->isEmpty()) {

            $this->addFlash('danger', "Impossible de supprimer l'utilisateur " . $user->getPrenom() . " " . $user->getPrenom() . " car il est affilié à une entreprise !");
            return $this->redirectToRoute('app_admin_users');
        }
        $this->em->remove($user);
        $this->em->flush();
        $this->addFlash('success', "L'utilisateur " . $user->getPrenom() . " " . $user->getPrenom() . " a bien été supprimée !");
        return $this->redirectToRoute('app_admin_users');
    }
}
