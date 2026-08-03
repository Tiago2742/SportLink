<?php

namespace App\Tests\Fonctionnel\Controller;

use App\Entity\Game;
use App\Entity\MatchCamp;
use App\Entity\Resultat;
use App\Entity\Utilisateur;
use App\Enum\RoleMatchCamp;
use App\Enum\StatutGame;
use App\Enum\StatutMatchCamp;
use App\Enum\TypeUtilisateur;
use App\Tests\Fonctionnel\BaseTestFonctionnel;

class StatsControllerTest extends BaseTestFonctionnel
{
    // ── Helper : crée un match terminé entre deux joueurs avec résultat ─────────

    private function creerMatchTermineAvecResultat(
        Utilisateur $joueur1,
        Utilisateur $joueur2,
        int $scoreCamp1,
        int $scoreCamp2,
    ): Game {
        $tennis = $this->getSport('Tennis');

        $match = new Game();
        $match->setSport($tennis);
        $match->setDateMatch(new \DateTime('-1 day'));
        $match->setLieu('Court test');
        $match->setStatut(StatutGame::Termine);
        $match->setCreateur($joueur1);
        $this->em->persist($match);

        $camp1 = new MatchCamp();
        $camp1->setRole(RoleMatchCamp::Camp1);
        $camp1->setStatut(StatutMatchCamp::Confirme);
        $camp1->setJoueur($joueur1);
        $match->addCamp($camp1);
        $this->em->persist($camp1);

        $camp2 = new MatchCamp();
        $camp2->setRole(RoleMatchCamp::Camp2);
        $camp2->setStatut(StatutMatchCamp::Confirme);
        $camp2->setJoueur($joueur2);
        $match->addCamp($camp2);
        $this->em->persist($camp2);

        $resultat = new Resultat();
        $resultat->setGame($match);
        $resultat->setScoreCamp1($scoreCamp1);
        $resultat->setScoreCamp2($scoreCamp2);
        $this->em->persist($resultat);

        $this->em->flush();
        return $match;
    }

    // =========================================================================
    // GET /api/stats — accès
    // =========================================================================

    public function testNonAuthentifieRefuse(): void
    {
        $this->requete('GET', '/api/stats');
        $this->assertStatut(401);
    }

    public function testAuthentifieAccede(): void
    {
        $tennis = $this->getSport('Tennis');
        $joueur = $this->creerUtilisateur('stats.ok@test.fr', TypeUtilisateur::Joueur, $tennis);
        $this->requeteAuth('GET', '/api/stats', $this->obtenirToken($joueur));
        $this->assertStatut(200);
    }

    // =========================================================================
    // Calcul des stats — victoire
    // =========================================================================

