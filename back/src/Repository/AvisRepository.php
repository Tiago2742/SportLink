<?php

namespace App\Repository;

use App\Entity\Avis;
use App\Entity\Game;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AvisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Avis::class);
    }

    public function findParNotantEtGame(Utilisateur $notant, Game $game): ?Avis
    {
        return $this->findOneBy(['notant' => $notant, 'game' => $game]);
    }

    /**
     * Moyennes des 3 critères + nombre total d'avis reçus par un utilisateur.
     * Retourne null si aucun avis reçu.
     *
     * @return array{ponctualite: float, fairPlay: float, niveauConforme: float, total: int}|null
     */
    public function calculerMoyennesRecues(Utilisateur $evalue): ?array
    {
        $result = $this->createQueryBuilder('a')
            ->select(
                'AVG(a.ponctualite) AS ponctualite',
                'AVG(a.fairPlay) AS fairPlay',
                'AVG(a.niveauConforme) AS niveauConforme',
                'COUNT(a.id) AS total',
            )
            ->where('a.evalue = :evalue')
            ->setParameter('evalue', $evalue)
            ->getQuery()
            ->getSingleResult();

        if ((int) $result['total'] === 0) {
            return null;
        }

        return [
            'ponctualite'    => round((float) $result['ponctualite'], 1),
            'fairPlay'       => round((float) $result['fairPlay'], 1),
            'niveauConforme' => round((float) $result['niveauConforme'], 1),
            'total'          => (int) $result['total'],
        ];
    }
}
