<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\MoisDeGestion;

class FactureCalculator
{
    public function calculerFactureUtilisateur(User $user, MoisDeGestion $mois): array
    {
        $lignes = [];
        $totalHT = 0;
        $totalTVA = [];
        $totalTTC = 0;

        foreach ($user->getChevalProprietaires() as $cp) {
            $cheval = $cp->getCheval();
            $pourcentage = $cp->getPourcentage() / 100;

            foreach ($cheval->getChevalProduits() as $conso) {
                if ($conso->getMoisDeGestion()->getId() !== $mois->getId()) {
                    continue;
                }

                $prixProrata = $conso->getPrixUnitaire() * $pourcentage;
                $montantHT = $prixProrata * $conso->getQuantite();
                $montantTVA = $montantHT * ($conso->getProduit()->getTauxTVA() / 100);
                $montantTTC = $montantHT + $montantTVA;

                $lignes[] = [
                    'cheval' => $cheval->getNom(),
                    'pourcentage' => $cp->getPourcentage(),
                    'code' => $conso->getProduit()->getNom(),
                    'description' => $conso->getProduit()->getDescription(),
                    'quantite' => $conso->getQuantite(),
                    'prixBase' => $conso->getPrixUnitaire(),
                    'prixUnitaire' => $prixProrata,
                    'montantHT' => $montantHT,
                    'tauxTVA' => $conso->getProduit()->getTauxTVA(),
                    'montantTVA' => $montantTVA,
                ];

                $totalHT += $montantHT;
                $totalTTC += $montantTTC;

                if (!isset($totalTVA[$conso->getProduit()->getTauxTVA()])) {
                    $totalTVA[$conso->getProduit()->getTauxTVA()] = 0;
                }
                $totalTVA[$conso->getProduit()->getTauxTVA()] += $montantTVA;
            }
        }

        return [
            'lignes' => $lignes,
            'totalHT' => $totalHT,
            'totalTVA' => $totalTVA,
            'totalTTC' => $totalTTC,
        ];
    }
}