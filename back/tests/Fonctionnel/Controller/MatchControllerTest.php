<?php

namespace App\Tests\Fonctionnel\Controller;

use App\Entity\Participation;
use App\Tests\Fonctionnel\BaseTestFonctionnel;

class MatchControllerTest extends BaseTestFonctionnel
{
    public function testListerMatchs_RetourneListe(): void
    {
        $createur = $this->creerUtilisateur();
        $this->creerMatch($createur, 'football');
        $this->creerMatch($createur, 'basketball');

        $this->requeteAuth('GET', '/api/matchs', $this->obtenirToken($createur));

        $this->assertStatut(200);
        $this->assertCount(2, $this->reponseJson());
    }

    public function testListerMatchsAvecFiltres(): void
    {
        $createur = $this->creerUtilisateur();
        $this->creerMatch($createur, 'football');
        $this->creerMatch($createur, 'basketball');

        $this->requeteAuth('GET', '/api/matchs?sport=football', $this->obtenirToken($createur));

        $this->assertStatut(200);
        $reponse = $this->reponseJson();
        $this->assertCount(1, $reponse);
        $this->assertEquals('football', $reponse[0]['sport']);
    }

    public function testCreerMatchSucces(): void
    {
        $createur = $this->creerUtilisateur();

        $this->requeteAuth('POST', '/api/matchs', $this->obtenirToken($createur), [
            'sport'        => 'football',
            'dateMatch'    => '2026-09-15 18:00',
            'lieu'         => 'Stade Municipal',
            'niveauRequis' => 'D1',
        ]);

        $this->assertStatut(201);
        $reponse = $this->reponseJson();
        $this->assertEquals('football', $reponse['sport']);
        $this->assertEquals('ouvert', $reponse['statut']);
        $this->assertEquals($createur->getId(), $reponse['createur']['id']);
        $this->assertArrayHasKey('participations', $reponse);
    }

    public function testCreerMatchSansSport_RetourneErreur400(): void
    {
        $createur = $this->creerUtilisateur();

        $this->requeteAuth('POST', '/api/matchs', $this->obtenirToken($createur), [
            'dateMatch' => '2026-09-15 18:00',
        ]);

        $this->assertStatut(400);
    }

    public function testAfficherMatch_AvecParticipations(): void
    {
        $createur = $this->creerUtilisateur();
        $match = $this->creerMatch($createur);

        $this->requeteAuth('GET', "/api/matchs/{$match->getId()}", $this->obtenirToken($createur));

        $this->assertStatut(200);
        $reponse = $this->reponseJson();
        $this->assertEquals($match->getId(), $reponse['id']);
        $this->assertArrayHasKey('participations', $reponse);
    }

    public function testModifierMatch_ParLeCreateur(): void
    {
        $createur = $this->creerUtilisateur();
        $match = $this->creerMatch($createur, 'football');

        $this->requeteAuth('PUT', "/api/matchs/{$match->getId()}", $this->obtenirToken($createur), [
            'sport' => 'rugby',
            'lieu'  => 'Nouveau terrain',
        ]);

        $this->assertStatut(200);
        $reponse = $this->reponseJson();
        $this->assertEquals('rugby', $reponse['sport']);
        $this->assertEquals('Nouveau terrain', $reponse['lieu']);
    }

    public function testModifierMatch_ParUnAutreUtilisateur_RetourneErreur403(): void
    {
        $createur = $this->creerUtilisateur('c@test.fr');
        $autreUser = $this->creerUtilisateur('a@test.fr');
        $match = $this->creerMatch($createur);

        $this->requeteAuth('PUT', "/api/matchs/{$match->getId()}", $this->obtenirToken($autreUser), [
            'sport' => 'rugby',
        ]);

        $this->assertStatut(403);
    }

    public function testSupprimerMatch_ParLeCreateur(): void
    {
        $createur = $this->creerUtilisateur();
        $match = $this->creerMatch($createur);

        $this->requeteAuth('DELETE', "/api/matchs/{$match->getId()}", $this->obtenirToken($createur));

        $this->assertStatut(204);
    }

    // --- Participations ---

    public function testParticiperAUnMatch(): void
    {
        $createur = $this->creerUtilisateur('c@test.fr');
        $participant = $this->creerUtilisateur('p@test.fr');
        $match = $this->creerMatch($createur);

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/participations", $this->obtenirToken($participant));

        $this->assertStatut(201);
        $reponse = $this->reponseJson();
        $this->assertEquals('invité', $reponse['statut']);
        $this->assertEquals($participant->getId(), $reponse['utilisateur']['id']);
    }

    public function testParticiperDeuxFois_RetourneErreur422(): void
    {
        $createur = $this->creerUtilisateur('c2@test.fr');
        $participant = $this->creerUtilisateur('p2@test.fr');
        $match = $this->creerMatch($createur);

        $token = $this->obtenirToken($participant);
        $url = "/api/matchs/{$match->getId()}/participations";

        $this->requeteAuth('POST', $url, $token);
        $this->assertStatut(201);

        $this->requeteAuth('POST', $url, $token);
        $this->assertStatut(422);
    }

