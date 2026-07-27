<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\MatchCamp;
use App\Entity\Message;
use App\Entity\Niveau;
use App\Entity\Resultat;
use App\Entity\Sport;
use App\Entity\Utilisateur;
use App\Enum\RoleMatchCamp;
use App\Enum\StatutGame;
use App\Enum\StatutMatchCamp;
use App\Enum\TypeSport;
use App\Enum\TypeUtilisateur;
use App\Repository\EquipeRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;

class MatchService
{
    public function __construct(
        private EntityManagerInterface $em,
        private EquipeRepository       $equipeRepository,
        private UtilisateurRepository  $utilisateurRepository,
    ) {}

    // -------------------------------------------------------------------------
    // Match CRUD
    // -------------------------------------------------------------------------

    public function creer(
        Sport $sport,
        ?Niveau $niveauRequis,
        string $dateMatch,
        ?string $lieu,
        Utilisateur $createur,
        ?string $description = null,
        ?int $equipeId = null,
    ): Game {
        $match = new Game();
        $match->setSport($sport);
        $match->setNiveauRequis($niveauRequis);
        $match->setDateMatch(new \DateTime($dateMatch));
        $match->setLieu($lieu);
        $match->setDescription($description);
        $match->setStatut(StatutGame::EnAttente);
        $match->setCreateur($createur);

        $this->em->persist($match);
        $this->inscrireCreateurCommeCamp1($match, $createur, $equipeId);
        $this->em->flush();

        return $match;
    }

