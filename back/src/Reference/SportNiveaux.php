<?php

namespace App\Reference;

class SportNiveaux
{
    public const CATALOGUE = [
        'Football' => [
            'Amateur',
            'R3 — Régional 3',
            'R2 — Régional 2',
            'R1 — Régional 1',
            'D2 — Division 2',
            'D1 — Division 1',
        ],
        'Tennis' => [
            'Non classé',
            '40',
            '30/5',
            '30/4',
            '30/3',
            '30/2',
            '30/1',
            '30',
            '15/5',
            '15/4',
            '15/3',
            '15/2',
            '15/1',
            '15',
            '4/6',
            '3/6',
            '2/6',
            '1/6',
        ],
        'Basketball' => [
            'Loisir',
            'Départementale',
            'Régionale',
            'Nationale 3',
            'Nationale 2',
            'Nationale 1',
            'Pro B',
            'Pro A',
        ],
        'Volleyball' => [
            'Loisir',
            'Départementale',
            'Régionale',
            'Nationale',
            'Ligue A',
        ],
        'Rugby' => [
            'Loisir',
            'Fédérale 3',
            'Fédérale 2',
            'Fédérale 1',
            'Pro D2',
            'Top 14',
        ],
        'Handball' => [
            'Loisir',
            'Départementale',
            'Régionale',
            'Nationale 3',
            'Nationale 2',
            'Nationale 1',
            'Pro D2',
            'Starligue',
        ],
        'Badminton' => [
            'Loisir',
            'Débutant',
            'Intermédiaire',
            'Confirmé',
            'Expert',
            'Série nationale',
        ],
        'Natation' => [
            'Loisir',
            'Débutant',
            'Intermédiaire',
            'Confirmé',
            'Régional',
            'National',
        ],
    ];

    public static function getSports(): array
    {
        return array_keys(self::CATALOGUE);
    }

    public static function getNiveaux(string $sport): array
    {
        return self::CATALOGUE[$sport] ?? [];
    }

    public static function estValide(string $sport, string $niveau): bool
    {
        return in_array($niveau, self::CATALOGUE[$sport] ?? [], true);
    }
}
