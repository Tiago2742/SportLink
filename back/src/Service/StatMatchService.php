<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\Utilisateur;
use App\Enum\RoleMatchCamp;
use App\Enum\StatutMembreEquipe;
use App\Repository\GameRepository;

class StatMatchService
{
    public function __construct(private GameRepository $gameRepository) {}

    /**
     * Stats pour les matchs où l'utilisateur est directement impliqué
     * (créateur, joueur d'un camp individuel, ou club d'une équipe en camp).
     */
    public function calculerPourParticipant(Utilisateur $user): array
    {
        $games = $this->gameRepository->trouverTerminesAvecResultatPourParticipant($user->getId());
        return $this->calculerStats(
            $games,
            fn(Game $g) => $this->trouverRolePourParticipant($g, $user),
        );
    }

    /**
     * Stats pour les matchs des équipes dont l'utilisateur est membre confirmé.
     */
    public function calculerPourEquipesJoueur(Utilisateur $user): array
    {
        $games = $this->gameRepository->trouverTerminesAvecResultatPourEquipesJoueur($user->getId());
        return $this->calculerStats(
            $games,
            fn(Game $g) => $this->trouverRolePourEquipeMembre($g, $user),
        );
    }

    // -------------------------------------------------------------------------
    // Logique commune
    // -------------------------------------------------------------------------

    /** @param Game[] $games Déjà filtrés : terminés et ayant un résultat (via la requête SQL). */
    private function calculerStats(array $games, callable $trouverRole): array
    {
        $joues = $victoires = $defaites = $nuls = 0;

        foreach ($games as $game) {
            $resultat = $game->getResultat();
            if ($resultat === null) {
                continue; // garde défensive (ne devrait pas arriver avec INNER JOIN)
            }

            $role = $trouverRole($game);
            if ($role === null) {
                continue;
            }

            $monScore  = $role === RoleMatchCamp::Camp1
                ? $resultat->getScoreCamp1()
                : $resultat->getScoreCamp2();
            $leurScore = $role === RoleMatchCamp::Camp1
                ? $resultat->getScoreCamp2()
                : $resultat->getScoreCamp1();

            $joues++;
            if ($monScore > $leurScore) {
                $victoires++;
            } elseif ($monScore === $leurScore) {
                $nuls++;
            } else {
                $defaites++;
            }
        }

        return [
            'joues'     => $joues,
            'victoires' => $victoires,
            'defaites'  => $defaites,
            'nuls'      => $nuls,
            'ratio'     => $joues > 0 ? round($victoires / $joues * 100, 1) : 0.0,
        ];
    }

    // -------------------------------------------------------------------------
    // Identification du camp de l'utilisateur
    // -------------------------------------------------------------------------

    private function trouverRolePourParticipant(Game $game, Utilisateur $user): ?RoleMatchCamp
    {
        foreach ($game->getCamps() as $camp) {
            if ($camp->getJoueur()?->getId() === $user->getId()) {
                return $camp->getRole();
            }
            if ($camp->getEquipe()?->getClub()?->getId() === $user->getId()) {
                return $camp->getRole();
            }
        }

        return null;
    }

    private function trouverRolePourEquipeMembre(Game $game, Utilisateur $user): ?RoleMatchCamp
    {
        foreach ($game->getCamps() as $camp) {
            if ($camp->getEquipe() === null) {
                continue;
            }
            foreach ($camp->getEquipe()->getMembres() as $membre) {
                if ($membre->getUtilisateur()?->getId() === $user->getId()
                    && $membre->getStatut() === StatutMembreEquipe::Confirme) {
                    return $camp->getRole();
                }
            }
        }

        return null;
    }
}
