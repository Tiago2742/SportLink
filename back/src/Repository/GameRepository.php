<?php

namespace App\Repository;

use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    /**
     * Matchs rejoignables : statut en_attente ET date dans le futur.
     * Utilisé par l'accueil et la recherche par défaut.
     */
    public function estDisponible(): string
    {
        return "g.statut = 'en_attente' AND g.dateMatch > :maintenant";
    }

    /** @return Game[] */
    public function trouverAvecFiltres(
        ?int    $sportId             = null,
        ?string $lieu                = null,
        ?string $statut              = null,
        ?int    $niveauId            = null,
        ?int    $createurId          = null,
        bool    $disponibleSeulement = false,
        array   $sportIds            = [],
    ): array {
        $qb = $this->createQueryBuilder('g')
            ->addSelect('s', 'n', 'c', 'camps')
            ->leftJoin('g.sport', 's')
            ->leftJoin('g.niveauRequis', 'n')
            ->leftJoin('g.createur', 'c')
            ->leftJoin('g.camps', 'camps');

        // Restriction aux sports déclarés par l'utilisateur
        if (!empty($sportIds)) {
            $qb->andWhere('s.id IN (:sportIds)')
               ->setParameter('sportIds', $sportIds);
        }

        if ($disponibleSeulement) {
            // en_attente, date future, et moins de 2 camps (place libre)
            $qb->andWhere("g.statut = 'en_attente'")
               ->andWhere('g.dateMatch > :maintenant')
               ->andWhere(
                   '(SELECT COUNT(mc.id) FROM App\Entity\MatchCamp mc WHERE mc.game = g) < 2'
               )
               ->setParameter('maintenant', new \DateTime());
        } elseif ($statut) {
            $qb->andWhere('g.statut = :statut')
               ->setParameter('statut', $statut);
        }

        if ($sportId) {
            $qb->andWhere('s.id = :sportId')
               ->setParameter('sportId', $sportId);
        }

        if ($lieu) {
            $qb->andWhere('LOWER(g.lieu) LIKE LOWER(:lieu)')
               ->setParameter('lieu', '%' . $lieu . '%');
        }

        if ($niveauId) {
            $qb->andWhere('n.id = :niveauId')
               ->setParameter('niveauId', $niveauId);
        }

        if ($createurId) {
            $qb->andWhere('c.id = :createurId')
               ->setParameter('createurId', $createurId);
        }

        return $qb->orderBy('g.dateMatch', 'ASC')->getQuery()->getResult();
    }

    /**
     * Matchs du compte connecté : créés par lui ou où il est inscrit (joueur / club d'une équipe).
     *
     * @return Game[]
     */
    public function trouverPourParticipant(int $utilisateurId, ?string $statut = null): array
    {
        $qb = $this->createQueryBuilder('g')
            ->distinct()
            ->addSelect('s', 'n', 'c', 'camps')
            ->leftJoin('g.sport', 's')
            ->leftJoin('g.niveauRequis', 'n')
            ->leftJoin('g.createur', 'c')
            ->leftJoin('g.camps', 'camps');

        $qb->andWhere($qb->expr()->orX(
            'c.id = :utilisateurId',
            'EXISTS (SELECT 1 FROM App\Entity\MatchCamp mcj JOIN mcj.joueur u WHERE mcj.game = g AND u.id = :utilisateurId)',
            'EXISTS (SELECT 1 FROM App\Entity\MatchCamp mce JOIN mce.equipe eq JOIN eq.club cl WHERE mce.game = g AND cl.id = :utilisateurId)',
        ))
            ->setParameter('utilisateurId', $utilisateurId);

        if ($statut) {
            $qb->andWhere('g.statut = :statut')
               ->setParameter('statut', $statut);
        }

        return $qb->orderBy('g.dateMatch', 'ASC')->getQuery()->getResult();
    }
}
