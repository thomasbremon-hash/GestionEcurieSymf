<?php

namespace App\Controller\Admin;

use App\Entity\Deplacement;
use App\Form\DeplacementType;
use App\Repository\DeplacementRepository;
use App\Repository\DistanceStructureRepository;
use App\Security\BackofficeAccessTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/deplacement')]
final class DeplacementController extends AbstractController
{
    use BackofficeAccessTrait;

    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/liste', name: 'app_admin_deplacements')]
    public function index(DeplacementRepository $deplacementRepository): Response
    {
        $this->requireBackofficeAccess();

        $deplacements = $deplacementRepository->createQueryBuilder('d')
            ->leftJoin('d.entreprise', 'e')
            ->orderBy('e.nom', 'ASC')
            ->addOrderBy('d.date', 'DESC')
            ->getQuery()->getResult();

        return $this->render('admin/deplacement/liste.html.twig', [
            'deplacements' => $deplacements,
        ]);
    }

    #[Route('/new', name: 'app_admin_deplacement_new')]
    #[Route('/edit/{id}', name: 'app_admin_deplacement_update')]
    public function form(Request $request, EntityManagerInterface $em, ?Deplacement $deplacement = null, DistanceStructureRepository $distanceRepo): Response
    {
        $this->requireAdminAccess();

        $isEdit = $deplacement !== null;
        if (!$deplacement) $deplacement = new Deplacement();

        $form = $this->createForm(DeplacementType::class, $deplacement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $distance = $distanceRepo->findDistance($deplacement->getEntreprise(), $deplacement->getStructure());

            if ($distance === null) {
                $this->addFlash('danger', 'Distance non définie pour cette entreprise et structure.');
                return $this->redirectToRoute('app_admin_deplacement_new');
            }

            $deplacement->setDistance($distance);
            $em->persist($deplacement);
            $em->flush();
            $this->addFlash('success', $isEdit ? 'Déplacement modifié !' : 'Déplacement créé !');
            return $this->redirectToRoute('app_admin_deplacements');
        }

        return $this->render('admin/deplacement/deplacement.form.html.twig', [
            'formDeplacement' => $form,
            'deplacementId'   => $deplacement->getId(),
        ]);
    }

    #[Route('/delete/{id}', name: 'app_admin_deplacement_delete', methods: ['POST'])]
    public function delete(?Deplacement $deplacement, Request $request): Response
    {
        $this->requireAdminAccess();

        if (!$deplacement) {
            $this->addFlash('danger', 'Déplacement introuvable.');
            return $this->redirectToRoute('app_admin_deplacements');
        }

        if ($this->isCsrfTokenValid('delete'.$deplacement->getId(), $request->request->get('_token'))) {
            $this->em->remove($deplacement);
            $this->em->flush();
            $this->addFlash('success', "Le déplacement « {$deplacement->getNom()} » a bien été supprimé !");
        }

        return $this->redirectToRoute('app_admin_deplacements');
    }

    #[Route('/delete-bulk', name: 'app_admin_deplacement_delete_bulk', methods: ['POST'])]
    public function deleteBulk(Request $request): Response
    {
        $this->requireAdminAccess();

        if (!$this->isCsrfTokenValid('bulk-delete', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $ids = $request->request->all('ids');
        $deleted = 0;

        foreach ($ids as $id) {
            $deplacement = $this->em->find(\App\Entity\Deplacement::class, (int) $id);
            if (!$deplacement) { continue; }
            $this->em->remove($deplacement);
            $deleted++;
        }
        $this->em->flush();

        if ($deleted > 0) {
            $this->addFlash('success', "$deleted déplacement(s) supprimé(s).");
        }

        return $this->redirectToRoute('app_admin_deplacements');
    }
}
