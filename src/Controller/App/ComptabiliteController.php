<?php

namespace App\Controller\App;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ComptabiliteController extends AbstractController
{
    #[Route('/comptabilite', name: 'app_comptabilite')]
    public function index(): Response
    {
        return $this->render('app/comptabilite/index.html.twig', [
            'controller_name' => 'App/ComptabiliteController',
        ]);
    }
}
