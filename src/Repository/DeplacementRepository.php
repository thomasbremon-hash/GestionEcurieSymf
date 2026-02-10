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
    public function findByMois(MoisDeGestion $mois): array
    {
        $start = new \DateTimeImmutable(sprintf(
            '%04d-%02d-01 00:00:00',
            $mois->getAnnee(),
            $mois->getMois()
        ));

        $end = $start->modify('last day of this month 23:59:59');

        return $this->createQueryBuilder('d')
            ->andWhere('d.date BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }
}
