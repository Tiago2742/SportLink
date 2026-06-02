<?php

namespace App\Repository;

use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    /** @return Game[] */
    public function trouverAvecFiltres(
        ?string $sport,
        ?string $lieu,
        ?string $statut,
        ?string $niveauRequis,
        ?int $createurId = null,
    ): array {
        $qb = $this->createQueryBuilder('g')
            ->addSelect('c')
            ->leftJoin('g.createur', 'c');

        if ($sport) {
            $qb->andWhere('LOWER(g.sport) = LOWER(:sport)')
               ->setParameter('sport', $sport);
        }

        if ($lieu) {
            $qb->andWhere('LOWER(g.lieu) LIKE LOWER(:lieu)')
               ->setParameter('lieu', '%' . $lieu . '%');
        }

        if ($statut) {
            $qb->andWhere('LOWER(g.statut) = LOWER(:statut)')
               ->setParameter('statut', $statut);
        }

        if ($niveauRequis) {
            $qb->andWhere('LOWER(g.niveauRequis) = LOWER(:niveauRequis)')
               ->setParameter('niveauRequis', $niveauRequis);
        }

        if ($createurId) {
            $qb->andWhere('c.id = :createurId')
               ->setParameter('createurId', $createurId);
        }

        return $qb->orderBy('g.dateMatch', 'ASC')->getQuery()->getResult();
    }
}