    private function inscrireCreateurCommeCamp1(Game $match, Utilisateur $createur, ?int $equipeId): void
    {
        $sport = $match->getSport();
        $camp  = new MatchCamp();
        $camp->setRole(RoleMatchCamp::Camp1);
        $camp->setStatut(StatutMatchCamp::Confirme);

        if ($sport->getType() === TypeSport::Individuel) {
            $camp->setJoueur($createur);
        } else {
            if ($equipeId === null) {
                throw new \InvalidArgumentException(
                    'Le champ "equipeId" est requis pour créer un match de sport collectif.'
                );
            }
            $equipe = $this->equipeRepository->find($equipeId);
            if (!$equipe) {
                throw new \InvalidArgumentException('Équipe introuvable.');
            }
            if ($equipe->getClub() !== $createur) {
                throw new \InvalidArgumentException('Cette équipe ne vous appartient pas.');
            }
            if ($equipe->getSport() !== $sport) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'L\'équipe pratique "%s", le match est de "%s".',
                        $equipe->getSport()?->getNom(),
                        $sport->getNom(),
                    )
                );
            }
            $camp->setEquipe($equipe);
        }

        $match->addCamp($camp);
        $this->em->persist($camp);
    }

    public function modifier(
        Game $match,
        ?Sport $sport = null,
        ?Niveau $niveauRequis = null,
        bool $effacerNiveau = false,
        ?string $dateMatch = null,
        ?string $lieu = null,
        ?string $description = null,
        bool $effacerDescription = false,
    ): Game {
        // A3 — Statut et date
        if (in_array($match->getStatut(), [StatutGame::Termine, StatutGame::Annule], true)) {
            $libelle = $match->getStatut() === StatutGame::Termine ? 'terminé' : 'annulé';
            throw new \InvalidArgumentException("Impossible de modifier ce match : il est déjà {$libelle}.");
        }
        if ($match->getDateMatch() <= new \DateTime()) {
            throw new \InvalidArgumentException('Impossible de modifier ce match : la date est déjà passée.');
        }

        if ($sport !== null)                   $match->setSport($sport);
        if ($niveauRequis !== null)            $match->setNiveauRequis($niveauRequis);
        elseif ($effacerNiveau)                $match->setNiveauRequis(null);
        if ($dateMatch !== null)               $match->setDateMatch(new \DateTime($dateMatch));
        if ($lieu !== null)                    $match->setLieu($lieu);
        if ($description !== null)             $match->setDescription($description);
        elseif ($effacerDescription)           $match->setDescription(null);

        $this->em->flush();

        return $match;
    }

    public function supprimer(Game $match): void
    {
        // A4 — Statut
        if (in_array($match->getStatut(), [StatutGame::Termine, StatutGame::Annule], true)) {
            $libelle = $match->getStatut() === StatutGame::Termine ? 'terminé' : 'annulé';
            throw new \InvalidArgumentException("Impossible de supprimer ce match : il est déjà {$libelle}.");
        }

        $this->em->remove($match);
        $this->em->flush();
    }

    public function creerCamp(
        Game $match,
        ?int $equipeId,
        ?int $joueurId,
        Utilisateur $acteur,
        bool $confirmerInscription = true,
    ): MatchCamp {
        // R5 — validation stricte : XOR équipe/joueur
        if ($equipeId !== null && $joueurId !== null) {
            throw new \InvalidArgumentException(
                'Fournissez soit "equipeId" (sport collectif) soit "joueurId" (sport individuel), pas les deux.'
            );
        }
        if ($equipeId === null && $joueurId === null) {
            throw new \InvalidArgumentException(
                'Fournissez soit "equipeId" (sport collectif) soit "joueurId" (sport individuel).'
            );
        }

        // A1 — Statut et date
        if ($match->getStatut() !== StatutGame::EnAttente) {
            throw new \InvalidArgumentException(
                'Impossible de rejoindre ce match : il n\'est plus en attente de participants.'
            );
        }
        if ($match->getDateMatch() <= new \DateTime()) {
            throw new \InvalidArgumentException(
                'Impossible de rejoindre ce match : la date est déjà passée.'
            );
        }

        $typeSport = $match->getSport()->getType();

        if ($equipeId !== null && $typeSport === TypeSport::Individuel) {
            throw new \InvalidArgumentException(
                'Ce match est de sport individuel : fournissez "joueurId", pas "equipeId".'
            );
        }
        if ($joueurId !== null && $typeSport === TypeSport::Collectif) {
            throw new \InvalidArgumentException(
                'Ce match oppose des équipes : inscrivez une équipe (foot, rugby, volley…), pas un joueur seul.'
            );
        }

        if ($equipeId !== null && $acteur->getType() !== TypeUtilisateur::Club) {
            throw new \InvalidArgumentException(
                'Seul un compte club peut inscrire une équipe à un match collectif.'
            );
        }

        if (count($match->getCamps()) >= 2) {
            throw new \InvalidArgumentException('Ce match a déjà deux camps inscrits.');
        }

        $role = count($match->getCamps()) === 0 ? RoleMatchCamp::Camp1 : RoleMatchCamp::Camp2;

        $camp = new MatchCamp();
        $camp->setRole($role);
        // Rejoindre volontairement = confirmé ; invitation d'un tiers = invité
        $camp->setStatut($confirmerInscription ? StatutMatchCamp::Confirme : StatutMatchCamp::Invite);

        if ($equipeId !== null) {
            $equipe = $this->equipeRepository->find($equipeId);
            if (!$equipe) {
                throw new \InvalidArgumentException('Équipe introuvable.');
            }
            // Vérifier que l'équipe joue bien ce sport
            if ($equipe->getSport() !== $match->getSport()) {
                throw new \InvalidArgumentException(
                    sprintf('L\'équipe pratique "%s", le match est de "%s".',
                        $equipe->getSport()?->getNom(),
                        $match->getSport()->getNom()
                    )
                );
            }
            // Vérifier que l'équipe n'est pas déjà inscrite
            foreach ($match->getCamps() as $c) {
                if ($c->getEquipe() === $equipe) {
                    throw new \InvalidArgumentException('Cette équipe est déjà inscrite à ce match.');
                }
            }
            if ($equipe->getClub() !== $acteur) {
                throw new \InvalidArgumentException('Vous ne pouvez inscrire que vos propres équipes.');
            }
            $camp->setEquipe($equipe);
        } else {
            $joueur = $this->utilisateurRepository->find($joueurId);
            if (!$joueur) {
                throw new \InvalidArgumentException('Joueur introuvable.');
            }
            // Vérifier que le joueur n'est pas déjà inscrit
            foreach ($match->getCamps() as $c) {
                if ($c->getJoueur() === $joueur) {
                    throw new \InvalidArgumentException('Ce joueur est déjà inscrit à ce match.');
                }
            }
            if ($joueur !== $acteur) {
                throw new \InvalidArgumentException('Vous ne pouvez inscrire que vous-même à un match individuel.');
            }
            $camp->setJoueur($joueur);
        }

        $match->addCamp($camp);
        $this->em->persist($camp);
        $this->actualiserStatutMatch($match);
        $this->em->flush();

        return $camp;
    }

    public function repondreInvitation(MatchCamp $camp, StatutMatchCamp $nouveauStatut): MatchCamp
    {
        if ($nouveauStatut === StatutMatchCamp::Invite) {
            throw new \InvalidArgumentException(
                'Statut invalide. Valeurs acceptées : confirme, refuse.'
            );
        }

        $camp->setStatut($nouveauStatut);
        $this->actualiserStatutMatch($camp->getGame());
        $this->em->flush();

        return $camp;
    }

    /** R2 — le match passe en « confirmé » quand les 2 inscriptions sont confirmées. */
    private function actualiserStatutMatch(Game $match): void
    {
        if ($match->getStatut() === StatutGame::Termine || $match->getStatut() === StatutGame::Annule) {
            return;
        }

        if (count($match->getCamps()) < 2) {
            if ($match->getStatut() === StatutGame::Confirme) {
                $match->setStatut(StatutGame::EnAttente);
            }

            return;
        }

        foreach ($match->getCamps() as $c) {
            if ($c->getStatut() !== StatutMatchCamp::Confirme) {
                if ($match->getStatut() === StatutGame::Confirme) {
                    $match->setStatut(StatutGame::EnAttente);
                }

                return;
            }
        }

        $match->setStatut(StatutGame::Confirme);
    }

    public function supprimerCamp(MatchCamp $camp): void
    {
        $match = $camp->getGame();

        // A2 — Statut et date
        if (in_array($match->getStatut(), [StatutGame::Termine, StatutGame::Annule], true)) {
            $libelle = $match->getStatut() === StatutGame::Termine ? 'terminé' : 'annulé';
            throw new \InvalidArgumentException("Impossible de quitter ce match : il est déjà {$libelle}.");
        }
        if ($match->getDateMatch() <= new \DateTime()) {
            throw new \InvalidArgumentException('Impossible de quitter ce match : la date est déjà passée.');
        }

        $this->em->remove($camp);
        $this->actualiserStatutMatch($match);
        $this->em->flush();
    }

    public function peutQuitterCamp(MatchCamp $camp, Utilisateur $utilisateur): bool
    {
        if ($camp->getJoueur() === $utilisateur) {
            return true;
        }

        $equipe = $camp->getEquipe();

        return $equipe !== null && $equipe->getClub() === $utilisateur;
    }

    // -------------------------------------------------------------------------
    // Messages
    // -------------------------------------------------------------------------

    public function envoyerMessage(Game $match, Utilisateur $expediteur, string $contenu): Message
    {
        $message = new Message();
        $message->setGame($match);
        $message->setExpediteur($expediteur);
        $message->setContenu($contenu);
        $message->setDateEnvoi(new \DateTime());

        $this->em->persist($message);
        $this->em->flush();

        return $message;
    }

    // -------------------------------------------------------------------------
    // Clôture automatique (à la volée + commande)
    // -------------------------------------------------------------------------

    /**
     * Transite le match vers annulé ou terminé si sa date est passée.
     * Pas de flush — à appeler en boucle, flush groupé à la charge de l'appelant.
     *
     * @return bool true si le statut a changé
     */
    public function cloturerSiExpire(Game $match): bool
    {
        if ($match->getDateMatch() > new \DateTime()) {
            return false;
        }

        if ($match->getStatut() === StatutGame::EnAttente) {
            $match->setStatut(StatutGame::Annule);
            return true;
        }

        if ($match->getStatut() === StatutGame::Confirme) {
            $match->setStatut(StatutGame::Termine);
            return true;
        }

        return false;
    }

    /**
     * Clôture en batch une liste de matchs : un seul flush() groupé si nécessaire.
     *
     * @param  Game[] $matchs
     * @return int    nombre de matchs dont le statut a changé
     */
    public function cloturerMatchsExpires(array $matchs): int
    {
        $count = 0;

        foreach ($matchs as $match) {
            if ($this->cloturerSiExpire($match)) {
                $count++;
            }
        }

        if ($count > 0) {
            $this->em->flush();
        }

        return $count;
    }

    // -------------------------------------------------------------------------
    // Résultat (R3)
    // -------------------------------------------------------------------------

    public function saisirResultat(Game $match, int $scoreCamp1, int $scoreCamp2): Resultat
    {
        // R3 — le match doit être clôturé par la commande avant toute saisie
        if ($match->getStatut() !== StatutGame::Termine) {
            throw new \InvalidArgumentException(
                'Le résultat ne peut être saisi que sur un match clôturé (statut "terminé"). '
                . 'La clôture est effectuée automatiquement après la date du match.'
            );
        }

        $this->validerScoresResultat($match, $scoreCamp1, $scoreCamp2);

        // R3a — 2 camps confirmés (défense en profondeur)
        $campsConfirmes = 0;
        foreach ($match->getCamps() as $camp) {
            if ($camp->getStatut() === StatutMatchCamp::Confirme) {
                $campsConfirmes++;
            }
        }
        if ($campsConfirmes < 2) {
            throw new \InvalidArgumentException(
                'Le résultat ne peut être saisi que si les 2 camps sont confirmés (R3).'
            );
        }

        // R3b — dateMatch passée (défense en profondeur)
        if ($match->getDateMatch() > new \DateTime()) {
            throw new \InvalidArgumentException(
                'Le résultat ne peut être saisi qu\'après la date du match (R3).'
            );
        }

        // R3c — pas de résultat existant
        if ($match->getResultat()) {
            throw new \InvalidArgumentException('Ce match a déjà un résultat.');
        }

        $resultat = new Resultat();
        $resultat->setGame($match);
        $resultat->setScoreCamp1($scoreCamp1);
        $resultat->setScoreCamp2($scoreCamp2);

        $this->em->persist($resultat);
        $this->em->flush();

        return $resultat;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function validerScoresResultat(Game $match, int $scoreCamp1, int $scoreCamp2): void
    {
        foreach ([$scoreCamp1, $scoreCamp2] as $score) {
            if ($score < 0) {
                throw new \InvalidArgumentException(
                    'Les scores doivent être supérieurs ou égaux à 0.'
                );
            }

            $collectif = $match->getSport()->getType() === TypeSport::Collectif;

            if (!$collectif && $score > 5) {
                throw new \InvalidArgumentException(
                    'Le score d\'un sport individuel doit être entre 0 et 5.'
                );
            }

            if ($collectif && $score > 200) {
                throw new \InvalidArgumentException(
                    'Le score d\'un sport collectif doit être entre 0 et 200.'
                );
            }
        }
    }

    /**
     * Vérifie si l'utilisateur peut saisir le résultat du match :
     * le créateur, le joueur d'un camp (individuel) ou le club de l'équipe d'un camp (collectif).
     */
    public function peutSaisirResultat(Game $match, Utilisateur $utilisateur): bool
    {
        if ($match->getCreateur() === $utilisateur) {
            return true;
        }

        foreach ($match->getCamps() as $camp) {
            if ($camp->getJoueur() === $utilisateur) {
                return true;
            }
            if ($camp->getEquipe()?->getClub() === $utilisateur) {
                return true;
            }
        }

        return false;
    }

    public function estParticipant(Game $match, Utilisateur $utilisateur): bool
    {
        if ($match->getCreateur() === $utilisateur) {
            return true;
        }

        foreach ($match->getCamps() as $camp) {
            if ($camp->getStatut() !== StatutMatchCamp::Confirme) {
                continue;
            }
            // Sport individuel : le joueur direct
            if ($camp->getJoueur() === $utilisateur) {
                return true;
            }
            // Sport collectif : membre confirmé de l'équipe
            if ($camp->getEquipe() !== null) {
                foreach ($camp->getEquipe()->getMembres() as $membre) {
                    if ($membre->getUtilisateur() === $utilisateur
                        && $membre->getStatut() === \App\Enum\StatutMembreEquipe::Confirme) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
