<?php

namespace App\Service;

use App\Entity\Avis;
use App\Entity\Game;
use App\Entity\MatchCamp;
use App\Entity\Utilisateur;
use App\Enum\StatutGame;
use App\Repository\AvisRepository;
use Doctrine\ORM\EntityManagerInterface;

class AvisService
{
    public function __construct(
        private EntityManagerInterface $em,
        private AvisRepository         $avisRepository,
    ) {}

    /**
     * Dépose un avis du notant sur son adversaire dans le match.
     * La cible est déterminée automatiquement depuis les camps — elle n'est jamais fournie par le client.
     *
     * @throws \InvalidArgumentException si une règle métier est violée (→ 422 côté contrôleur)
     */
    public function deposerAvis(
        Game $game,
        Utilisateur $notant,
        int $ponctualite,
        int $fairPlay,
        int $niveauConforme,
    ): Avis {
        // R1 — match terminé
        if ($game->getStatut() !== StatutGame::Termine) {
            throw new \InvalidArgumentException(
                'La notation n\'est possible qu\'après un match terminé.'
            );
        }

        // R2 — plage des notes
        foreach (['Ponctualité' => $ponctualite, 'Fair-play' => $fairPlay, 'Niveau conforme' => $niveauConforme] as $libelle => $note) {
            if ($note < 1 || $note > 5) {
                throw new \InvalidArgumentException(
                    sprintf('%s doit être notée entre 1 et 5.', $libelle)
                );
            }
        }

        // R3 — notant est dans un camp
        $campNotant = $this->trouverCamp($game, $notant);
        if ($campNotant === null) {
            throw new \InvalidArgumentException(
                'Vous n\'êtes pas participant de ce match.'
            );
        }

        // R4 — identifier le camp adverse et en déduire l'évalué
        $campAdverse = $this->trouverCampAdverse($game, $campNotant);
        if ($campAdverse === null) {
            throw new \InvalidArgumentException(
                'Aucun adversaire identifiable pour ce match.'
            );
        }

        $evalue = $this->extraireOccupant($campAdverse);
        if ($evalue === null) {
            throw new \InvalidArgumentException(
                'Impossible de déterminer l\'adversaire à évaluer.'
            );
        }

        // R5 — auto-notation (garde défensive)
        if ($notant === $evalue) {
            throw new \InvalidArgumentException(
                'Vous ne pouvez pas vous auto-noter.'
            );
        }

        // R6 — doublon
        if ($this->avisRepository->findParNotantEtGame($notant, $game) !== null) {
            throw new \InvalidArgumentException(
                'Vous avez déjà évalué ce match.'
            );
        }

        $avis = new Avis();
        $avis->setNotant($notant);
        $avis->setEvalue($evalue);
        $avis->setGame($game);
        $avis->setPonctualite($ponctualite);
        $avis->setFairPlay($fairPlay);
        $avis->setNiveauConforme($niveauConforme);
        $avis->setDateCreation(new \DateTimeImmutable());

        $this->em->persist($avis);
        $this->em->flush();

        return $avis;
    }

    // -------------------------------------------------------------------------
    // Helpers privés
    // -------------------------------------------------------------------------

    /**
     * Retourne le camp du match occupé par cet utilisateur
     * (joueur direct pour sport individuel, club de l'équipe pour sport collectif).
     */
    private function trouverCamp(Game $game, Utilisateur $utilisateur): ?MatchCamp
    {
        foreach ($game->getCamps() as $camp) {
            if ($camp->getJoueur() === $utilisateur) {
                return $camp;
            }
            if ($camp->getEquipe()?->getClub() === $utilisateur) {
                return $camp;
            }
        }

        return null;
    }

    /** Retourne l'autre camp (pas celui du notant). */
    private function trouverCampAdverse(Game $game, MatchCamp $campNotant): ?MatchCamp
    {
        foreach ($game->getCamps() as $camp) {
            if ($camp !== $campNotant) {
                return $camp;
            }
        }

        return null;
    }

    /**
     * Extrait l'Utilisateur occupant d'un camp :
     * - sport individuel : le joueur direct
     * - sport collectif  : le club gestionnaire de l'équipe
     */
    private function extraireOccupant(MatchCamp $camp): ?Utilisateur
    {
        if ($camp->getJoueur() !== null) {
            return $camp->getJoueur();
        }

        return $camp->getEquipe()?->getClub();
    }
}
