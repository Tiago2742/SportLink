<?php

namespace App\Tests\Fonctionnel\Controller;

use App\Entity\Equipe;
use App\Entity\EquipeJoueur;
use App\Entity\Utilisateur;
use App\Enum\OrigineMembreEquipe;
use App\Enum\RoleEquipe;
use App\Enum\StatutMembreEquipe;
use App\Enum\TypeUtilisateur;
use App\Tests\Fonctionnel\BaseTestFonctionnel;

class EquipeControllerTest extends BaseTestFonctionnel
{
    // ---- Helper ---------------------------------------------------------------

    /**
     * Crée directement en base une adhésion avec le statut voulu.
     * addMembre() est utilisé pour synchroniser la collection en mémoire (même EM que le kernel).
     */
    private function creerMembreEquipe(
        Utilisateur $utilisateur,
        Equipe $equipe,
        StatutMembreEquipe $statut = StatutMembreEquipe::EnAttente,
        OrigineMembreEquipe $origine = OrigineMembreEquipe::DemandeJoueur,
        RoleEquipe $role = RoleEquipe::Joueur,
    ): EquipeJoueur {
        $ej = new EquipeJoueur();
        $ej->setUtilisateur($utilisateur);
        $ej->setRole($role);
        $ej->setStatut($statut);
        $ej->setOrigine($origine);
        $equipe->addMembre($ej);  // synchronise la collection en mémoire
        $this->em->persist($ej);
        $this->em->flush();
        return $ej;
    }

    // =========================================================================
    // F1/F2 — Permissions création et gestion d'équipe
    // =========================================================================

    public function testJoueurNePeutPasCreerEquipe(): void
    {
        $football = $this->getSport('Football');
        $joueur   = $this->creerUtilisateur('joueur.f1@test.fr', TypeUtilisateur::Joueur, $football);

        $this->requeteAuth('POST', '/api/equipes', $this->obtenirToken($joueur), [
            'nom'     => 'Équipe Joueur FC',
            'sportId' => $football->getId(),
        ]);

        $this->assertStatut(403);
    }

    public function testClubNePeutPasInviterSurEquipeAutreClub(): void
    {
        $football = $this->getSport('Football');
        $clubA    = $this->creerUtilisateur('clubA.f2@test.fr', TypeUtilisateur::Club, $football);
        $clubB    = $this->creerUtilisateur('clubB.f2@test.fr', TypeUtilisateur::Club, $football);
        $joueur   = $this->creerUtilisateur('joueur.f2@test.fr', TypeUtilisateur::Joueur, $football);
        $equipeA  = $this->creerEquipe($clubA, $football, 'AS Club A');

        // Club B tente d'inviter quelqu'un dans l'équipe appartenant à Club A
        $this->requeteAuth('POST', "/api/equipes/{$equipeA->getId()}/membres", $this->obtenirToken($clubB), [
            'utilisateur_id' => $joueur->getId(),
        ]);

        $this->assertStatut(403);
    }

    // =========================================================================
    // B1 — Invitation club → joueur : acceptation et protection
    // =========================================================================

    public function testJoueurAccepteInvitation(): void
    {
        $football = $this->getSport('Football');
        $club     = $this->creerUtilisateur('club.b1a@test.fr', TypeUtilisateur::Club, $football);
        $joueur   = $this->creerUtilisateur('joueur.b1a@test.fr', TypeUtilisateur::Joueur, $football);
        $equipe   = $this->creerEquipe($club, $football, 'AS B1a');

        $ej = $this->creerMembreEquipe(
            $joueur, $equipe, StatutMembreEquipe::EnAttente, OrigineMembreEquipe::InvitationClub,
        );

        $this->requeteAuth(
            'PATCH',
            "/api/equipes/{$equipe->getId()}/membres/{$ej->getId()}",
            $this->obtenirToken($joueur),
            ['statut' => 'confirme'],
        );

        $this->assertStatut(200);
        $this->assertSame('confirme', $this->reponseJson()['statut']);
    }

