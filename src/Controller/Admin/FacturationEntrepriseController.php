<?php

namespace App\Controller\Admin;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\User;
use App\Entity\MoisDeGestion;
use App\Service\FactureCalculator;
use App\Entity\FacturationEntreprise;
use App\Entity\FacturationUtilisateur;
use App\Form\FacturationGenerationType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\MoisDeGestionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\FacturationEntrepriseRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/facturation')]
final class FacturationEntrepriseController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
}
