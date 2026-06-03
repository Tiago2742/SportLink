<?php

namespace App\Service;

use App\Entity\Equipe;
use App\Entity\EquipeJoueur;
use App\Entity\Niveau;
use App\Entity\Sport;
use App\Entity\Utilisateur;
use App\Enum\OrigineMembreEquipe;
use App\Enum\RoleEquipe;
use App\Enum\StatutMembreEquipe;
use App\Enum\TypeSport;
use App\Enum\TypeUtilisateur;
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
        if ($club->getType() !== TypeUtilisateur::Club) {
            throw new \InvalidArgumentException('Seul un compte club peut créer une équipe.');
        }

        if ($sport->getType() !== TypeSport::Collectif) {
            throw new \InvalidArgumentException(
                'Une équipe ne peut être créée que pour un sport collectif (foot, rugby, volley…).'
            );
        }

        $equipe = new Equipe();
        $equipe->setNom($nom);
        $equipe->setSport($sport);
        $equipe->setNiveau($niveau);
        $equipe->setLocalisation($localisation);
        $equipe->setLogo($logo);
        $equipe->setClub($club);

        $gestionnaire = new EquipeJoueur();
        $gestionnaire->setUtilisateur($club);
        $gestionnaire->setEquipe($equipe);
        $gestionnaire->setRole(RoleEquipe::Gestionnaire);
        $gestionnaire->setStatut(StatutMembreEquipe::Confirme);
        $gestionnaire->setOrigine(OrigineMembreEquipe::InvitationClub);
        $equipe->addMembre($gestionnaire);

        $this->em->persist($equipe);
        $this->em->persist($gestionnaire);
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
        if ($nom !== null) {
            $equipe->setNom($nom);
        }
        if ($sport !== null) {
            $equipe->setSport($sport);
        }
        if ($niveau !== null) {
            $equipe->setNiveau($niveau);
        } elseif ($effacerNiveau) {
            $equipe->setNiveau(null);
        }
        if ($localisation !== null) {
            $equipe->setLocalisation($localisation);
        }
        if ($logo !== null) {
            $equipe->setLogo($logo);
        }

        $this->em->flush();

        return $equipe;
    }

    public function ajouterMembre(
        Equipe $equipe,
        int $utilisateurId,
        ?string $role,
        OrigineMembreEquipe $origine = OrigineMembreEquipe::InvitationClub,
    ): EquipeJoueur {
        $utilisateur = $this->utilisateurRepository->find($utilisateurId);
        if (!$utilisateur) {
            throw new \InvalidArgumentException('Utilisateur introuvable.');
        }

        if ($utilisateur->getType() !== TypeUtilisateur::Joueur) {
            throw new \InvalidArgumentException('Seuls les comptes joueur peuvent rejoindre une équipe en tant que membre.');
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
        $membreEquipe->setStatut(StatutMembreEquipe::EnAttente);
        $membreEquipe->setOrigine($origine);

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
