<?php

namespace App\Repository;

use App\Entity\Sport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sport::class);
    }

    public function findAllAvecNiveaux(): array
    {
        return $this->createQueryBuilder('s')
            ->addSelect('n')
            ->leftJoin('s.niveaux', 'n')
            ->orderBy('s.nom', 'ASC')
            ->addOrderBy('n.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
