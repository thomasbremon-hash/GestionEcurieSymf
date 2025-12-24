<?php

namespace App\Controller\Admin;

use App\Entity\Course;
use App\Form\CourseType;
use App\Entity\Participation;
use App\Form\ParticipationType;
use App\Repository\CourseRepository;
use App\Repository\ParticipationRepository;
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

    #[Route('participations', name: 'app_admin_participations')]
    public function participations(ParticipationRepository $participationRepository): Response
    {
        return $this->render('admin/cheval/participations.html.twig', [
            'participations' => $participationRepository->findAll(),
        ]);
    }


    #[Route('/participation/new', name: 'app_admin_participation_new')]
    #[Route('/participation/update/{id}', name: 'app_admin_participation_update')]
    public function formParticipation(Request $request, ?Participation $participation, ParticipationRepository $participationRepository): Response
    {
        $isEdit = $participation !== null;

        if (!$participation) {
            $participation = new Participation();
        }

        $form = $this->createForm(ParticipationType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $cheval = $participation->getCheval();
            $course = $participation->getCourse();

            // Vérification si le cheval est déjà inscrit à la même course
            $existingParticipation = $participationRepository->findOneBy([
                'cheval' => $cheval,
                'course' => $course,
            ]);

            if ($existingParticipation && (!$isEdit || $existingParticipation->getId() !== $participation->getId())) {
                $this->addFlash('danger', sprintf(
                    "Le cheval " . $cheval->getNom() . " est déjà inscrit à la course " . $course->getNom() . " !",
                    $cheval->getNom(),
                    $course->getNom()
                ));
                return $this->redirectToRoute('app_admin_participations');
            }

            $this->em->persist($participation);
            $this->em->flush();

            $txt = $isEdit ? "modifiée" : "ajoutée";
            $this->addFlash('success', "La participation a été $txt avec succès !");
            return $this->redirectToRoute('app_admin_participations');
        }

        return $this->render('admin/cheval/participation.form.html.twig', [
            'formParticipation' => $form,
            'participationId' => $participation->getId(),
        ]);
    }


    #[Route('/participation/delete/{id}', name: 'app_admin_participation_delete')]
    public function delete(Participation $participation): Response
    {
        $this->em->remove($participation);
        $this->em->flush();

        $this->addFlash('danger', 'La participation a été supprimée !');

        return $this->redirectToRoute('app_admin_participations');
    }
}
