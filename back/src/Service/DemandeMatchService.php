<?php

namespace App\Service;

use App\Entity\DemandeMatch;
use App\Entity\Game;
use App\Entity\MatchCamp;
use App\Entity\Utilisateur;
use App\Enum\RoleMatchCamp;
use App\Enum\StatutDemande;
use App\Enum\StatutGame;
use App\Enum\StatutMatchCamp;
use App\Enum\TypeSport;
use App\Exception\DemandeEnAttenteException;
use App\Repository\DemandeMatchRepository;
use App\Repository\EquipeRepository;
use App\Repository\UtilisateurNiveauRepository;
use Doctrine\ORM\EntityManagerInterface;

class DemandeMatchService
{
    public function __construct(
        private EntityManagerInterface      $em,
        private DemandeMatchRepository      $demandeMatchRepository,
        private UtilisateurNiveauRepository $utilisateurNiveauRepository,
        private EquipeRepository            $equipeRepository,
    ) {}

    // -------------------------------------------------------------------------

    public function demander(Game $game, Utilisateur $demandeur, ?int $equipeId): DemandeMatch
    {
        // 1. Match encore ouvert
        if ($game->getStatut() !== StatutGame::EnAttente) {
            throw new \InvalidArgumentException('Le match n\'est plus ouvert aux demandes.');
        }

        // 2. Place disponible (camp_2 non occupé)
        if ($game->getCamps()->count() >= 2) {
            throw new \InvalidArgumentException('Ce match est déjà complet.');
        }

        // 3. Sport déclaré dans le profil du demandeur
        $sportIds = $this->utilisateurNiveauRepository->findSportIdsByUtilisateur($demandeur->getId());
        if (!in_array($game->getSport()->getId(), $sportIds, true)) {
            throw new \InvalidArgumentException('Vous n\'avez pas déclaré ce sport dans votre profil.');
        }

        // 4. Cohérence type sport / equipeId
        $equipe = null;
        if ($game->getSport()->getType() === TypeSport::Collectif) {
            if ($equipeId === null) {
                throw new \InvalidArgumentException('Fournissez votre equipeId pour un match collectif.');
            }
            $equipe = $this->equipeRepository->find($equipeId);
            if (!$equipe) {
                throw new \InvalidArgumentException('Équipe introuvable.');
            }
            if ($equipe->getClub() !== $demandeur) {
                throw new \InvalidArgumentException('Vous ne pouvez engager que vos propres équipes.');
            }
            if ($equipe->getSport() !== $game->getSport()) {
                throw new \InvalidArgumentException(
                    sprintf('Cette équipe pratique "%s", pas le sport de ce match.', $equipe->getSport()?->getNom())
                );
            }
        } else {
            if ($equipeId !== null) {
                throw new \InvalidArgumentException('Un match individuel ne nécessite pas d\'equipeId.');
            }
        }

        // 5. Find-or-reactivate (unicité UNIQUE(game_id, demandeur_id))
        $existante = $this->demandeMatchRepository->findExistante($game, $demandeur);

        if ($existante !== null) {
            if ($existante->getStatut() === StatutDemande::EnAttente) {
                throw new DemandeEnAttenteException('Vous avez déjà une demande en attente sur ce match.');
            }
            // Réactivation après refus ou annulation
            $existante->setStatut(StatutDemande::EnAttente);
            $existante->setDateCreation(new \DateTimeImmutable());
            $existante->setEquipe($equipe);
            $this->em->flush();
            return $existante;
        }

        $demande = new DemandeMatch();
        $demande->setGame($game);
        $demande->setDemandeur($demandeur);
        $demande->setEquipe($equipe);
        $this->em->persist($demande);
        $this->em->flush();

        return $demande;
    }

    // -------------------------------------------------------------------------

    public function accepter(DemandeMatch $demande): void
    {
        if ($demande->getStatut() !== StatutDemande::EnAttente) {
            throw new \InvalidArgumentException('Cette demande n\'est plus en attente.');
        }

        $game = $demande->getGame();

        $camp = new MatchCamp();
        $camp->setRole(RoleMatchCamp::Camp2);
        $camp->setStatut(StatutMatchCamp::Confirme);

        if ($game->getSport()->getType() === TypeSport::Individuel) {
            $camp->setJoueur($demande->getDemandeur());
        } else {
            $camp->setEquipe($demande->getEquipe());
        }

        $game->addCamp($camp);
        $this->em->persist($camp);

        $demande->setStatut(StatutDemande::Acceptee);

        // R2 — camp_1 est toujours confirme, le match est systématiquement confirmé ici
        $game->setStatut(StatutGame::Confirme);

        // Purger les autres demandes en attente sur ce match
        $this->demandeMatchRepository->refuserToutesEnAttente($game);

        $this->em->flush();
    }

    // -------------------------------------------------------------------------

    public function refuser(DemandeMatch $demande): void
    {
        if ($demande->getStatut() !== StatutDemande::EnAttente) {
            throw new \InvalidArgumentException('Cette demande n\'est plus en attente.');
        }

        $demande->setStatut(StatutDemande::Refusee);
        $this->em->flush();
    }

    // -------------------------------------------------------------------------

    public function annuler(DemandeMatch $demande): void
    {
        if ($demande->getStatut() !== StatutDemande::EnAttente) {
            throw new \InvalidArgumentException('Impossible d\'annuler : la demande n\'est plus en attente.');
        }

        $demande->setStatut(StatutDemande::Annulee);
        $this->em->flush();
    }
}
