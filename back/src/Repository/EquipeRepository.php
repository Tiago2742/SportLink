<?php

namespace App\Repository;

use App\Entity\Equipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EquipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Equipe::class);
    }

    /** @return Equipe[] */
    public function trouverAvecFiltres(
        ?int    $sportId      = null,
        ?int    $niveauId     = null,
        ?string $localisation = null,
        ?int    $clubId       = null,
        ?string $nom          = null,
        ?array  $sportIds     = null,
    ): array {
        $qb = $this->createQueryBuilder('e')
            ->addSelect('s', 'n', 'c')
            ->leftJoin('e.sport', 's')
            ->leftJoin('e.niveau', 'n')
            ->leftJoin('e.club', 'c');

        if ($sportId) {
            $qb->andWhere('s.id = :sportId')
               ->setParameter('sportId', $sportId);
        } elseif ($sportIds !== null && count($sportIds) > 0) {
            $qb->andWhere('s.id IN (:sportIds)')
               ->setParameter('sportIds', $sportIds);
        } elseif ($sportIds !== null && count($sportIds) === 0) {
            // Aucun sport déclaré → aucun résultat
            $qb->andWhere('1 = 0');
        }

        if ($niveauId) {
            $qb->andWhere('n.id = :niveauId')
               ->setParameter('niveauId', $niveauId);
        }

        if ($localisation) {
            $qb->andWhere('LOWER(e.localisation) LIKE LOWER(:localisation)')
               ->setParameter('localisation', '%' . $localisation . '%');
        }

        if ($clubId) {
            $qb->andWhere('c.id = :clubId')
               ->setParameter('clubId', $clubId);
        }

        if ($nom !== null && trim($nom) !== '') {
            $qb->andWhere('LOWER(e.nom) LIKE LOWER(:nom)')
               ->setParameter('nom', '%' . trim($nom) . '%');
        }

        return $qb->orderBy('e.id', 'DESC')->getQuery()->getResult();
    }
}
