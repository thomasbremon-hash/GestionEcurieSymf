<?php

namespace App\Controller\Admin;

use App\Entity\Course;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/cheval')]
final class CourseController extends AbstractController
{

    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('courses', name: 'app_admin_courses')]
    public function index(CourseRepository $courseRepository): Response
    {
        return $this->render('admin/cheval/courses.html.twig', [
            'courses' => $courseRepository->findAll(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/course/new', name: 'app_admin_course_new')]
    #[Route('course/edit/{id}', name: 'app_admin_course_update')]
    public function form(Request $request, EntityManagerInterface $em, ?Course $Course): Response
    {
        $isEdit = true;
        if (!$Course) {
            $Course = new Course();
            $isEdit = false;
        }

        $form = $this->createForm(CourseType::class, $Course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($Course);
            $em->flush();

            $this->addFlash('success', $isEdit ? 'Course modifiée !' : 'Course créée !');

            return $this->redirectToRoute('app_admin_courses');
        }

        return $this->render('admin/cheval/course.form.html.twig', [
            'formCourse' => $form,
            'courseId' => $Course->getId(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/course/delete/{id}', name: 'app_admin_course_delete')]
    public function adminCoursesRemove(?Course $course)
    {
        if (!$course) {
            $this->addFlash('danger', "Course introuvable.");
            return $this->redirectToRoute('app_admin_courses');
        }

        // Vérification si un propriétaire est associé
        if ($course->getParticipations() !== null) {
            $this->addFlash(
                'danger',
                "Impossible de supprimer la course « " . $course->getNom() . " » car elle est associée à une participation."
            );
            return $this->redirectToRoute('app_admin_courses');
        }

        $this->em->remove($course);
        $this->em->flush();

        $this->addFlash(
            'success',
            "La course « " . $course->getNom() . " » a bien été supprimée !"
        );

        return $this->redirectToRoute('app_admin_courses');
    }
}
