<?php

namespace App\Enum;

enum OrigineMembreEquipe: string
{
    case InvitationClub = 'invitation_club';
    case DemandeJoueur  = 'demande_joueur';
}