    public function testAutreJoueurNePeutPasAccepterInvitationDautrui(): void
    {
        $football = $this->getSport('Football');
        $club     = $this->creerUtilisateur('club.b1b@test.fr', TypeUtilisateur::Club, $football);
        $joueur1  = $this->creerUtilisateur('j1.b1b@test.fr', TypeUtilisateur::Joueur, $football);
        $joueur2  = $this->creerUtilisateur('j2.b1b@test.fr', TypeUtilisateur::Joueur, $football);
        $equipe   = $this->creerEquipe($club, $football, 'AS B1b');

        // Invitation créée pour joueur1
        $ej = $this->creerMembreEquipe(
            $joueur1, $equipe, StatutMembreEquipe::EnAttente, OrigineMembreEquipe::InvitationClub,
        );

        // Joueur2 tente d'accepter à la place de joueur1
        $this->requeteAuth(
            'PATCH',
            "/api/equipes/{$equipe->getId()}/membres/{$ej->getId()}",
            $this->obtenirToken($joueur2),
            ['statut' => 'confirme'],
        );

        $this->assertStatut(403);
    }

    // =========================================================================
    // B2 — Demande joueur → club : acceptation et protection
    // =========================================================================

    public function testClubAccepteDemandeJoueur(): void
    {
        $football = $this->getSport('Football');
        $club     = $this->creerUtilisateur('club.b2a@test.fr', TypeUtilisateur::Club, $football);
        $joueur   = $this->creerUtilisateur('joueur.b2a@test.fr', TypeUtilisateur::Joueur, $football);
        $equipe   = $this->creerEquipe($club, $football, 'AS B2a');

        $ej = $this->creerMembreEquipe(
            $joueur, $equipe, StatutMembreEquipe::EnAttente, OrigineMembreEquipe::DemandeJoueur,
        );

        $this->requeteAuth(
            'PATCH',
            "/api/equipes/{$equipe->getId()}/membres/{$ej->getId()}",
            $this->obtenirToken($club),
            ['statut' => 'confirme'],
        );

        $this->assertStatut(200);
        $this->assertSame('confirme', $this->reponseJson()['statut']);
    }

    public function testAutreClubNePeutPasAccepterDemandeJoueur(): void
    {
        $football = $this->getSport('Football');
        $clubA    = $this->creerUtilisateur('clubA.b2b@test.fr', TypeUtilisateur::Club, $football);
        $clubB    = $this->creerUtilisateur('clubB.b2b@test.fr', TypeUtilisateur::Club, $football);
        $joueur   = $this->creerUtilisateur('joueur.b2b@test.fr', TypeUtilisateur::Joueur, $football);
        $equipe   = $this->creerEquipe($clubA, $football, 'AS B2b');

        $ej = $this->creerMembreEquipe(
            $joueur, $equipe, StatutMembreEquipe::EnAttente, OrigineMembreEquipe::DemandeJoueur,
        );

        // Club B tente de répondre à une demande concernant l'équipe de Club A
        $this->requeteAuth(
            'PATCH',
            "/api/equipes/{$equipe->getId()}/membres/{$ej->getId()}",
            $this->obtenirToken($clubB),
            ['statut' => 'confirme'],
        );

        $this->assertStatut(403);
    }

    // =========================================================================
    // B3 — Refus d'une invitation et d'une demande
    // =========================================================================

    public function testJoueurRefuseInvitation(): void
    {
        $football = $this->getSport('Football');
        $club     = $this->creerUtilisateur('club.b3a@test.fr', TypeUtilisateur::Club, $football);
        $joueur   = $this->creerUtilisateur('joueur.b3a@test.fr', TypeUtilisateur::Joueur, $football);
        $equipe   = $this->creerEquipe($club, $football, 'AS B3a');

        $ej = $this->creerMembreEquipe(
            $joueur, $equipe, StatutMembreEquipe::EnAttente, OrigineMembreEquipe::InvitationClub,
        );

        $this->requeteAuth(
            'PATCH',
            "/api/equipes/{$equipe->getId()}/membres/{$ej->getId()}",
            $this->obtenirToken($joueur),
            ['statut' => 'refuse'],
        );

        $this->assertStatut(200);
        $this->assertSame('refuse', $this->reponseJson()['statut']);
    }

    public function testClubRefuseDemande(): void
    {
        $football = $this->getSport('Football');
        $club     = $this->creerUtilisateur('club.b3b@test.fr', TypeUtilisateur::Club, $football);
        $joueur   = $this->creerUtilisateur('joueur.b3b@test.fr', TypeUtilisateur::Joueur, $football);
        $equipe   = $this->creerEquipe($club, $football, 'AS B3b');

        $ej = $this->creerMembreEquipe(
            $joueur, $equipe, StatutMembreEquipe::EnAttente, OrigineMembreEquipe::DemandeJoueur,
        );

        $this->requeteAuth(
            'PATCH',
            "/api/equipes/{$equipe->getId()}/membres/{$ej->getId()}",
            $this->obtenirToken($club),
            ['statut' => 'refuse'],
        );

        $this->assertStatut(200);
        $this->assertSame('refuse', $this->reponseJson()['statut']);
    }

