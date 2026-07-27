<?php

namespace App\Tests\Fonctionnel;

use App\Entity\Equipe;
use App\Entity\Game;
use App\Entity\MatchCamp;
use App\Entity\Niveau;
use App\Entity\Sport;
use App\Entity\Utilisateur;
use App\Entity\UtilisateurNiveau;
use App\Enum\RoleMatchCamp;
use App\Enum\StatutGame;
use App\Enum\StatutMatchCamp;
use App\Enum\TypeSport;
use App\Enum\TypeUtilisateur;
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
        $this->em     = static::getContainer()->get('doctrine.orm.entity_manager');
    }

    // ---- Référence sport/niveau (depuis TestFixtures) -------------------------

    /**
     * Trouve un sport par nom depuis la base de test.
     * Lance une exception claire si TestFixtures n'ont pas été chargées.
     */
    protected function getSport(string $nom = 'Football'): Sport
    {
        $sport = $this->em->getRepository(Sport::class)->findOneBy(['nom' => $nom]);
        if (!$sport) {
            throw new \RuntimeException(
                "Sport '$nom' introuvable — avez-vous chargé TestFixtures ?\n" .
                "  docker compose exec php php bin/console doctrine:fixtures:load --env=test --group=test --no-interaction"
            );
        }
        return $sport;
    }

    protected function getNiveau(string $libelle, Sport $sport): Niveau
    {
        $niveau = $this->em->getRepository(Niveau::class)->findOneBy(['libelle' => $libelle, 'sport' => $sport]);
        if (!$niveau) {
            throw new \RuntimeException("Niveau '$libelle' introuvable pour '{$sport->getNom()}'.");
        }
        return $niveau;
    }

    protected function getPremierNiveau(Sport $sport): Niveau
    {
        $niveau = $this->em->getRepository(Niveau::class)->findOneBy(['sport' => $sport], ['ordre' => 'ASC']);
        if (!$niveau) {
            throw new \RuntimeException("Aucun niveau trouvé pour '{$sport->getNom()}'.");
        }
        return $niveau;
    }

    // ---- Création d'entités de test ------------------------------------------

    /**
     * Crée un utilisateur avec un sport déclaré (requis par C7).
     * Mot de passe par défaut : Test1234!
     * Sport par défaut : Football (collectif) pour un club, Football pour un joueur.
     */
    protected function creerUtilisateur(
        string $email = 'utilisateur@test.fr',
        TypeUtilisateur $type = TypeUtilisateur::Joueur,
        ?Sport $sport = null,
    ): Utilisateur {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $utilisateur = new Utilisateur();
        $utilisateur->setEmail($email);
        $utilisateur->setPassword($hasher->hashPassword($utilisateur, 'Test1234!'));
        $utilisateur->setNom($type === TypeUtilisateur::Club ? 'AS Test' : 'Durand');
        $utilisateur->setPrenom($type === TypeUtilisateur::Joueur ? 'Test' : null);
        $utilisateur->setType($type);
        $utilisateur->setDateInscription(new \DateTime());
        $this->em->persist($utilisateur);

        // Déclarer un sport (C7 — au moins 1 requis pour pouvoir agir sur des matchs/équipes)
        $sportRef  = $sport ?? $this->getSport('Football');
        $niveauRef = $this->getPremierNiveau($sportRef);

        $un = new UtilisateurNiveau();
        $un->setUtilisateur($utilisateur);
        $un->setSport($sportRef);
        $un->setNiveau($niveauRef);
        $this->em->persist($un);

        $this->em->flush();
        return $utilisateur;
    }

    /**
     * Crée un match en_attente avec camp_1 confirmé pour le créateur.
     * Sport par défaut : Tennis (individuel) — le camp_1 est le joueur créateur.
     * Pour un match collectif, passer un sport Collectif et une Equipe ; le camp sera sans equipe
     * (à compléter manuellement dans le test si nécessaire).
     */
    protected function creerMatch(
        Utilisateur $createur,
        ?Sport $sport = null,
        string $dateMatch = '+30 days',
    ): Game {
        $sportRef = $sport ?? $this->getSport('Tennis');

        $match = new Game();
        $match->setSport($sportRef);
        $match->setDateMatch(new \DateTime($dateMatch));
        $match->setLieu('Terrain test');
        $match->setStatut(StatutGame::EnAttente);
        $match->setCreateur($createur);
        $this->em->persist($match);

        $camp1 = new MatchCamp();
        $camp1->setGame($match);
        $camp1->setRole(RoleMatchCamp::Camp1);
        $camp1->setStatut(StatutMatchCamp::Confirme);
        if ($sportRef->getType() === TypeSport::Individuel) {
            $camp1->setJoueur($createur);
        }
        $this->em->persist($camp1);

        $this->em->flush();
        return $match;
    }

    /**
     * Crée une équipe pour un compte club.
     * Sport par défaut : Football (collectif).
     */
    protected function creerEquipe(
        Utilisateur $club,
        ?Sport $sport = null,
        string $nom = 'Équipe test',
    ): Equipe {
        $sportRef = $sport ?? $this->getSport('Football');

        $equipe = new Equipe();
        $equipe->setNom($nom);
        $equipe->setSport($sportRef);
        $equipe->setClub($club);
        $this->em->persist($equipe);
        $this->em->flush();

        return $equipe;
    }

    protected function obtenirToken(Utilisateur $utilisateur): string
    {
        $jwtManager = static::getContainer()->get('lexik_jwt_authentication.jwt_manager');
        return $jwtManager->create($utilisateur);
    }

    // ---- Helpers HTTP --------------------------------------------------------

    protected function requeteAuth(string $methode, string $url, string $token, ?array $donnees = null): void
    {
        $this->client->request(
            $methode,
            $url,
            [],
            [],
            [
                'CONTENT_TYPE'       => 'application/json',
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
        $this->assertSame($statut, $this->client->getResponse()->getStatusCode());
    }
}
