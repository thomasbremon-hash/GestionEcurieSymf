<?php

namespace App\Repository;

use App\Entity\Deplacement;
use App\Entity\MoisDeGestion;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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

    /**
     * Récupère tous les déplacements du mois en cours
     *
     * @return Deplacement[]
     */
    public function findByCurrentMonth(): array
    {
        $firstDay = new DateTime('first day of this month');
        $lastDay = new DateTime('last day of this month');

        return $this->createQueryBuilder('d')
            ->andWhere('d.date >= :start AND d.date <= :end')
            ->setParameter('start', $firstDay->format('Y-m-d'))
            ->setParameter('end', $lastDay->format('Y-m-d'))
            ->orderBy('d.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * N derniers déplacements pour les chevaux de l'utilisateur.
     */
    public function findRecentByUser(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.chevaux', 'c')
            ->join('c.chevalProprietaires', 'cp')
            ->where('cp.proprietaire = :user')
            ->setParameter('user', $user)
            ->orderBy('d.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
