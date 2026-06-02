<?php

namespace App\Tests\Fonctionnel\Controller;

use App\Tests\Fonctionnel\BaseTestFonctionnel;

class EquipeControllerTest extends BaseTestFonctionnel
{
    public function testListerEquipes_RetourneListe(): void
    {
        $createur = $this->creerUtilisateur();
        $this->creerEquipe($createur, 'Les Aigles', 'football');
        $this->creerEquipe($createur, 'Les Tigres', 'basketball');

        $this->requeteAuth('GET', '/api/equipes', $this->obtenirToken($createur));

        $this->assertStatut(200);
        $this->assertCount(2, $this->reponseJson());
    }

    public function testListerEquipesAvecFiltreSport(): void
    {
        $createur = $this->creerUtilisateur();
        $this->creerEquipe($createur, 'Les Aigles', 'football');
        $this->creerEquipe($createur, 'Les Tigres', 'basketball');

        $this->requeteAuth('GET', '/api/equipes?sport=football', $this->obtenirToken($createur));

        $this->assertStatut(200);
        $reponse = $this->reponseJson();
        $this->assertCount(1, $reponse);
        $this->assertEquals('football', $reponse[0]['sport']);
    }

    public function testCreerEquipeSucces(): void
    {
        $createur = $this->creerUtilisateur();

        $this->requeteAuth('POST', '/api/equipes', $this->obtenirToken($createur), [
            'nom'         => 'Les Aigles',
            'sport'       => 'football',
            'niveau'      => 'D1',
            'localisation' => 'Paris',
        ]);

        $this->assertStatut(201);
        $reponse = $this->reponseJson();
        $this->assertEquals('Les Aigles', $reponse['nom']);
        $this->assertEquals('football', $reponse['sport']);
        $this->assertEquals($createur->getId(), $reponse['createur']['id']);
        $this->assertArrayHasKey('membres', $reponse);
    }

    public function testCreerEquipeSansNom_RetourneErreur400(): void
    {
        $createur = $this->creerUtilisateur();

        $this->requeteAuth('POST', '/api/equipes', $this->obtenirToken($createur), [
            'sport' => 'football',
        ]);

        $this->assertStatut(400);
    }

    public function testAfficherEquipe(): void
    {
        $createur = $this->creerUtilisateur();
        $equipe = $this->creerEquipe($createur);

        $this->requeteAuth('GET', "/api/equipes/{$equipe->getId()}", $this->obtenirToken($createur));

        $this->assertStatut(200);
        $reponse = $this->reponseJson();
        $this->assertEquals($equipe->getId(), $reponse['id']);
        $this->assertArrayHasKey('membres', $reponse);
    }

    public function testAfficherEquipeInexistante_RetourneErreur404(): void
    {
        $user = $this->creerUtilisateur();

        $this->requeteAuth('GET', '/api/equipes/99999', $this->obtenirToken($user));

        $this->assertStatut(404);
    }

    public function testModifierEquipe_ParLeCreateur(): void
    {
        $createur = $this->creerUtilisateur();
        $equipe = $this->creerEquipe($createur, 'Ancien nom', 'football');

        $this->requeteAuth('PUT', "/api/equipes/{$equipe->getId()}", $this->obtenirToken($createur), [
            'nom'   => 'Nouveau nom',
            'sport' => 'rugby',
        ]);

        $this->assertStatut(200);
        $reponse = $this->reponseJson();
        $this->assertEquals('Nouveau nom', $reponse['nom']);
        $this->assertEquals('rugby', $reponse['sport']);
    }

    public function testModifierEquipe_ParUnAutreUtilisateur_RetourneErreur403(): void
    {
        $createur = $this->creerUtilisateur('createur@test.fr');
        $autreUser = $this->creerUtilisateur('autre@test.fr');
        $equipe = $this->creerEquipe($createur);

        $this->requeteAuth('PUT', "/api/equipes/{$equipe->getId()}", $this->obtenirToken($autreUser), [
            'nom' => 'Tentative de modification',
        ]);

        $this->assertStatut(403);
    }

    public function testSupprimerEquipe_ParLeCreateur(): void
    {
        $createur = $this->creerUtilisateur();
        $equipe = $this->creerEquipe($createur);

        $this->requeteAuth('DELETE', "/api/equipes/{$equipe->getId()}", $this->obtenirToken($createur));

        $this->assertStatut(204);
    }

    public function testSupprimerEquipe_ParUnAutreUtilisateur_RetourneErreur403(): void
    {
        $createur = $this->creerUtilisateur('proprio@test.fr');
        $autreUser = $this->creerUtilisateur('intrus@test.fr');
        $equipe = $this->creerEquipe($createur);

        $this->requeteAuth('DELETE', "/api/equipes/{$equipe->getId()}", $this->obtenirToken($autreUser));

        $this->assertStatut(403);
    }

    public function testAjouterMembre_ParLeCreateur(): void
    {
        $createur = $this->creerUtilisateur('createur@test.fr');
        $nouveauMembre = $this->creerUtilisateur('membre@test.fr');
        $equipe = $this->creerEquipe($createur);

        $this->requeteAuth('POST', "/api/equipes/{$equipe->getId()}/membres", $this->obtenirToken($createur), [
            'utilisateur_id' => $nouveauMembre->getId(),
            'role'           => 'joueur',
        ]);

        $this->assertStatut(201);
        $reponse = $this->reponseJson();
        $this->assertEquals($nouveauMembre->getId(), $reponse['utilisateur']['id']);
        $this->assertEquals('joueur', $reponse['role']);
    }

    public function testAjouterMembreDejaPresent_RetourneErreur422(): void
    {
        $createur = $this->creerUtilisateur('c@test.fr');
        $membre = $this->creerUtilisateur('m@test.fr');
        $equipe = $this->creerEquipe($createur);

        $token = $this->obtenirToken($createur);
        $donnees = ['utilisateur_id' => $membre->getId()];

        $this->requeteAuth('POST', "/api/equipes/{$equipe->getId()}/membres", $token, $donnees);
        $this->assertStatut(201);

        $this->requeteAuth('POST', "/api/equipes/{$equipe->getId()}/membres", $token, $donnees);
        $this->assertStatut(422);
    }

    public function testRetirerMembre(): void
    {
        $createur = $this->creerUtilisateur('c2@test.fr');
        $membre = $this->creerUtilisateur('m2@test.fr');
        $equipe = $this->creerEquipe($createur);

        $token = $this->obtenirToken($createur);

        $this->requeteAuth('POST', "/api/equipes/{$equipe->getId()}/membres", $token, [
            'utilisateur_id' => $membre->getId(),
        ]);
        $membreId = $this->reponseJson()['id'];

        $this->requeteAuth('DELETE', "/api/equipes/{$equipe->getId()}/membres/$membreId", $token);

        $this->assertStatut(204);
    }
}
