<?php

namespace App\DataFixtures;

use App\Entity\Utilisateur;
use App\Enum\TypeUtilisateur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class E2eFixtures extends Fixture implements FixtureGroupInterface
{
    public const JOUEUR_EMAIL = 'e2e-joueur@sportlink.test';
    public const CLUB_EMAIL   = 'e2e-club@sportlink.test';
    public const PASSWORD     = 'Test1234!';

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {}

    public static function getGroups(): array
    {
        return ['e2e'];
    }

    public function load(ObjectManager $manager): void
    {
        $joueur = new Utilisateur();
        $joueur->setEmail(self::JOUEUR_EMAIL);
        $joueur->setPassword($this->hasher->hashPassword($joueur, self::PASSWORD));
        $joueur->setPrenom('Alex');
        $joueur->setNom('Dupont');
        $joueur->setType(TypeUtilisateur::Joueur);
        $joueur->setDateInscription(new \DateTime());
        $manager->persist($joueur);

        $club = new Utilisateur();
        $club->setEmail(self::CLUB_EMAIL);
        $club->setPassword($this->hasher->hashPassword($club, self::PASSWORD));
        $club->setPrenom(null);
        $club->setNom('FC Test Club');
        $club->setType(TypeUtilisateur::Club);
        $club->setDateInscription(new \DateTime());
        $manager->persist($club);

        $manager->flush();
    }
}
