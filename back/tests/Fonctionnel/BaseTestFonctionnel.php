<?php

namespace App\Tests\Fonctionnel;

use App\Entity\Equipe;
use App\Entity\Game;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class BaseTestFonctionnel extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get('doctrine.orm.entity_manager');
    }

    protected function creerUtilisateur(
        string $email = 'utilisateur@test.fr',
        string $mdp = 'Test1234!',
        string $nom = 'Durand',
        string $prenom = 'Marie',
        string $type = 'joueur',
    ): Utilisateur {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $utilisateur = new Utilisateur();
        $utilisateur->setEmail($email);
        $utilisateur->setPassword($hasher->hashPassword($utilisateur, $mdp));
        $utilisateur->setNom($nom);
        $utilisateur->setPrenom($prenom);
        $utilisateur->setType($type);
        $utilisateur->setDateInscription(new \DateTime());

        $this->em->persist($utilisateur);
        $this->em->flush();

        return $utilisateur;
    }

    protected function obtenirToken(Utilisateur $utilisateur): string
    {
        $jwtManager = static::getContainer()->get('lexik_jwt_authentication.jwt_manager');

        return $jwtManager->create($utilisateur);
    }

    protected function creerEquipe(Utilisateur $createur, string $nom = 'Les Aigles', string $sport = 'football'): Equipe
    {
        $equipe = new Equipe();
        $equipe->setNom($nom);
        $equipe->setSport($sport);
        $equipe->setCreateur($createur);

        $this->em->persist($equipe);
        $this->em->flush();

        return $equipe;
    }

    protected function creerMatch(Utilisateur $createur, string $sport = 'football'): Game
    {
        $match = new Game();
        $match->setSport($sport);
        $match->setDateMatch(new \DateTime('2026-09-01 18:00'));
        $match->setLieu('Terrain municipal');
        $match->setStatut('ouvert');
        $match->setCreateur($createur);

        $this->em->persist($match);
        $this->em->flush();

        return $match;
    }

    protected function requeteAuth(string $methode, string $url, string $token, ?array $donnees = null): void
    {
        $this->client->request(
            $methode,
            $url,
            [],
            [],
            [
                'CONTENT_TYPE'      => 'application/json',
                'HTTP_AUTHORIZATION' => "Bearer $token",
            ],
            $donnees !== null ? json_encode($donnees) : null,
        );
    }

    protected function requete(string $methode, string $url, ?array $donnees = null): void
    {
        $this->client->request(
            $methode,
            $url,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $donnees !== null ? json_encode($donnees) : null,
        );
    }

    protected function reponseJson(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true) ?? [];
    }

    protected function assertStatut(int $statut): void
    {
        $this->assertEquals($statut, $this->client->getResponse()->getStatusCode());
    }
}
