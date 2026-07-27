<?php

namespace App\DataFixtures;

use App\Entity\Niveau;
use App\Entity\Sport;
use App\Enum\TypeSport;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class TestFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['test'];
    }

    public function load(ObjectManager $manager): void
    {
        $this->creerSport($manager, 'Football', TypeSport::Collectif, [
            ['Débutant', 1],
            ['Confirmé', 2],
        ]);

        $this->creerSport($manager, 'Tennis', TypeSport::Individuel, [
            ['Initiation', 1],
            ['Avancé', 2],
        ]);

        $manager->flush();
    }

    private function creerSport(ObjectManager $manager, string $nom, TypeSport $type, array $niveaux): void
    {
        $sport = new Sport();
        $sport->setNom($nom);
        $sport->setType($type);
        $manager->persist($sport);

        foreach ($niveaux as [$libelle, $ordre]) {
            $niveau = new Niveau();
            $niveau->setLibelle($libelle);
            $niveau->setOrdre($ordre);
            $niveau->setSport($sport);
            $manager->persist($niveau);
        }
    }
}
