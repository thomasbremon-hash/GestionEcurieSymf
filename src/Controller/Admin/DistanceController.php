<?php

namespace App\Controller\Admin;

use App\Entity\DistanceStructure;
use App\Form\DistanceStructureType;
use App\Repository\distanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Service\DistanceCalculator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\DistanceStructureRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/distance')]
final class DistanceController extends AbstractController
{

    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/liste', name: 'app_admin_distances')]
    public function index(DistanceStructureRepository $distanceRepository): Response
    {
        return $this->render('admin/distance/liste.html.twig', [
            'distances' => $distanceRepository->findAll(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/new', name: 'app_admin_distance_new')]
    #[Route('/edit/{id}', name: 'app_admin_distance_update')]
    public function form(Request $request, EntityManagerInterface $em, ?DistanceStructure $distance, DistanceCalculator $distanceCalculator): Response
    {
        $isEdit = true;
        if (!$distance) {
            $distance = new DistanceStructure();
            $isEdit = false;
        }

        $form = $this->createForm(DistanceStructureType::class, $distance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entreprise = $distance->getEntreprise();
            $structure = $distance->getStructure();

            $distanceKm = $distanceCalculator->calculate(
                $entreprise->getAdresseComplete(),
                $structure->getAdresseComplete()
            );

            $distance->setDistance($distanceKm);

            $em->persist($distance);
            $em->flush();

            $this->addFlash('success', $isEdit ? 'Distance modifiée !' : 'Distance créée !');

            return $this->redirectToRoute('app_admin_distances');
        }

        return $this->render('admin/distance/distance.form.html.twig', [
            'formDistance' => $form,
            'distanceId' => $distance->getId(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/delete/{id}', name: 'app_admin_distance_delete')]
    public function adminDistanceRemove(?DistanceStructure $distance)
    {
        if (!$distance) {
            $this->addFlash('danger', "distance introuvable.");
            return $this->redirectToRoute('app_admin_distances');
        }

        // Vérification si un propriétaire est associé
        // if ($distance->getProprietaire() !== null) {
        //     $this->addFlash(
        //         'danger',
        //         "Impossible de supprimer le distance « " . $distance->getNom() . " » car il est associé à un propriétaire."
        //     );
        //     return $this->redirectToRoute('app_admin_chevaux');
        // }

        $this->em->remove($distance);
        $this->em->flush();

        $this->addFlash(
            'success',
            "La distance a bien été supprimé !"
        );

        return $this->redirectToRoute('app_admin_distances');
    }
}
