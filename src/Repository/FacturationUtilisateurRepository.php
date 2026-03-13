<?php

namespace App\Repository;

use App\Entity\FacturationUtilisateur;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FacturationUtilisateur>
 */
class FacturationUtilisateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FacturationUtilisateur::class);
    }

    //    /**
    //     * @return FacturationUtilisateur[] Returns an array of FacturationUtilisateur objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?FacturationUtilisateur
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function getNextNumeroFacture(): string
    {
        $year = date('Y');

        $qb = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.numFacture LIKE :year')
            ->setParameter('year', "FACT-$year-%");

        $count = $qb->getQuery()->getSingleScalarResult();

        $next = $count + 1;

        return sprintf('FACT-%s-%04d', $year, $next);
    }

    /**
     * Retourne le chiffre d'affaires total (somme de toutes les factures)
     *
     * @return float
     */
    public function sumTotalCA(): float
    {
        $qb = $this->createQueryBuilder('f')
            ->select('SUM(f.total) as totalCA');

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result ? (float)$result : 0.0;
    }

    public function findByUtilisateur(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->join('f.moisDeGestion', 'mg')
            ->where('f.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('mg.annee', 'DESC')
            ->addOrderBy('mg.mois', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findImpayeesByUtilisateur(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->join('f.moisDeGestion', 'mg')
            ->where('f.utilisateur = :user')
            ->andWhere('f.statut = :statut')
            ->setParameter('user', $user)
            ->setParameter('statut', 'impayee')
            ->orderBy('mg.annee', 'DESC')
            ->addOrderBy('mg.mois', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