    public function testModifierStatutParticipation_ParLeCreateur(): void
    {
        $createur = $this->creerUtilisateur('c3@test.fr');
        $participant = $this->creerUtilisateur('p3@test.fr');
        $match = $this->creerMatch($createur);

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/participations", $this->obtenirToken($participant));
        $participationId = $this->reponseJson()['id'];

        $this->requeteAuth(
            'PATCH',
            "/api/matchs/{$match->getId()}/participations/$participationId",
            $this->obtenirToken($createur),
            ['statut' => 'confirmé'],
        );

        $this->assertStatut(200);
        $this->assertEquals('confirmé', $this->reponseJson()['statut']);
    }

    public function testModifierStatutInvalide_RetourneErreur422(): void
    {
        $createur = $this->creerUtilisateur('c4@test.fr');
        $participant = $this->creerUtilisateur('p4@test.fr');
        $match = $this->creerMatch($createur);

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/participations", $this->obtenirToken($participant));
        $participationId = $this->reponseJson()['id'];

        $this->requeteAuth(
            'PATCH',
            "/api/matchs/{$match->getId()}/participations/$participationId",
            $this->obtenirToken($createur),
            ['statut' => 'statut_inexistant'],
        );

        $this->assertStatut(422);
    }

    // --- Messages ---

    public function testEnvoyerMessage_CreateurPeutEcrire(): void
    {
        $createur = $this->creerUtilisateur();
        $match = $this->creerMatch($createur);

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/messages", $this->obtenirToken($createur), [
            'contenu' => 'Rendez-vous à 18h !',
        ]);

        $this->assertStatut(201);
        $reponse = $this->reponseJson();
        $this->assertEquals('Rendez-vous à 18h !', $reponse['contenu']);
        $this->assertEquals($createur->getId(), $reponse['expediteur']['id']);
    }

    public function testEnvoyerMessage_NonParticipant_RetourneErreur403(): void
    {
        $createur = $this->creerUtilisateur('c5@test.fr');
        $etranger = $this->creerUtilisateur('e@test.fr');
        $match = $this->creerMatch($createur);

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/messages", $this->obtenirToken($etranger), [
            'contenu' => 'Intrusion !',
        ]);

        $this->assertStatut(403);
    }

    public function testListerMessages_ReservesAuxParticipants(): void
    {
        $createur = $this->creerUtilisateur('c6@test.fr');
        $participant = $this->creerUtilisateur('p6@test.fr');
        $etranger = $this->creerUtilisateur('e2@test.fr');
        $match = $this->creerMatch($createur);

        // Le participant rejoint et confirme sa participation via EntityManager direct
        $participation = new Participation();
        $participation->setGame($match);
        $participation->setUtilisateur($participant);
        $participation->setStatut('confirmé');
        $this->em->persist($participation);
        $this->em->flush();

        // Le créateur envoie un message
        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/messages", $this->obtenirToken($createur), [
            'contenu' => 'Bonjour',
        ]);

        // Le participant confirmé peut lire
        $this->requeteAuth('GET', "/api/matchs/{$match->getId()}/messages", $this->obtenirToken($participant));
        $this->assertStatut(200);
        $this->assertCount(1, $this->reponseJson());

        // L'étranger ne peut pas lire
        $this->requeteAuth('GET', "/api/matchs/{$match->getId()}/messages", $this->obtenirToken($etranger));
        $this->assertStatut(403);
    }

    // --- Résultat ---

    public function testSaisirEtConsulterResultat(): void
    {
        $createur = $this->creerUtilisateur();
        $match = $this->creerMatch($createur);
        $token = $this->obtenirToken($createur);

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/resultat", $token, [
            'scoreEquipe1' => 3,
            'scoreEquipe2' => 1,
        ]);

        $this->assertStatut(201);
        $reponse = $this->reponseJson();
        $this->assertEquals(3, $reponse['scoreEquipe1']);
        $this->assertEquals(1, $reponse['scoreEquipe2']);

        $this->requeteAuth('GET', "/api/matchs/{$match->getId()}/resultat", $token);
        $this->assertStatut(200);
    }

    public function testSaisirResultatDeuxFois_RetourneErreur422(): void
    {
        $createur = $this->creerUtilisateur('c7@test.fr');
        $match = $this->creerMatch($createur);
        $token = $this->obtenirToken($createur);
        $donnees = ['scoreEquipe1' => 2, 'scoreEquipe2' => 0];

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/resultat", $token, $donnees);
        $this->assertStatut(201);

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/resultat", $token, $donnees);
        $this->assertStatut(422);
    }

    public function testModifierResultat(): void
    {
        $createur = $this->creerUtilisateur('c8@test.fr');
        $match = $this->creerMatch($createur);
        $token = $this->obtenirToken($createur);

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/resultat", $token, [
            'scoreEquipe1' => 1, 'scoreEquipe2' => 0,
        ]);

        $this->requeteAuth('PUT', "/api/matchs/{$match->getId()}/resultat", $token, [
            'scoreEquipe1' => 2, 'scoreEquipe2' => 2,
        ]);

        $this->assertStatut(200);
        $reponse = $this->reponseJson();
        $this->assertEquals(2, $reponse['scoreEquipe1']);
        $this->assertEquals(2, $reponse['scoreEquipe2']);
    }

    public function testConsulterResultatInexistant_RetourneErreur404(): void
    {
        $createur = $this->creerUtilisateur('c9@test.fr');
        $match = $this->creerMatch($createur);

        $this->requeteAuth('GET', "/api/matchs/{$match->getId()}/resultat", $this->obtenirToken($createur));

        $this->assertStatut(404);
    }
}
