<?php

namespace App\Repository;

use App\Entity\Deplacement;
use App\Entity\MoisDeGestion;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Deplacement>
 */
class DeplacementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deplacement::class);
    }

    //    /**
    //     * @return Deplacement[] Returns an array of Deplacement objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Deplacement
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function findByMois(\App\Entity\MoisDeGestion $mois)
    {
        $debutMois = new \DateTime(sprintf('%d-%02d-01', $mois->getAnnee(), $mois->getMois()));
        $finMois = (clone $debutMois)->modify('last day of this month')->setTime(23, 59, 59);

        return $this->createQueryBuilder('d')
            ->where('d.date BETWEEN :debut AND :fin')
            ->setParameter('debut', $debutMois)
            ->setParameter('fin', $finMois)
            ->getQuery()
            ->getResult();
    }
}
