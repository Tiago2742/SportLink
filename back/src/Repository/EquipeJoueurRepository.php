<?php

namespace App\Repository;

use App\Entity\Equipe;
use App\Entity\EquipeJoueur;
use App\Entity\Sport;
use App\Entity\Utilisateur;
use App\Enum\StatutMembreEquipe;
use App\Enum\TypeUtilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<EquipeJoueur> */
class EquipeJoueurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EquipeJoueur::class);
    }

    /**
     * Adhésions actives (en attente ou confirmées) du joueur sur une autre équipe du même sport.
     */
    public function aAdhesionActiveSurSport(
        Utilisateur $joueur,
        Sport $sport,
        ?Equipe $exclureEquipe = null,
    ): bool {
        if ($joueur->getType() !== TypeUtilisateur::Joueur) {
            return false;
        }

        $qb = $this->createQueryBuilder('ej')
            ->select('COUNT(ej.id)')
            ->innerJoin('ej.equipe', 'eq')
            ->innerJoin('eq.sport', 's')
            ->andWhere('ej.utilisateur = :joueur')
            ->andWhere('s.id = :sportId')
            ->andWhere('ej.statut IN (:statuts)')
            ->setParameter('joueur', $joueur)
            ->setParameter('sportId', $sport->getId())
            ->setParameter('statuts', [
                StatutMembreEquipe::EnAttente->value,
                StatutMembreEquipe::Confirme->value,
            ]);

        if ($exclureEquipe !== null) {
            $qb->andWhere('eq.id != :exclureEquipeId')
               ->setParameter('exclureEquipeId', $exclureEquipe->getId());
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /** @return EquipeJoueur[] */
    public function trouverInvitationsEnAttentePourJoueur(Utilisateur $joueur): array
    {
        return $this->createQueryBuilder('ej')
            ->addSelect('eq', 's', 'n', 'c')
            ->innerJoin('ej.equipe', 'eq')
            ->innerJoin('eq.sport', 's')
            ->leftJoin('eq.niveau', 'n')
            ->innerJoin('eq.club', 'c')
            ->andWhere('ej.utilisateur = :joueur')
            ->andWhere('ej.statut = :statut')
            ->andWhere('ej.origine = :origine')
            ->setParameter('joueur', $joueur)
            ->setParameter('statut', StatutMembreEquipe::EnAttente->value)
            ->setParameter('origine', \App\Enum\OrigineMembreEquipe::InvitationClub->value)
            ->orderBy('ej.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
