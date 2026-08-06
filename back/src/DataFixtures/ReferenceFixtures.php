<?php

namespace App\DataFixtures;

use App\Entity\Niveau;
use App\Entity\Sport;
use App\Enum\TypeSport;
use App\Reference\SportNiveaux;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Charge uniquement les sports et leurs niveaux réels depuis SportNiveaux::CATALOGUE.
 *
 * Groupe "reference" — utilisé en production pour peupler le référentiel
 * au premier démarrage, sans aucune donnée de démo.
 *
 * AppFixtures (groupe "dev") recrée les mêmes sports de son côté depuis la même
 * source : les deux classes coexistent sans se gêner et restent automatiquement
 * cohérentes tant que CATALOGUE est la référence commune.
 */
class ReferenceFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['reference'];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (SportNiveaux::CATALOGUE as $nom => $cfg) {
            $sport = new Sport();
            $sport->setNom($nom);
            $sport->setType(TypeSport::from($cfg['type']));

            foreach ($cfg['niveaux'] as $ordre => $libelle) {
                $niveau = new Niveau();
                $niveau->setLibelle($libelle);
                $niveau->setOrdre($ordre + 1);
                $sport->addNiveau($niveau);
                $manager->persist($niveau);
            }

            $manager->persist($sport);
        }

        $manager->flush();
    }
}
