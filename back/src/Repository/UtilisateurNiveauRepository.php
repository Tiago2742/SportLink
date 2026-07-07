<?php

namespace App\Repository;

use App\Entity\UtilisateurNiveau;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UtilisateurNiveauRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UtilisateurNiveau::class);
    }

    /**
     * Retourne les IDs des sports déclarés par un utilisateur.
     *
     * @return int[]
     */
    public function findSportIdsByUtilisateur(int $userId): array
    {
        $rows = $this->createQueryBuilder('un')
            ->select('IDENTITY(un.sport) AS sportId')
            ->where('un.utilisateur = :id')
            ->setParameter('id', $userId)
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'sportId');
    }
}