    public function testVictoireCompteeCorrectement(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('stats.v1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('stats.v2@test.fr', TypeUtilisateur::Joueur, $tennis);

        // joueur1 est camp_1, gagne (3-1)
        $this->creerMatchTermineAvecResultat($joueur1, $joueur2, 3, 1);

        $this->requeteAuth('GET', '/api/stats', $this->obtenirToken($joueur1));
        $this->assertStatut(200);
        $corps = $this->reponseJson();

        $this->assertSame(1, $corps['individuels']['joues']);
        $this->assertSame(1, $corps['individuels']['victoires']);
        $this->assertSame(0, $corps['individuels']['defaites']);
        $this->assertSame(0, $corps['individuels']['nuls']);
        $this->assertEquals(100.0, $corps['individuels']['ratio']);
    }

    public function testDefaiteCompteeCorrectement(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('stats.d1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('stats.d2@test.fr', TypeUtilisateur::Joueur, $tennis);

        // joueur2 est camp_2, perd (3-1)
        $this->creerMatchTermineAvecResultat($joueur1, $joueur2, 3, 1);

        $this->requeteAuth('GET', '/api/stats', $this->obtenirToken($joueur2));
        $this->assertStatut(200);
        $corps = $this->reponseJson();

        $this->assertSame(1, $corps['individuels']['joues']);
        $this->assertSame(0, $corps['individuels']['victoires']);
        $this->assertSame(1, $corps['individuels']['defaites']);
        $this->assertSame(0, $corps['individuels']['nuls']);
        $this->assertEquals(0.0, $corps['individuels']['ratio']);
    }

    public function testNulCompteeCorrectement(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('stats.n1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('stats.n2@test.fr', TypeUtilisateur::Joueur, $tennis);

        // Égalité (2-2)
        $this->creerMatchTermineAvecResultat($joueur1, $joueur2, 2, 2);

        $this->requeteAuth('GET', '/api/stats', $this->obtenirToken($joueur1));
        $this->assertStatut(200);
        $corps = $this->reponseJson();

        $this->assertSame(1, $corps['individuels']['joues']);
        $this->assertSame(0, $corps['individuels']['victoires']);
        $this->assertSame(0, $corps['individuels']['defaites']);
        $this->assertSame(1, $corps['individuels']['nuls']);
        $this->assertEquals(0.0, $corps['individuels']['ratio']);
    }

    public function testRatioCalculeCorrectement(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('stats.r1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('stats.r2@test.fr', TypeUtilisateur::Joueur, $tennis);

        // 1 victoire (3-1) + 1 défaite (1-3) → ratio = 50%
        $this->creerMatchTermineAvecResultat($joueur1, $joueur2, 3, 1);
        $this->creerMatchTermineAvecResultat($joueur1, $joueur2, 1, 3);

        $this->requeteAuth('GET', '/api/stats', $this->obtenirToken($joueur1));
        $this->assertStatut(200);
        $corps = $this->reponseJson();

        $this->assertSame(2, $corps['individuels']['joues']);
        $this->assertSame(1, $corps['individuels']['victoires']);
        $this->assertSame(1, $corps['individuels']['defaites']);
        $this->assertEquals(50.0, $corps['individuels']['ratio']);
    }

    // =========================================================================
    // Match non terminé ou sans résultat → ignoré
    // =========================================================================

    public function testMatchSansResultatIgnore(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('stats.sr1@test.fr', TypeUtilisateur::Joueur, $tennis);

        // Match terminé SANS résultat
        $tennis2 = $this->getSport('Tennis');
        $match = new Game();
        $match->setSport($tennis2);
        $match->setDateMatch(new \DateTime('-1 day'));
        $match->setLieu('Court');
        $match->setStatut(StatutGame::Termine);
        $match->setCreateur($joueur1);
        $this->em->persist($match);

        $camp1 = new MatchCamp();
        $camp1->setRole(RoleMatchCamp::Camp1);
        $camp1->setStatut(StatutMatchCamp::Confirme);
        $camp1->setJoueur($joueur1);
        $match->addCamp($camp1);
        $this->em->persist($camp1);
        $this->em->flush();

        $this->requeteAuth('GET', '/api/stats', $this->obtenirToken($joueur1));
        $this->assertStatut(200);
        $corps = $this->reponseJson();

        $this->assertSame(0, $corps['individuels']['joues']);
    }

    public function testMatchNonTermineIgnore(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur  = $this->creerUtilisateur('stats.nt@test.fr', TypeUtilisateur::Joueur, $tennis);

        // Match en_attente → ne compte pas
        $this->creerMatch($joueur, $tennis, '+10 days');

        $this->requeteAuth('GET', '/api/stats', $this->obtenirToken($joueur));
        $this->assertStatut(200);
        $corps = $this->reponseJson();

        $this->assertSame(0, $corps['individuels']['joues']);
    }

    // =========================================================================
    // Structure de la réponse
    // =========================================================================

    public function testJoueurADeuxBlocs(): void
    {
        $tennis = $this->getSport('Tennis');
        $joueur = $this->creerUtilisateur('stats.struct.j@test.fr', TypeUtilisateur::Joueur, $tennis);

        $this->requeteAuth('GET', '/api/stats', $this->obtenirToken($joueur));
        $this->assertStatut(200);
        $corps = $this->reponseJson();

        $this->assertArrayHasKey('individuels', $corps);
        $this->assertArrayHasKey('equipes', $corps);
        $this->assertNotNull($corps['equipes']);
    }

    public function testClubNAPasEquipes(): void
    {
        $club = $this->creerUtilisateur('stats.struct.c@test.fr', TypeUtilisateur::Club);

        $this->requeteAuth('GET', '/api/stats', $this->obtenirToken($club));
        $this->assertStatut(200);
        $corps = $this->reponseJson();

        $this->assertArrayHasKey('individuels', $corps);
        $this->assertNull($corps['equipes']);
    }
}
