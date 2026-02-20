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
        $seen = []; // Pour éviter les doublons

        foreach ($user->getChevalProprietaires() as $cp) {
            $cheval = $cp->getCheval();
            $pourcentage = $cp->getPourcentage() / 100;

            foreach ($cheval->getChevalProduits() as $conso) {
                if ($conso->getMoisDeGestion()->getId() !== $mois->getId()) {
                    continue;
                }

                // clé unique pour éviter doublons
                $key = $conso->getId() . '-' . $cp->getPourcentage();
                if (in_array($key, $seen)) {
                    continue;
                }
                $seen[] = $key;

                $prixProrata = $conso->getPrixUnitaire() * $pourcentage;
                $montantHT = $prixProrata * $conso->getQuantite();
                $montantTVA = $montantHT * ($conso->getProduit()->getTauxTVA() / 100);
                $montantTTC = $montantHT + $montantTVA;

                // 🔹 Si c'est un déplacement, on prend le commentaire comme description
                $description = $conso->getProduit()->getNom();
                if ($conso->getProduit()->getNom() === 'Déplacement' && $conso->getCommentaire()) {
                    $description = $conso->getCommentaire();
                }

                $lignes[] = [
                    'cheval' => $cheval->getNom(),
                    'pourcentage' => $cp->getPourcentage(),
                    'code' => $conso->getProduit()->getNom(),
                    'description' => $description,
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
