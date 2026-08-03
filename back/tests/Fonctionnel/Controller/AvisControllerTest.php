<?php

namespace App\Tests\Fonctionnel\Controller;

use App\Entity\Avis;
use App\Entity\Game;
use App\Entity\MatchCamp;
use App\Entity\Utilisateur;
use App\Enum\RoleMatchCamp;
use App\Enum\StatutGame;
use App\Enum\StatutMatchCamp;
use App\Enum\TypeUtilisateur;
use App\Tests\Fonctionnel\BaseTestFonctionnel;

class AvisControllerTest extends BaseTestFonctionnel
{
    // ── Helper privé ──────────────────────────────────────────────────────────

    /** Match Terminé avec 2 camps joueur (sport individuel — Tennis). */
    private function creerMatchTermineDeuxJoueurs(
        Utilisateur $joueur1,
        Utilisateur $joueur2,
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

        $this->em->flush();
        return $match;
    }

    // =========================================================================
    // POST /api/matchs/{id}/avis — cas nominal
    // =========================================================================

    public function testDeposerAvisReussit(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('av.ok.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('av.ok.j2@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatchTermineDeuxJoueurs($joueur1, $joueur2);

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/avis", $this->obtenirToken($joueur1), [
            'ponctualite'    => 4,
            'fairPlay'       => 5,
            'niveauConforme' => 3,
        ]);

