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
    public function trouverAvecFiltres(
        ?string $sport,
        ?string $niveau,
        ?string $localisation,
        ?int $createurId = null,
    ): array {
        $qb = $this->createQueryBuilder('e')
            ->addSelect('c')
            ->leftJoin('e.createur', 'c');

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

        if ($createurId) {
            $qb->andWhere('c.id = :createurId')
               ->setParameter('createurId', $createurId);
        }

        return $qb->orderBy('e.id', 'DESC')->getQuery()->getResult();
    }
}
