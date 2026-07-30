<?php

namespace App\Tests\Fonctionnel\Controller;

use App\Entity\Game;
use App\Enum\TypeUtilisateur;
use App\Tests\Fonctionnel\BaseTestFonctionnel;

class ProfilSuppressionTest extends BaseTestFonctionnel
{
    public function testSupprimerCompteNonAuthentifie(): void
    {
        $this->requete('DELETE', '/api/profil');
        $this->assertStatut(401);
    }

    public function testSupprimerCompteJoueur(): void
    {
        $joueur = $this->creerUtilisateur('joueur.suppr@test.fr', TypeUtilisateur::Joueur);
        $token  = $this->obtenirToken($joueur);

        $this->requeteAuth('DELETE', '/api/profil', $token);
        $this->assertStatut(204);

        $this->em->refresh($joueur);
        $this->assertStringStartsWith('deleted_', $joueur->getEmail());
        $this->assertSame('Utilisateur supprimé', $joueur->getNom());
        $this->assertNull($joueur->getPrenom());
        $this->assertNull($joueur->getLocalisation());
        $this->assertCount(0, $joueur->getNiveaux());
    }

    public function testAncienJwtApresSuppressionDonne401(): void
    {
        $joueur = $this->creerUtilisateur('joueur.jwt@test.fr', TypeUtilisateur::Joueur);
        $token  = $this->obtenirToken($joueur);

        $this->requeteAuth('DELETE', '/api/profil', $token);
        $this->assertStatut(204);

        // Le même token ne doit plus être accepté (email introuvable en base)
        $this->requeteAuth('GET', '/api/profil', $token);
        $this->assertStatut(401);
    }

    public function testSupprimerCompteClubAvecEquipesRetourne422(): void
    {
        $club = $this->creerUtilisateur('club.equipes@test.fr', TypeUtilisateur::Club);
        $this->creerEquipe($club, null, 'Équipe du club');
        $token = $this->obtenirToken($club);

        $this->requeteAuth('DELETE', '/api/profil', $token);
        $this->assertStatut(422);

        $data = $this->reponseJson();
        $this->assertArrayHasKey('erreur', $data);
    }

    public function testSupprimerCompteClubSansEquipes(): void
    {
        $club  = $this->creerUtilisateur('club.noequipe@test.fr', TypeUtilisateur::Club);
        $token = $this->obtenirToken($club);

        $this->requeteAuth('DELETE', '/api/profil', $token);
        $this->assertStatut(204);
    }

    public function testHistoriqueMatchConserveApresSuppression(): void
    {
        $joueur   = $this->creerUtilisateur('joueur.history@test.fr', TypeUtilisateur::Joueur);
        $matchRef = $this->creerMatch($joueur);
        $token    = $this->obtenirToken($joueur);

        $this->requeteAuth('DELETE', '/api/profil', $token);
        $this->assertStatut(204);

        // Le match doit encore exister en base (anonymisation, pas suppression)
        $this->em->clear();
        $matchEnBase = $this->em->find(Game::class, $matchRef->getId());
        $this->assertNotNull($matchEnBase, 'Le match doit persister après anonymisation du créateur.');
    }
}
