<?php

namespace App\Repository;

use App\Entity\Cheval;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cheval>
 */
class ChevalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cheval::class);
    }

    //    /**
    //     * @return Cheval[] Returns an array of Cheval objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Cheval
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Retourne les chevaux de l'utilisateur avec son pourcentage de détention.
     * Résultat : array of ['cheval' => Cheval, 'pourcentage' => float]
     *
     * Adapte 'uc.user' et 'uc.pourcentage' selon ta table de liaison.
     * Si tu as une entité UserCheval avec propriétés $user, $cheval, $pourcentage :
     */
    public function findByUserWithPourcentage(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->select('c AS cheval, cp.pourcentage')
            ->join('c.chevalProprietaires', 'cp')
            ->where('cp.proprietaire = :user')
            ->setParameter('user', $user)
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
