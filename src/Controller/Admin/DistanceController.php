<?php

namespace App\Controller\Admin;

use App\Entity\DistanceStructure;
use App\Form\DistanceStructureType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\DistanceStructureRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/distance')]
final class DistanceController extends AbstractController
{

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }


    #[IsGranted('ROLE_ADMIN')]
    #[Route('/liste', name: 'app_admin_distances')]
    public function index(
        DistanceStructureRepository $distanceRepository
    ): Response {

        return $this->render('admin/distance/liste.html.twig', [

            'distances' => $distanceRepository->findAll(),

        ]);
    }



    #[IsGranted('ROLE_ADMIN')]
    #[Route('/new', name: 'app_admin_distance_new')]
    #[Route('/edit/{id}', name: 'app_admin_distance_update')]
    public function form(
        Request $request,
        ?DistanceStructure $distance
    ): Response {

        $isEdit = true;

        if (!$distance) {

            $distance = new DistanceStructure();

            $isEdit = false;
        }



        $form = $this->createForm(
            DistanceStructureType::class,
            $distance
        );

        $form->handleRequest($request);




        if ($form->isSubmitted() && $form->isValid()) {


            // IMPORTANT
            // la distance est déjà définie dans le formulaire


            $this->em->persist($distance);

            $this->em->flush();




            $this->addFlash(

                'success',

                $isEdit

                    ? 'Distance modifiée !'

                    : 'Distance créée !'

            );




            return $this->redirectToRoute(

                'app_admin_distances'

            );
        }




        return $this->render(

            'admin/distance/distance.form.html.twig',

            [

                'formDistance' => $form,

                'distanceId' => $distance->getId(),

            ]

        );
    }





    #[IsGranted('ROLE_ADMIN')]
    #[Route('/delete/{id}', name: 'app_admin_distance_delete')]
    public function adminDistanceRemove(
        ?DistanceStructure $distance
    ): Response {


        if (!$distance) {

            $this->addFlash(

                'danger',

                "Distance introuvable."

            );


            return $this->redirectToRoute(

                'app_admin_distances'

            );
        }




        $this->em->remove($distance);

        $this->em->flush();




        $this->addFlash(

            'success',

            "La distance a bien été supprimée !"

        );




        return $this->redirectToRoute(

            'app_admin_distances'

        );
    }
}
