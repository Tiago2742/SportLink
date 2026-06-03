<?php

namespace App\Service;

use App\Entity\Equipe;
use App\Entity\EquipeJoueur;
use App\Entity\Niveau;
use App\Entity\Sport;
use App\Entity\Utilisateur;
use App\Enum\RoleEquipe;
use App\Enum\StatutMembreEquipe;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;

class EquipeService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UtilisateurRepository  $utilisateurRepository,
    ) {}

    public function creer(
        string $nom,
        Sport $sport,
        ?Niveau $niveau,
        ?string $localisation,
        ?string $logo,
        Utilisateur $club,
    ): Equipe {
        $equipe = new Equipe();
        $equipe->setNom($nom);
        $equipe->setSport($sport);
        $equipe->setNiveau($niveau);
        $equipe->setLocalisation($localisation);
        $equipe->setLogo($logo);
        $equipe->setClub($club);

        $this->em->persist($equipe);
        $this->em->flush();

        return $equipe;
    }

    public function modifier(
        Equipe $equipe,
        ?string $nom,
        ?Sport $sport,
        ?Niveau $niveau,
        bool $effacerNiveau,
        ?string $localisation,
        ?string $logo,
    ): Equipe {
        if ($nom !== null)          $equipe->setNom($nom);
        if ($sport !== null)        $equipe->setSport($sport);
        if ($niveau !== null)       $equipe->setNiveau($niveau);
        elseif ($effacerNiveau)     $equipe->setNiveau(null);
        if ($localisation !== null) $equipe->setLocalisation($localisation);
        if ($logo !== null)         $equipe->setLogo($logo);

        $this->em->flush();

        return $equipe;
    }

    public function ajouterMembre(Equipe $equipe, int $utilisateurId, ?string $role): EquipeJoueur
    {
        $utilisateur = $this->utilisateurRepository->find($utilisateurId);
        if (!$utilisateur) {
            throw new \InvalidArgumentException('Utilisateur introuvable.');
        }

        foreach ($equipe->getMembres() as $membre) {
            if ($membre->getUtilisateur() === $utilisateur) {
                throw new \InvalidArgumentException('Cet utilisateur est déjà membre de l\'équipe.');
            }
        }

        $roleEnum = ($role !== null && RoleEquipe::tryFrom($role) !== null)
            ? RoleEquipe::from($role)
            : RoleEquipe::Joueur;

        $membreEquipe = new EquipeJoueur();
        $membreEquipe->setUtilisateur($utilisateur);
        $membreEquipe->setEquipe($equipe);
        $membreEquipe->setRole($roleEnum);
        $membreEquipe->setStatut(StatutMembreEquipe::Invite);

        $this->em->persist($membreEquipe);
        $this->em->flush();

        return $membreEquipe;
    }

    public function retirerMembre(EquipeJoueur $membre): void
    {
        $this->em->remove($membre);
        $this->em->flush();
    }

    public function supprimer(Equipe $equipe): void
    {
        $this->em->remove($equipe);
        $this->em->flush();
    }
}
