<?php

namespace App\Repository;

use App\Entity\Equipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Equipe>
 */
class EquipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Equipe::class);
    }

    /** @return Equipe[] */
    public function trouverAvecFiltres(?string $sport, ?string $niveau, ?string $localisation): array
    {
        $qb = $this->createQueryBuilder('e');

        if ($sport) {
            $qb->andWhere('LOWER(e.sport) = LOWER(:sport)')
               ->setParameter('sport', $sport);
        }

        if ($niveau) {
            $qb->andWhere('LOWER(e.niveau) = LOWER(:niveau)')
               ->setParameter('niveau', $niveau);
        }

        if ($localisation) {
            $qb->andWhere('LOWER(e.localisation) LIKE LOWER(:localisation)')
               ->setParameter('localisation', '%' . $localisation . '%');
        }

        return $qb->orderBy('e.id', 'DESC')->getQuery()->getResult();
    }
}
