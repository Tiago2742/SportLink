<?php

namespace App\Enum;

enum StatutGame: string
{
    case EnAttente = 'en_attente';
    case Confirme  = 'confirme';
    case Termine   = 'termine';
    case Annule    = 'annule';
}
