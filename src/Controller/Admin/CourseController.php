<?php

namespace App\Controller\Admin;

use App\Entity\Course;
use App\Entity\Participation;
use App\Form\CourseType;
use App\Form\ParticipationType;
use App\Repository\CourseRepository;
use App\Repository\ParticipationRepository;
use App\Security\BackofficeAccessTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/cheval')]
final class CourseController extends AbstractController
{
    use BackofficeAccessTrait;

    public function __construct(private EntityManagerInterface $em) {}

    #[Route('courses', name: 'app_admin_courses')]
    public function index(CourseRepository $courseRepository): Response
    {
        $this->requireBackofficeAccess();

        return $this->render('admin/cheval/courses.html.twig', [
            'courses' => $courseRepository->findAll(),
        ]);
    }

    #[Route('/course/new', name: 'app_admin_course_new')]
    #[Route('course/edit/{id}', name: 'app_admin_course_update')]
    public function form(Request $request, EntityManagerInterface $em, ?Course $course = null): Response
    {
        $this->requireAdminAccess();

        $isEdit = $course !== null;
        if (!$course) $course = new Course();

        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($course);
            $em->flush();
            $this->addFlash('success', $isEdit ? 'Course modifiée !' : 'Course créée !');
            return $this->redirectToRoute('app_admin_courses');
        }

        return $this->render('admin/cheval/course.form.html.twig', [
            'formCourse' => $form,
            'courseId'   => $course->getId(),
        ]);
    }

    #[Route('/course/delete/{id}', name: 'app_admin_course_delete', methods: ['POST'])]
    public function deleteCourse(?Course $course, Request $request): Response
    {
        $this->requireAdminAccess();

        if (!$course) {
            $this->addFlash('danger', 'Course introuvable.');
            return $this->redirectToRoute('app_admin_courses');
        }

        if ($this->isCsrfTokenValid('delete'.$course->getId(), $request->request->get('_token'))) {
            if ($course->getParticipations() !== null) {
                $this->addFlash('danger', "Impossible de supprimer « {$course->getNom()} » car elle est associée à une participation.");
                return $this->redirectToRoute('app_admin_courses');
            }

            $this->em->remove($course);
            $this->em->flush();
            $this->addFlash('success', "La course « {$course->getNom()} » a bien été supprimée !");
        }

        return $this->redirectToRoute('app_admin_courses');
    }

    #[Route('participations', name: 'app_admin_participations')]
    public function participations(ParticipationRepository $participationRepository): Response
    {
        $this->requireBackofficeAccess();

        return $this->render('admin/cheval/participations.html.twig', [
            'participations' => $participationRepository->findAll(),
        ]);
    }

    #[Route('/participation/new', name: 'app_admin_participation_new')]
    #[Route('/participation/update/{id}', name: 'app_admin_participation_update')]
    public function formParticipation(Request $request, ?Participation $participation, ParticipationRepository $participationRepository): Response
    {
        $this->requireAdminAccess();

        $isEdit = $participation !== null;
        if (!$participation) $participation = new Participation();

        $form = $this->createForm(ParticipationType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cheval = $participation->getCheval();
            $course = $participation->getCourse();

            $existing = $participationRepository->findOneBy(['cheval' => $cheval, 'course' => $course]);
            if ($existing && (!$isEdit || $existing->getId() !== $participation->getId())) {
                $this->addFlash('danger', "Le cheval {$cheval->getNom()} est déjà inscrit à la course {$course->getNom()} !");
                return $this->redirectToRoute('app_admin_participations');
            }

            $this->em->persist($participation);
            $this->em->flush();
            $this->addFlash('success', $isEdit ? 'Participation modifiée !' : 'Participation ajoutée !');
            return $this->redirectToRoute('app_admin_participations');
        }

        return $this->render('admin/cheval/participation.form.html.twig', [
            'formParticipation' => $form,
            'participationId'   => $participation->getId(),
        ]);
    }

    #[Route('/participation/delete/{id}', name: 'app_admin_participation_delete', methods: ['POST'])]
    public function deleteParticipation(Participation $participation, Request $request): Response
    {
        $this->requireAdminAccess();

        if ($this->isCsrfTokenValid('delete'.$participation->getId(), $request->request->get('_token'))) {
            $this->em->remove($participation);
            $this->em->flush();
            $this->addFlash('danger', 'La participation a été supprimée !');
        }

        return $this->redirectToRoute('app_admin_participations');
    }
}
