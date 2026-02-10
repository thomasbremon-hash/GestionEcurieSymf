<?php

namespace App\Service;

use App\Entity\ChevalProduit;
use App\Entity\MoisDeGestion;
use App\Repository\DeplacementRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;

class DeplacementToChevalProduitService
{
    public function __construct(
        private EntityManagerInterface $em,
        private DeplacementRepository $deplacementRepo,
        private ProduitRepository $produitRepo
    ) {}

    public function genererPourMois(MoisDeGestion $mois): void
    {
        $produitKm = $this->produitRepo->findOneBy(['nom' => 'Déplacement']);

        if (!$produitKm) {
            throw new \RuntimeException('Produit "Déplacement" introuvable');
        }

        $deplacements = $this->deplacementRepo->findByMois($mois);

        foreach ($deplacements as $deplacement) {

            $chevaux = $deplacement->getChevaux();
            if ($chevaux->isEmpty()) {
                continue;
            }

            $kmParCheval = $deplacement->getDistance() / count($chevaux);

            foreach ($chevaux as $cheval) {
                $cp = new ChevalProduit();
                $cp->setCheval($cheval);
                $cp->setProduit($produitKm);
                $cp->setMoisDeGestion($mois);
                $cp->setQuantite($kmParCheval);
                $cp->setPrixUnitaire($produitKm->getPrix());
                $cp->setTotal($kmParCheval * $produitKm->getPrix());
                $cp->setCommentaire(
                    sprintf(
                        'Déplacement "%s" du %s',
                        $deplacement->getNom(),
                        $deplacement->getDate()?->format('d/m/Y')
                    )
                );

                $this->em->persist($cp);
            }
        }

        $this->em->flush();
    }
}
