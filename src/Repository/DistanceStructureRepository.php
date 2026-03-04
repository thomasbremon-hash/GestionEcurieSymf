<?php

namespace App\Repository;

use App\Entity\DistanceStructure;
use App\Entity\Entreprise;
use App\Entity\Structure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DistanceStructure>
 */
class DistanceStructureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DistanceStructure::class);
    }

    //    /**
    //     * @return DistanceStructure[] Returns an array of DistanceStructure objects
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

    //    public function findOneBySomeField($value): ?DistanceStructure
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }


    public function findDistance(Entreprise $entreprise, Structure $structure): ?int
    {
        $result = $this->createQueryBuilder('d')
            ->andWhere('d.entreprise = :entreprise')
            ->andWhere('d.structure = :structure')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('structure', $structure)
            ->getQuery()
            ->getOneOrNullResult();

        return $result?->getDistance();
    }
}
