<?php

namespace App\Reference;

class SportNiveaux
{
    public const CATALOGUE = [
        'Badminton' => [
            'type'    => 'individuel',
            'niveaux' => ['Loisir', 'Débutant', 'Intermédiaire', 'Confirmé', 'Expert', 'Série nationale'],
        ],
        'Basketball' => [
            'type'    => 'collectif',
            'niveaux' => ['Loisir', 'Départementale', 'Régionale', 'N3', 'N2', 'N1', 'Pro B', 'Pro A'],
        ],
        'Football' => [
            'type'    => 'collectif',
            'niveaux' => ['Amateur', 'D6', 'D5', 'D4', 'D3', 'D2', 'D1', 'R3', 'R2', 'R1', 'N3', 'N2', 'N1', 'L2', 'L1'],
        ],
        'Handball' => [
            'type'    => 'collectif',
            'niveaux' => ['Loisir', 'Départementale', 'Régionale', 'N3', 'N2', 'N1', 'Pro D2', 'Starligue'],
        ],
        'Rugby' => [
            'type'    => 'collectif',
            'niveaux' => ['Loisir', 'Fédérale 3', 'Fédérale 2', 'Fédérale 1', 'Pro D2', 'Top 14'],
        ],
        'Tennis' => [
            'type'    => 'individuel',
            'niveaux' => ['Non classé', '40', '30/5', '30/4', '30/3', '30/2', '30/1', '30', '15/5', '15/4', '15/3', '15/2', '15/1', '15', '4/6', '3/6', '2/6', '1/6'],
        ],
        'Volleyball' => [
            'type'    => 'collectif',
            'niveaux' => ['Loisir', 'Départementale', 'Régionale', 'Nationale', 'Ligue A'],
        ],
    ];

    public static function getSports(): array
    {
        return array_keys(self::CATALOGUE);
    }

    public static function getNiveaux(string $sport): array
    {
        return self::CATALOGUE[$sport]['niveaux'] ?? [];
    }

    public static function getType(string $sport): string
    {
        return self::CATALOGUE[$sport]['type'] ?? 'collectif';
    }

    public static function estValide(string $sport, string $niveau): bool
    {
        return in_array($niveau, self::CATALOGUE[$sport]['niveaux'] ?? [], true);
    }
}
