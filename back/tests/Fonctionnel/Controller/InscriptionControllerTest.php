<?php

namespace App\Tests\Fonctionnel\Controller;

use App\Tests\Fonctionnel\BaseTestFonctionnel;

class InscriptionControllerTest extends BaseTestFonctionnel
{
    public function testInscriptionSucces(): void
    {
        $this->requete('POST', '/api/register', [
            'email'    => 'nouveau@test.fr',
            'password' => 'Test1234!',
            'nom'      => 'Martin',
            'prenom'   => 'Paul',
            'type'     => 'joueur',
        ]);

        $this->assertStatut(201);
        $reponse = $this->reponseJson();
        $this->assertEquals('nouveau@test.fr', $reponse['email']);
        $this->assertEquals('Martin', $reponse['nom']);
        $this->assertArrayNotHasKey('password', $reponse);
        $this->assertArrayNotHasKey('roles', $reponse);
        $this->assertArrayHasKey('id', $reponse);
    }

    public function testInscriptionChampManquant_RetourneErreur400(): void
    {
        $this->requete('POST', '/api/register', [
            'email'    => 'incomplet@test.fr',
            'password' => 'Test1234!',
            // nom, prenom, type manquants
        ]);

        $this->assertStatut(400);
        $this->assertArrayHasKey('erreur', $this->reponseJson());
    }

    public function testInscriptionEmailDuplique_RetourneErreur409(): void
    {
        $this->creerUtilisateur('existant@test.fr');

        $this->requete('POST', '/api/register', [
            'email'    => 'existant@test.fr',
            'password' => 'AutreMdp1!',
            'nom'      => 'Dupont',
            'prenom'   => 'Jean',
            'type'     => 'joueur',
        ]);

        $this->assertStatut(409);
        $this->assertStringContainsString('email', $this->reponseJson()['erreur']);
    }

    public function testInscriptionClubSansPrenom_Succes(): void
    {
        $this->requete('POST', '/api/register', [
            'email'    => 'club.nouveau@test.fr',
            'password' => 'Test1234!',
            'nom'      => 'AS Test Club',
            'type'     => 'club',
        ]);

        $this->assertStatut(201);
        $reponse = $this->reponseJson();
        $this->assertEquals('AS Test Club', $reponse['nom']);
        $this->assertNull($reponse['prenom'] ?? null);
    }

    public function testInscriptionJoueurSansPrenom_RetourneErreur400(): void
    {
        $this->requete('POST', '/api/register', [
            'email'    => 'joueur.sans.prenom@test.fr',
            'password' => 'Test1234!',
            'nom'      => 'Martin',
            'type'     => 'joueur',
        ]);

        $this->assertStatut(400);
        $this->assertStringContainsString('prenom', $this->reponseJson()['erreur']);
    }

    public function testLoginSucces_RetourneToken(): void
    {
        $this->creerUtilisateur('connecte@test.fr', 'Test1234!');

        $this->requete('POST', '/api/login_check', [
            'email'    => 'connecte@test.fr',
            'password' => 'Test1234!',
        ]);

        $this->assertStatut(200);
        $this->assertArrayHasKey('token', $this->reponseJson());
    }

    public function testLoginMauvaisMotDePasse_RetourneErreur401(): void
    {
        $this->creerUtilisateur('user@test.fr', 'BonMdp1!');

        $this->requete('POST', '/api/login_check', [
            'email'    => 'user@test.fr',
            'password' => 'MauvaisMdp',
        ]);

        $this->assertStatut(401);
    }

    public function testAccesRouteProtegee_SansToken_RetourneErreur401(): void
    {
        $this->requete('GET', '/api/equipes');

        $this->assertStatut(401);
    }
}
