<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\BackofficeAccessTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/admin/user')]
final class UserController extends AbstractController
{
    use BackofficeAccessTrait;

    public function __construct(private EntityManagerInterface $em) {}

    #[Route(name: 'app_admin_users')]
    public function index(UserRepository $userRepository): Response
    {
        $this->requireBackofficeAccess();

        return $this->render('admin/user/list.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_user_new')]
    #[Route('/update/{id}', name: 'app_admin_user_update')]
    public function new(Request $request, ResetPasswordHelperInterface $resetPasswordHelper, MailerInterface $mailer, ?User $user = null): Response
    {
        $this->requireAdminAccess();

        $isEdit = $user !== null;
        if (!$user) $user = new User();

        $form = $this->createForm(RegistrationFormType::class, $user, ['is_edit' => $isEdit]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setIsActive(false);
            $this->em->persist($user);
            $this->em->flush();

            if (!$isEdit) {
                $resetToken = $resetPasswordHelper->generateResetToken($user);
                $resetUrl = $this->generateUrl('app_reset_password', ['token' => $resetToken->getToken()], UrlGeneratorInterface::ABSOLUTE_URL);

                $mailer->send((new TemplatedEmail())
                        ->from('thomas.bremon@logicielpourtous.com')
                        ->to($user->getEmail())
                        ->subject('Création de votre compte')
                        ->htmlTemplate('reset_password/welcome.html.twig')
                        ->context(['user' => $user, 'resetUrl' => $resetUrl])
                );
            }

            $this->addFlash('success', "L'utilisateur a été " . ($isEdit ? 'modifié' : 'enregistré') . " avec succès !");
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/user/user.form.html.twig', [
            'formUser' => $form,
            'userId'   => $user->getId(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_user_show')]
    public function show(User $user): Response
    {
        $this->requireBackofficeAccess();

        return $this->render('admin/user/show.html.twig', ['user' => $user]);
    }

    #[Route('/delete/{id}', name: 'app_admin_user_delete', methods: ['POST'])]
    public function delete(?User $user, Request $request): Response
    {
        $this->requireAdminAccess();

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $this->em->remove($user);
            $this->em->flush();
            $this->addFlash('success', "L'utilisateur {$user->getPrenom()} {$user->getNom()} a bien été supprimé !");
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/delete-bulk', name: 'app_admin_user_delete_bulk', methods: ['POST'])]
    public function deleteBulk(Request $request): Response
    {
        $this->requireAdminAccess();

        if (!$this->isCsrfTokenValid('bulk-delete', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $ids = $request->request->all('ids');
        $deleted = 0;

        foreach ($ids as $id) {
            $user = $this->em->find(User::class, (int) $id);
            if (!$user) { continue; }
            $this->em->remove($user);
            $deleted++;
        }
        $this->em->flush();

        if ($deleted > 0) {
            $this->addFlash('success', "$deleted utilisateur(s) supprimé(s).");
        }

        return $this->redirectToRoute('app_admin_users');
    }
}
