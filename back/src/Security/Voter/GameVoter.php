<?php

namespace App\Security\Voter;

use App\Entity\Game;
use App\Entity\MatchCamp;
use App\Entity\Utilisateur;
use App\Service\MatchService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class GameVoter extends Voter
{
    public const MODIFIER        = 'GAME_MODIFIER';
    public const SUPPRIMER       = 'GAME_SUPPRIMER';
    public const SAISIR_RESULTAT = 'GAME_SAISIR_RESULTAT';
    public const REPONDRE_CAMP   = 'GAME_REPONDRE_CAMP';

    public function __construct(
        private MatchService $matchService,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return match ($attribute) {
            self::MODIFIER, self::SUPPRIMER, self::SAISIR_RESULTAT => $subject instanceof Game,
            self::REPONDRE_CAMP                                    => $subject instanceof MatchCamp,
            default                                                => false,
        };
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $utilisateur = $token->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            return false;
        }

        return match ($attribute) {
            self::MODIFIER, self::SUPPRIMER => $subject->getCreateur() === $utilisateur,
            self::SAISIR_RESULTAT          => $this->matchService->peutSaisirResultat($subject, $utilisateur),
            self::REPONDRE_CAMP            => $subject->getJoueur() === $utilisateur
                                              || $subject->getEquipe()?->getClub() === $utilisateur,
            default                        => false,
        };
    }
}