        $this->assertStatut(201);
        $corps = $this->reponseJson();
        $this->assertSame(4, $corps['ponctualite']);
        $this->assertSame(5, $corps['fairPlay']);
        $this->assertSame(3, $corps['niveauConforme']);
        $this->assertArrayHasKey('dateCreation', $corps);
    }

    /** Le participant du camp_2 peut aussi noter (pas uniquement le créateur). */
    public function testParticipantCamp2PeutNoter(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('av.camp2.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('av.camp2.j2@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatchTermineDeuxJoueurs($joueur1, $joueur2);

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/avis", $this->obtenirToken($joueur2), [
            'ponctualite'    => 3,
            'fairPlay'       => 4,
            'niveauConforme' => 5,
        ]);

        $this->assertStatut(201);
    }

    // =========================================================================
    // R1 — Match non terminé
    // =========================================================================

    public function testDeposerAvisMatchNonTermineRefuse(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('av.r1.j1@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatch($joueur1, $tennis, '+30 days'); // en_attente

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/avis", $this->obtenirToken($joueur1), [
            'ponctualite'    => 4,
            'fairPlay'       => 5,
            'niveauConforme' => 3,
        ]);

        $this->assertStatut(422);
    }

    // =========================================================================
    // R2 — Note hors [1, 5]
    // =========================================================================

    public function testDeposerAvisNoteZeroRefuse(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('av.r2a.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('av.r2a.j2@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatchTermineDeuxJoueurs($joueur1, $joueur2);

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/avis", $this->obtenirToken($joueur1), [
            'ponctualite'    => 0,
            'fairPlay'       => 5,
            'niveauConforme' => 3,
        ]);

        $this->assertStatut(422);
    }

    public function testDeposerAvisNoteSixRefuse(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('av.r2b.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('av.r2b.j2@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatchTermineDeuxJoueurs($joueur1, $joueur2);

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/avis", $this->obtenirToken($joueur1), [
            'ponctualite'    => 4,
            'fairPlay'       => 6,
            'niveauConforme' => 3,
        ]);

        $this->assertStatut(422);
    }

    // =========================================================================
    // R3 — Notant non participant
    // =========================================================================

    public function testDeposerAvisNonParticipantRefuse(): void
    {
        $tennis   = $this->getSport('Tennis');
        $joueur1  = $this->creerUtilisateur('av.r3.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2  = $this->creerUtilisateur('av.r3.j2@test.fr', TypeUtilisateur::Joueur, $tennis);
        $etranger = $this->creerUtilisateur('av.r3.ext@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatchTermineDeuxJoueurs($joueur1, $joueur2);

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/avis", $this->obtenirToken($etranger), [
            'ponctualite'    => 4,
            'fairPlay'       => 5,
            'niveauConforme' => 3,
        ]);

        $this->assertStatut(422);
    }

    // =========================================================================
    // R6 — Doublon (même notant, même match)
    // =========================================================================

    public function testDeposerAvisDeuxFoisRefuse(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('av.r6.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('av.r6.j2@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatchTermineDeuxJoueurs($joueur1, $joueur2);
        $token = $this->obtenirToken($joueur1);
        $corps = ['ponctualite' => 4, 'fairPlay' => 5, 'niveauConforme' => 3];

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/avis", $token, $corps);
        $this->assertStatut(201);

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/avis", $token, $corps);
        $this->assertStatut(422);
    }

    // =========================================================================
    // Validation du corps de requête
    // =========================================================================

    public function testDeposerAvisChampManquantRefuse(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('av.400.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('av.400.j2@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatchTermineDeuxJoueurs($joueur1, $joueur2);

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/avis", $this->obtenirToken($joueur1), [
            'ponctualite' => 4,
            // fairPlay et niveauConforme manquants
        ]);

        $this->assertStatut(400);
    }

    public function testDeposerAvisNonAuthentifieRefuse(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('av.401.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('av.401.j2@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatchTermineDeuxJoueurs($joueur1, $joueur2);

        $this->requete('POST', "/api/matchs/{$match->getId()}/avis", [
            'ponctualite'    => 4,
            'fairPlay'       => 5,
            'niveauConforme' => 3,
        ]);

        $this->assertStatut(401);
    }

    // =========================================================================
    // GET /api/profils/{id}
    // =========================================================================

    public function testAfficherProfilPublicSansAvis(): void
    {
        $tennis = $this->getSport('Tennis');
        $cible  = $this->creerUtilisateur('av.profil.cible@test.fr', TypeUtilisateur::Joueur, $tennis);
        $autre  = $this->creerUtilisateur('av.profil.autre@test.fr', TypeUtilisateur::Joueur, $tennis);

        $this->requeteAuth('GET', "/api/profils/{$cible->getId()}", $this->obtenirToken($autre));

        $this->assertStatut(200);
        $corps = $this->reponseJson();
        $this->assertSame($cible->getId(), $corps['id']);
        $this->assertNull($corps['reputation']);
    }

    public function testAfficherProfilPublicAvecReputation(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('av.rep.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('av.rep.j2@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatchTermineDeuxJoueurs($joueur1, $joueur2);

        // Insérer un avis directement en base pour contrôler les valeurs exactes
        $avis = new Avis();
        $avis->setNotant($joueur1);
        $avis->setEvalue($joueur2);
        $avis->setGame($match);
        $avis->setPonctualite(4);
        $avis->setFairPlay(5);
        $avis->setNiveauConforme(3);
        $avis->setDateCreation(new \DateTimeImmutable());
        $this->em->persist($avis);
        $this->em->flush();

        $this->requeteAuth('GET', "/api/profils/{$joueur2->getId()}", $this->obtenirToken($joueur1));

        $this->assertStatut(200);
        $corps = $this->reponseJson();
        $this->assertNotNull($corps['reputation']);
        $this->assertEquals(4.0, $corps['reputation']['ponctualite']);
        $this->assertEquals(5.0, $corps['reputation']['fairPlay']);
        $this->assertEquals(3.0, $corps['reputation']['niveauConforme']);
        $this->assertSame(1, $corps['reputation']['total']);
    }

    /** Les deux camps peuvent noter indépendamment (chacun note l'autre). */
    public function testLesDeuxParticipantsPeuventNoterIndependamment(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('av.both.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('av.both.j2@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatchTermineDeuxJoueurs($joueur1, $joueur2);

        // joueur1 note joueur2
        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/avis", $this->obtenirToken($joueur1), [
            'ponctualite'    => 5,
            'fairPlay'       => 5,
            'niveauConforme' => 4,
        ]);
        $this->assertStatut(201);

        // joueur2 note joueur1 (camp différent — pas un doublon)
        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/avis", $this->obtenirToken($joueur2), [
            'ponctualite'    => 3,
            'fairPlay'       => 4,
            'niveauConforme' => 3,
        ]);
        $this->assertStatut(201);
    }
}
