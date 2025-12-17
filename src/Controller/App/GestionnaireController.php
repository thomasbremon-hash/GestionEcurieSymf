<?php

namespace App\Controller\App;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GestionnaireController extends AbstractController
{
    #[Route('/gestionnaire', name: 'app_gestionnaire')]
    public function index(): Response
    {
        return $this->render('app/gestionnaire/index.html.twig', [
            'controller_name' => 'App/GestionnaireController',
        ]);
    }
}
