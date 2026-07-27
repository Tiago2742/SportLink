<?php

namespace App\Tests\Fonctionnel\Controller;

use App\Enum\TypeUtilisateur;
use App\Tests\Fonctionnel\BaseTestFonctionnel;

class InscriptionControllerTest extends BaseTestFonctionnel
{
    // ---- /api/register ------------------------------------------------------

    public function testInscriptionSucces(): void
    {
        $football = $this->getSport('Football');
        $niveau   = $this->getPremierNiveau($football);

        $this->requete('POST', '/api/register', [
            'email'    => 'nouveau@test.fr',
            'password' => 'Test1234!',
            'nom'      => 'Martin',
            'prenom'   => 'Paul',
            'type'     => 'joueur',
            'sports'   => [['sportId' => $football->getId(), 'niveauId' => $niveau->getId()]],
        ]);

        $this->assertStatut(201);
        $reponse = $this->reponseJson();
        $this->assertEquals('nouveau@test.fr', $reponse['email']);
        $this->assertEquals('Martin', $reponse['nom']);
        $this->assertArrayNotHasKey('password', $reponse);
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

        // La vérification de doublon se fait avant le traitement des sports → 409 sans sports
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
        $football = $this->getSport('Football');
        $niveau   = $this->getPremierNiveau($football);

        $this->requete('POST', '/api/register', [
            'email'   => 'club.nouveau@test.fr',
            'password' => 'Test1234!',
            'nom'     => 'AS Test Club',
            'type'    => 'club',
            'sports'  => [['sportId' => $football->getId(), 'niveauId' => $niveau->getId()]],
        ]);

        $this->assertStatut(201);
        $reponse = $this->reponseJson();
        $this->assertEquals('AS Test Club', $reponse['nom']);
        $this->assertNull($reponse['prenom'] ?? null);
    }

    public function testInscriptionJoueurSansPrenom_RetourneErreur400(): void
    {
        // La vérification du prénom se fait avant le traitement des sports → 400 sans sports
        $this->requete('POST', '/api/register', [
            'email'    => 'joueur.sans.prenom@test.fr',
            'password' => 'Test1234!',
            'nom'      => 'Martin',
            'type'     => 'joueur',
        ]);

        $this->assertStatut(400);
        $this->assertStringContainsString('prenom', $this->reponseJson()['erreur']);
    }

    public function testInscriptionSansSport_RetourneErreur400(): void
    {
        $this->requete('POST', '/api/register', [
            'email'    => 'sans.sport@test.fr',
            'password' => 'Test1234!',
            'nom'      => 'Martin',
            'prenom'   => 'Paul',
            'type'     => 'joueur',
            // pas de champ sports
        ]);

        $this->assertStatut(400);
        $this->assertStringContainsString('sport', $this->reponseJson()['erreur']);
    }

    public function testInscriptionClubAvecSportIndividuel_RetourneErreur400(): void
    {
        $tennis = $this->getSport('Tennis');
        $niveau = $this->getPremierNiveau($tennis);

        $this->requete('POST', '/api/register', [
            'email'    => 'club.tennis@test.fr',
            'password' => 'Test1234!',
            'nom'      => 'Club Tennis',
            'type'     => 'club',
            'sports'   => [['sportId' => $tennis->getId(), 'niveauId' => $niveau->getId()]],
        ]);

        $this->assertStatut(400);
        $this->assertStringContainsString('collectif', $this->reponseJson()['erreur']);
    }

    // ---- /api/login_check ---------------------------------------------------

    public function testLoginSucces_RetourneToken(): void
    {
        $this->creerUtilisateur('connecte@test.fr');

        $this->requete('POST', '/api/login_check', [
            'email'    => 'connecte@test.fr',
            'password' => 'Test1234!',
        ]);

        $this->assertStatut(200);
        $this->assertArrayHasKey('token', $this->reponseJson());
    }

    public function testLoginMauvaisMotDePasse_RetourneErreur401(): void
    {
        $this->creerUtilisateur('user@test.fr');

        $this->requete('POST', '/api/login_check', [
            'email'    => 'user@test.fr',
            'password' => 'MauvaisMdp',
        ]);

        $this->assertStatut(401);
    }

    // ---- Test de preuve : authentification JWT bout en bout ------------------

    /**
     * Preuve que toute la chaîne fonctionne :
     * - Sans token → 401
     * - Avec token JWT valide → 200
     */
    public function testAccesRouteProtegee_SansToken_RetourneErreur401(): void
    {
        $this->requete('GET', '/api/matchs');

        $this->assertStatut(401);
    }

    public function testAccesRouteProtegee_AvecToken_Retourne200(): void
    {
        $joueur = $this->creerUtilisateur('joueur.proof@test.fr', TypeUtilisateur::Joueur);
        $token  = $this->obtenirToken($joueur);

        $this->requeteAuth('GET', '/api/matchs', $token);

        $this->assertStatut(200);
    }
}