    // =========================================================================
    // B5 — Retirer un membre : le gestionnaire est protégé
    // =========================================================================

    public function testGestionnaireNePeutPasEtreRetire(): void
    {
        $football = $this->getSport('Football');
        $club     = $this->creerUtilisateur('club.b5a@test.fr', TypeUtilisateur::Club, $football);
        $equipe   = $this->creerEquipe($club, $football, 'AS B5a');

        // Le club est gestionnaire de son équipe (role=Gestionnaire)
        $gest = $this->creerMembreEquipe(
            $club,
            $equipe,
            StatutMembreEquipe::Confirme,
            OrigineMembreEquipe::InvitationClub,
            RoleEquipe::Gestionnaire,
        );

        $this->requeteAuth(
            'DELETE',
            "/api/equipes/{$equipe->getId()}/membres/{$gest->getId()}",
            $this->obtenirToken($club),
        );

        $this->assertStatut(422);
    }

    public function testMembreJoueurPeutEtreRetireParClub(): void
    {
        $football = $this->getSport('Football');
        $club     = $this->creerUtilisateur('club.b5b@test.fr', TypeUtilisateur::Club, $football);
        $joueur   = $this->creerUtilisateur('joueur.b5b@test.fr', TypeUtilisateur::Joueur, $football);
        $equipe   = $this->creerEquipe($club, $football, 'AS B5b');

        $ej = $this->creerMembreEquipe(
            $joueur, $equipe, StatutMembreEquipe::Confirme, OrigineMembreEquipe::DemandeJoueur,
        );

        $this->requeteAuth(
            'DELETE',
            "/api/equipes/{$equipe->getId()}/membres/{$ej->getId()}",
            $this->obtenirToken($club),
        );

        $this->assertStatut(204);
    }

    // =========================================================================
    // B6 — Nouvelle demande possible après un refus
    // =========================================================================

    public function testNouvelleDemandeApresRefusPossible(): void
    {
        $football = $this->getSport('Football');
        $club     = $this->creerUtilisateur('club.b6@test.fr', TypeUtilisateur::Club, $football);
        $joueur   = $this->creerUtilisateur('joueur.b6@test.fr', TypeUtilisateur::Joueur, $football);
        $equipe   = $this->creerEquipe($club, $football, 'AS B6');

        // Adhésion précédemment refusée (B6 — le joueur a le droit de retenter)
        $this->creerMembreEquipe(
            $joueur, $equipe, StatutMembreEquipe::Refuse, OrigineMembreEquipe::DemandeJoueur,
        );

        // Nouvelle demande du joueur — doit aboutir à 201
        $this->requeteAuth('POST', "/api/equipes/{$equipe->getId()}/membres", $this->obtenirToken($joueur));

        $this->assertStatut(201);
    }

    // =========================================================================
    // E1 — Un joueur ne peut rejoindre qu'une seule équipe par sport
    // =========================================================================

    public function testJoueurDejaConfirmeNePeutPasRejoindreDeuxiemeEquipeMomeSport(): void
    {
        $football = $this->getSport('Football');
        $clubA    = $this->creerUtilisateur('clubA.e1@test.fr', TypeUtilisateur::Club, $football);
        $clubB    = $this->creerUtilisateur('clubB.e1@test.fr', TypeUtilisateur::Club, $football);
        $joueur   = $this->creerUtilisateur('joueur.e1@test.fr', TypeUtilisateur::Joueur, $football);
        $equipeA  = $this->creerEquipe($clubA, $football, 'AS E1a');
        $equipeB  = $this->creerEquipe($clubB, $football, 'AS E1b');

        // Joueur déjà confirmé dans equipeA (Football)
        $this->creerMembreEquipe(
            $joueur, $equipeA, StatutMembreEquipe::Confirme, OrigineMembreEquipe::DemandeJoueur,
        );

        // Tentative de rejoindre equipeB (même sport Football) → doit être refusée (E1)
        $this->requeteAuth('POST', "/api/equipes/{$equipeB->getId()}/membres", $this->obtenirToken($joueur));

        $this->assertStatut(422);
    }
}
