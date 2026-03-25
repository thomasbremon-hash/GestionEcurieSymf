<?php

namespace App\Controller\Admin;

use App\Entity\DistanceStructure;
use App\Form\DistanceStructureType;
use App\Repository\DistanceStructureRepository;
use App\Security\BackofficeAccessTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DistanceController extends AbstractController
{
    use BackofficeAccessTrait;

    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/liste', name: 'app_admin_distances')]
    public function index(DistanceStructureRepository $distanceRepository): Response
    {
        $this->requireBackofficeAccess();

        return $this->render('admin/distance/liste.html.twig', [
            'distances' => $distanceRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_distance_new')]
    #[Route('/edit/{id}', name: 'app_admin_distance_update')]
    public function form(Request $request, ?DistanceStructure $distance = null): Response
    {
        $this->requireAdminAccess();

        $isEdit = $distance !== null;
        if (!$distance) $distance = new DistanceStructure();

        $form = $this->createForm(DistanceStructureType::class, $distance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($distance);
            $this->em->flush();
            $this->addFlash('success', $isEdit ? 'Distance modifiée !' : 'Distance créée !');
            return $this->redirectToRoute('app_admin_distances');
        }

        return $this->render('admin/distance/distance.form.html.twig', [
            'formDistance' => $form,
            'distanceId'   => $distance->getId(),
        ]);
    }

    #[Route('/delete/{id}', name: 'app_admin_distance_delete')]
    public function delete(?DistanceStructure $distance): Response
    {
        $this->requireAdminAccess();

        if (!$distance) {
            $this->addFlash('danger', 'Distance introuvable.');
            return $this->redirectToRoute('app_admin_distances');
        }

        $this->em->remove($distance);
        $this->em->flush();
        $this->addFlash('success', 'La distance a bien été supprimée !');
        return $this->redirectToRoute('app_admin_distances');
    }
}
