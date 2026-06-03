<?php

namespace App\Enum;

enum StatutMembreEquipe: string
{
    case EnAttente = 'en_attente';
    case Confirme  = 'confirme';
    case Refuse    = 'refuse';
}
