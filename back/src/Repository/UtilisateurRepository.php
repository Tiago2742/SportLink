<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use App\Enum\TypeUtilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/** @extends ServiceEntityRepository<Utilisateur> */
class UtilisateurRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Utilisateur) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Joueurs recherchables par nom/prénom/email (pour invitation club).
     * Si $sportId est fourni, ne retourne que les joueurs qui déclarent ce sport.
     *
     * @return Utilisateur[]
     */
    public function rechercherJoueurs(string $terme, int $limite = 15, ?int $sportId = null): array
    {
        $terme = trim($terme);
        if ($terme === '') {
            return [];
        }

        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.type = :type')
            ->andWhere(
                'LOWER(u.email) LIKE LOWER(:q)
                OR LOWER(u.nom) LIKE LOWER(:q)
                OR LOWER(u.prenom) LIKE LOWER(:q)',
            )
            ->setParameter('type', TypeUtilisateur::Joueur->value)
            ->setParameter('q', '%' . $terme . '%')
            ->orderBy('u.nom', 'ASC')
            ->setMaxResults($limite);

        if ($sportId !== null) {
            $qb->innerJoin('u.niveaux', 'un')
               ->innerJoin('un.sport', 's_filter')
               ->andWhere('s_filter.id = :sportId')
               ->setParameter('sportId', $sportId);
        }

        return $qb->getQuery()->getResult();
    }
}
