<?php

namespace App\Controller\App;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ClientController extends AbstractController
{
    #[Route('/client', name: 'app_client')]
    public function index(): Response
    {
        return $this->render('app/client/index.html.twig', [
            'controller_name' => 'App/ClientController',
        ]);
    }
}
