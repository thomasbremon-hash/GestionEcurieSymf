<?php

namespace App\Twig;

use App\Repository\FacturationUtilisateurRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AdminExtension extends AbstractExtension
{
    public function __construct(
        private FacturationUtilisateurRepository $factureRepo
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('factures_en_retard_count', $this->countFacturesEnRetard(...)),
        ];
    }

    public function countFacturesEnRetard(int $jours = 30): int
    {
        return $this->factureRepo->countImpaieesEnRetard($jours);
    }
}
