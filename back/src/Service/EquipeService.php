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
use App\Repository\EquipeJoueurRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;

class EquipeService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UtilisateurRepository  $utilisateurRepository,
        private EquipeJoueurRepository $equipeJoueurRepository,
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

    /**
     * Invitation club → joueur (étape 2).
     */
    public function inviterJoueur(Equipe $equipe, int $joueurId, Utilisateur $club): EquipeJoueur
    {
        if ($equipe->getClub() !== $club) {
            throw new \InvalidArgumentException('Seul le club propriétaire peut inviter des joueurs.');
        }

        $joueur = $this->utilisateurRepository->find($joueurId);
        if (!$joueur) {
            throw new \InvalidArgumentException('Joueur introuvable.');
        }

        if ($joueur->getType() !== TypeUtilisateur::Joueur) {
            throw new \InvalidArgumentException('Seuls les comptes joueur peuvent être invités dans une équipe.');
        }

        foreach ($equipe->getMembres() as $membre) {
            if ($membre->getUtilisateur() !== $joueur) {
                continue;
            }

            if ($membre->getStatut() === StatutMembreEquipe::Refuse) {
                $this->verifierUneEquipeParSport($joueur, $equipe->getSport(), $equipe);

                $membre->setStatut(StatutMembreEquipe::EnAttente);
                $membre->setOrigine(OrigineMembreEquipe::InvitationClub);
                $membre->setRole(RoleEquipe::Joueur);
                $this->em->flush();

                return $membre;
            }

            if ($membre->getStatut() === StatutMembreEquipe::EnAttente) {
                throw new \InvalidArgumentException('Une invitation est déjà en attente pour ce joueur.');
            }

            throw new \InvalidArgumentException('Ce joueur fait déjà partie de cette équipe.');
        }

        $this->verifierUneEquipeParSport($joueur, $equipe->getSport(), $equipe);

        $membreEquipe = new EquipeJoueur();
        $membreEquipe->setUtilisateur($joueur);
        $membreEquipe->setEquipe($equipe);
        $membreEquipe->setRole(RoleEquipe::Joueur);
        $membreEquipe->setStatut(StatutMembreEquipe::EnAttente);
        $membreEquipe->setOrigine(OrigineMembreEquipe::InvitationClub);

        $this->em->persist($membreEquipe);
        $this->em->flush();

        return $membreEquipe;
    }

    /**
     * Demande d'adhésion joueur → club (étape 3).
     */
    public function demanderAdhesion(Equipe $equipe, Utilisateur $joueur): EquipeJoueur
    {
        if ($joueur->getType() !== TypeUtilisateur::Joueur) {
            throw new \InvalidArgumentException('Seul un compte joueur peut demander à rejoindre une équipe.');
        }

        if ($equipe->getClub() === $joueur) {
            throw new \InvalidArgumentException('Vous ne pouvez pas demander à rejoindre une équipe que vous gérez.');
        }

        foreach ($equipe->getMembres() as $membre) {
            if ($membre->getUtilisateur() !== $joueur) {
                continue;
            }

            if ($membre->getStatut() === StatutMembreEquipe::Refuse) {
                $this->verifierUneEquipeParSport($joueur, $equipe->getSport(), $equipe);

                $membre->setStatut(StatutMembreEquipe::EnAttente);
                $membre->setOrigine(OrigineMembreEquipe::DemandeJoueur);
                $membre->setRole(RoleEquipe::Joueur);
                $this->em->flush();

                return $membre;
            }

            if ($membre->getStatut() === StatutMembreEquipe::EnAttente) {
                throw new \InvalidArgumentException('Une demande est déjà en attente pour cette équipe.');
            }

            throw new \InvalidArgumentException('Vous faites déjà partie de cette équipe.');
        }

        $this->verifierUneEquipeParSport($joueur, $equipe->getSport(), $equipe);

        $membreEquipe = new EquipeJoueur();
        $membreEquipe->setUtilisateur($joueur);
        $membreEquipe->setEquipe($equipe);
        $membreEquipe->setRole(RoleEquipe::Joueur);
        $membreEquipe->setStatut(StatutMembreEquipe::EnAttente);
        $membreEquipe->setOrigine(OrigineMembreEquipe::DemandeJoueur);

        $this->em->persist($membreEquipe);
        $this->em->flush();

        return $membreEquipe;
    }

    /**
     * Réponse à une adhésion en attente (invitation club par le joueur, demande par le club).
     */
    public function repondreAdhesion(
        EquipeJoueur $membre,
        StatutMembreEquipe $nouveauStatut,
        Utilisateur $acteur,
    ): EquipeJoueur {
        if ($membre->getStatut() !== StatutMembreEquipe::EnAttente) {
            throw new \InvalidArgumentException('Cette adhésion n\'est plus en attente de réponse.');
        }

        if ($nouveauStatut === StatutMembreEquipe::EnAttente) {
            throw new \InvalidArgumentException('Statut invalide. Valeurs acceptées : confirme, refuse.');
        }

        return match ($membre->getOrigine()) {
            OrigineMembreEquipe::InvitationClub => $this->repondreInvitationClub($membre, $nouveauStatut, $acteur),
            OrigineMembreEquipe::DemandeJoueur => $this->repondreDemandeJoueur($membre, $nouveauStatut, $acteur),
        };
    }

    /** @deprecated Utiliser repondreAdhesion */
    public function repondreInvitation(
        EquipeJoueur $membre,
        StatutMembreEquipe $nouveauStatut,
        Utilisateur $joueur,
    ): EquipeJoueur {
        return $this->repondreAdhesion($membre, $nouveauStatut, $joueur);
    }

    private function repondreInvitationClub(
        EquipeJoueur $membre,
        StatutMembreEquipe $nouveauStatut,
        Utilisateur $joueur,
    ): EquipeJoueur {
        if ($membre->getUtilisateur() !== $joueur) {
            throw new \InvalidArgumentException('Seul le joueur invité peut répondre à cette invitation.');
        }

        if ($nouveauStatut === StatutMembreEquipe::Confirme) {
            $this->verifierUneEquipeParSport($joueur, $membre->getEquipe()->getSport(), $membre->getEquipe());
        }

        $membre->setStatut($nouveauStatut);
        $this->em->flush();

        return $membre;
    }

    private function repondreDemandeJoueur(
        EquipeJoueur $membre,
        StatutMembreEquipe $nouveauStatut,
        Utilisateur $club,
    ): EquipeJoueur {
        if ($club->getType() !== TypeUtilisateur::Club) {
            throw new \InvalidArgumentException('Seul le club propriétaire peut répondre à cette demande.');
        }

        if ($membre->getEquipe()->getClub() !== $club) {
            throw new \InvalidArgumentException('Accès refusé.');
        }

        if ($nouveauStatut === StatutMembreEquipe::Confirme) {
            $this->verifierUneEquipeParSport(
                $membre->getUtilisateur(),
                $membre->getEquipe()->getSport(),
                $membre->getEquipe(),
            );
        }

        $membre->setStatut($nouveauStatut);
        $this->em->flush();

        return $membre;
    }

    /**
     * Annule une adhésion en attente (invitation club ou demande joueur).
     */
    public function annulerAdhesionEnAttente(EquipeJoueur $membre, Utilisateur $acteur): void
    {
        if ($membre->getStatut() !== StatutMembreEquipe::EnAttente) {
            throw new \InvalidArgumentException('Seule une adhésion en attente peut être annulée.');
        }

        if ($membre->getRole() === RoleEquipe::Gestionnaire) {
            throw new \InvalidArgumentException('Impossible de retirer le gestionnaire de l\'équipe.');
        }

        $equipe = $membre->getEquipe();

        $peutAnnuler = match ($membre->getOrigine()) {
            OrigineMembreEquipe::InvitationClub =>
                $acteur->getType() === TypeUtilisateur::Club && $equipe->getClub() === $acteur,
            OrigineMembreEquipe::DemandeJoueur =>
                ($acteur->getType() === TypeUtilisateur::Club && $equipe->getClub() === $acteur)
                || $membre->getUtilisateur() === $acteur,
            default => false,
        };

        if (!$peutAnnuler) {
            throw new \InvalidArgumentException('Accès refusé.');
        }

        $this->em->remove($membre);
        $this->em->flush();
    }

    /** @deprecated Utiliser annulerAdhesionEnAttente */
    public function annulerInvitation(EquipeJoueur $membre, Utilisateur $club): void
    {
        $this->annulerAdhesionEnAttente($membre, $club);
    }

    /** @return EquipeJoueur[] */
    public function listerInvitationsEnAttente(Utilisateur $joueur): array
    {
        if ($joueur->getType() !== TypeUtilisateur::Joueur) {
            return [];
        }

        return $this->equipeJoueurRepository->trouverInvitationsEnAttentePourJoueur($joueur);
    }

    /**
     * Espace équipe joueur (mes équipes, invitations, demandes).
     *
     * @return array{
     *     mesEquipes: EquipeJoueur[],
     *     invitations: EquipeJoueur[],
     *     demandes: EquipeJoueur[],
     *     nbInvitations: int,
     * }
     */
    public function listerEspaceJoueur(Utilisateur $joueur): array
    {
        if ($joueur->getType() !== TypeUtilisateur::Joueur) {
            return [
                'mesEquipes'    => [],
                'invitations'   => [],
                'demandes'      => [],
                'nbInvitations' => 0,
            ];
        }

        $invitations = $this->equipeJoueurRepository->trouverInvitationsEnAttentePourJoueur($joueur);

        return [
            'mesEquipes'    => $this->equipeJoueurRepository->trouverAdhesionsConfirmeesPourJoueur($joueur),
            'invitations'   => $invitations,
            'demandes'      => $this->equipeJoueurRepository->trouverDemandesJoueurPourJoueur($joueur),
            'nbInvitations' => count($invitations),
        ];
    }

    private function verifierUneEquipeParSport(
        Utilisateur $joueur,
        Sport $sport,
        ?Equipe $exclureEquipe = null,
    ): void {
        if ($this->equipeJoueurRepository->aAdhesionActiveSurSport($joueur, $sport, $exclureEquipe)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Ce joueur est déjà inscrit (ou invité) dans une autre équipe de %s.',
                    $sport->getNom(),
                ),
            );
        }
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
