<?php

namespace App\Enum;

enum StatutMatchCamp: string
{
    case Invite   = 'invite';
    case Confirme = 'confirme';
    case Refuse   = 'refuse';
}
