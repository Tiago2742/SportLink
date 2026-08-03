<?php

namespace App\Repository;

use App\Entity\DemandeMatch;
use App\Entity\Game;
use App\Entity\Utilisateur;
use App\Enum\StatutDemande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DemandeMatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DemandeMatch::class);
    }

    /**
     * Retourne l'enregistrement existant pour ce couple (match, demandeur),
     * quel que soit son statut. Null si aucun.
     */
    public function findExistante(Game $game, Utilisateur $demandeur): ?DemandeMatch
    {
        return $this->findOneBy(['game' => $game, 'demandeur' => $demandeur]);
    }

    /**
     * Retourne une map gameId → statut.value pour toutes les demandes
     * du demandeur sur les matchs indiqués (une seule requête).
     *
     * @param  int[]                $gameIds
     * @return array<int, string>
     */
    public function findStatutParUtilisateurEtGames(Utilisateur $demandeur, array $gameIds): array
    {
        if (empty($gameIds)) {
            return [];
        }

        /** @var DemandeMatch[] $demandes */
        $demandes = $this->createQueryBuilder('d')
            ->where('d.demandeur = :demandeur')
            ->andWhere('d.game IN (:gameIds)')
            ->setParameter('demandeur', $demandeur)
            ->setParameter('gameIds', $gameIds)
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($demandes as $demande) {
            $map[$demande->getGame()->getId()] = $demande->getStatut()->value;
        }

        return $map;
    }

    /**
     * Passe toutes les demandes en_attente d'un match en refusee.
     * Utilisé lors de l'acceptation d'une demande OU quand un camp_2 est confirmé
     * via le flux d'invitation.
     */
    public function refuserToutesEnAttente(Game $game): void
    {
        $this->createQueryBuilder('d')
            ->update()
            ->set('d.statut', ':refusee')
            ->where('d.game = :game')
            ->andWhere('d.statut = :enAttente')
            ->setParameter('refusee', StatutDemande::Refusee)
            ->setParameter('game', $game)
            ->setParameter('enAttente', StatutDemande::EnAttente)
            ->getQuery()
            ->execute();
    }
}
