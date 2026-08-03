<?php

namespace App\Tests\Fonctionnel\Controller;

use App\Enum\TypeUtilisateur;
use App\Tests\Fonctionnel\BaseTestFonctionnel;

class MotDePasseControllerTest extends BaseTestFonctionnel
{
    // =========================================================================
    // PATCH /api/profil/mot-de-passe — cas nominal
    // =========================================================================

    public function testChangerMotDePasseReussit(): void
    {
        $joueur = $this->creerUtilisateur('mdp.ok@test.fr', TypeUtilisateur::Joueur);
        $token  = $this->obtenirToken($joueur);

        $this->requeteAuth('PATCH', '/api/profil/mot-de-passe', $token, [
            'ancienMotDePasse' => 'Test1234!',
            'nouveauMotDePasse' => 'NouveauMdp9',
        ]);

        $this->assertStatut(204);
    }

    /** Après changement, le nouveau mot de passe permet d'obtenir un token. */
    public function testNouveauMotDePasseFonctionneAuLogin(): void
    {
        $joueur = $this->creerUtilisateur('mdp.login@test.fr', TypeUtilisateur::Joueur);
        $token  = $this->obtenirToken($joueur);

        $this->requeteAuth('PATCH', '/api/profil/mot-de-passe', $token, [
            'ancienMotDePasse'  => 'Test1234!',
            'nouveauMotDePasse' => 'NouveauMdp9',
        ]);
        $this->assertStatut(204);

        // Login avec le nouveau mot de passe
        $this->requete('POST', '/api/login_check', [
            'email'    => 'mdp.login@test.fr',
            'password' => 'NouveauMdp9',
        ]);
        $this->assertStatut(200);
        $corps = $this->reponseJson();
        $this->assertArrayHasKey('token', $corps);
    }

    // =========================================================================
    // Ancien mot de passe incorrect → 422
    // =========================================================================

    public function testAncienMotDePasseIncorrectRefuse(): void
    {
        $joueur = $this->creerUtilisateur('mdp.faux@test.fr', TypeUtilisateur::Joueur);
        $token  = $this->obtenirToken($joueur);

        $this->requeteAuth('PATCH', '/api/profil/mot-de-passe', $token, [
            'ancienMotDePasse'  => 'MauvaisMotDePasse1',
            'nouveauMotDePasse' => 'NouveauMdp9',
        ]);

        $this->assertStatut(422);
        $corps = $this->reponseJson();
        $this->assertArrayHasKey('erreur', $corps);
    }

    // =========================================================================
    // Nouveau mot de passe non conforme → 422
    // =========================================================================

    public function testNouveauMotDePasseTropCourtRefuse(): void
    {
        $joueur = $this->creerUtilisateur('mdp.court@test.fr', TypeUtilisateur::Joueur);
        $token  = $this->obtenirToken($joueur);

        $this->requeteAuth('PATCH', '/api/profil/mot-de-passe', $token, [
            'ancienMotDePasse'  => 'Test1234!',
            'nouveauMotDePasse' => 'Ab1',
        ]);

        $this->assertStatut(422);
    }

    public function testNouveauMotDePasseSansChiffreRefuse(): void
    {
        $joueur = $this->creerUtilisateur('mdp.sanchiff@test.fr', TypeUtilisateur::Joueur);
        $token  = $this->obtenirToken($joueur);

        $this->requeteAuth('PATCH', '/api/profil/mot-de-passe', $token, [
            'ancienMotDePasse'  => 'Test1234!',
            'nouveauMotDePasse' => 'sansChiffre',
        ]);

        $this->assertStatut(422);
    }

    public function testNouveauMotDePasseSansLettreRefuse(): void
    {
        $joueur = $this->creerUtilisateur('mdp.sanlettre@test.fr', TypeUtilisateur::Joueur);
        $token  = $this->obtenirToken($joueur);

        $this->requeteAuth('PATCH', '/api/profil/mot-de-passe', $token, [
            'ancienMotDePasse'  => 'Test1234!',
            'nouveauMotDePasse' => '12345678',
        ]);

        $this->assertStatut(422);
    }

    // =========================================================================
    // Champs manquants → 400
    // =========================================================================

    public function testChampManquantRefuse(): void
    {
        $joueur = $this->creerUtilisateur('mdp.champ@test.fr', TypeUtilisateur::Joueur);
        $token  = $this->obtenirToken($joueur);

        $this->requeteAuth('PATCH', '/api/profil/mot-de-passe', $token, [
            'ancienMotDePasse' => 'Test1234!',
            // nouveauMotDePasse absent
        ]);

        $this->assertStatut(400);
    }

    // =========================================================================
    // Non authentifié → 401
    // =========================================================================

    public function testNonAuthentifieRefuse(): void
    {
        $this->requete('PATCH', '/api/profil/mot-de-passe', [
            'ancienMotDePasse'  => 'Test1234!',
            'nouveauMotDePasse' => 'NouveauMdp9',
        ]);

        $this->assertStatut(401);
    }
}
