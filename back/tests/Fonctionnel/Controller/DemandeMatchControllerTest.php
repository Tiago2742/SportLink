<?php

namespace App\Tests\Fonctionnel\Controller;

use App\Entity\DemandeMatch;
use App\Entity\Game;
use App\Entity\Utilisateur;
use App\Enum\StatutDemande;
use App\Enum\StatutGame;
use App\Enum\TypeUtilisateur;
use App\Tests\Fonctionnel\BaseTestFonctionnel;

class DemandeMatchControllerTest extends BaseTestFonctionnel
{
    // ── Helper privé ──────────────────────────────────────────────────────────

    private function creerDemande(
        Game $match,
        Utilisateur $demandeur,
        StatutDemande $statut = StatutDemande::EnAttente,
    ): DemandeMatch {
        $demande = new DemandeMatch();
        $demande->setGame($match);
        $demande->setDemandeur($demandeur);
        $demande->setStatut($statut);
        $this->em->persist($demande);
        $this->em->flush();
        return $demande;
    }

    // =========================================================================
    // POST /{id}/demandes — demander
    // =========================================================================

    public function testDemanderParCreateurRefuse(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('dm.creator.refuse@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatch($joueur1, $tennis, '+30 days');

        // Le créateur ne peut pas faire une demande sur son propre match
        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/demandes", $this->obtenirToken($joueur1));

        $this->assertStatut(403);
    }

    public function testDemanderMatchNonEnAttenteRefuse(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('dm.statut.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('dm.statut.j2@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatchAvecStatut($joueur1, $tennis, StatutGame::Confirme, '+30 days');

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/demandes", $this->obtenirToken($joueur2));

        $this->assertStatut(422);
    }

    public function testDemanderSportNonDeclareRefuse(): void
    {
        $tennis   = $this->getSport('Tennis');
        $football = $this->getSport('Football');
        $joueur1  = $this->creerUtilisateur('dm.sport.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        // joueur2 a déclaré Football, pas Tennis
        $joueur2  = $this->creerUtilisateur('dm.sport.j2@test.fr', TypeUtilisateur::Joueur, $football);

        $match = $this->creerMatch($joueur1, $tennis, '+30 days');

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/demandes", $this->obtenirToken($joueur2));

        $this->assertStatut(422);
    }

    public function testDemanderReussit(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('dm.ok.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('dm.ok.j2@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatch($joueur1, $tennis, '+30 days');

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/demandes", $this->obtenirToken($joueur2));

        $this->assertStatut(201);
        $corps = $this->reponseJson();
        $this->assertSame('en_attente', $corps['statut']);
    }

    public function testDemanderDeuxFoisEnAttenteRefuse(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('dm.double.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('dm.double.j2@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatch($joueur1, $tennis, '+30 days');
        $this->creerDemande($match, $joueur2, StatutDemande::EnAttente);

        // Deuxième tentative alors qu'une demande est déjà en attente → 409
        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/demandes", $this->obtenirToken($joueur2));

        $this->assertStatut(409);
    }

    public function testDemanderApresRefusReactive(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('dm.reactiv.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('dm.reactiv.j2@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatch($joueur1, $tennis, '+30 days');
        $this->creerDemande($match, $joueur2, StatutDemande::Refusee);

        // Re-demande après refus : réactivation, pas d'insertion → 201
        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/demandes", $this->obtenirToken($joueur2));

        $this->assertStatut(201);
        $corps = $this->reponseJson();
        $this->assertSame('en_attente', $corps['statut']);
    }

    // =========================================================================
    // GET /{id}/demandes — lister
    // =========================================================================

    public function testListerDemandesParCreateurReussit(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('dm.list.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('dm.list.j2@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur3 = $this->creerUtilisateur('dm.list.j3@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatch($joueur1, $tennis, '+30 days');
        $this->creerDemande($match, $joueur2);
        $this->creerDemande($match, $joueur3, StatutDemande::Refusee);

        // Sans filtre : toutes les demandes
        $this->requeteAuth('GET', "/api/matchs/{$match->getId()}/demandes", $this->obtenirToken($joueur1));

        $this->assertStatut(200);
        $corps = $this->reponseJson();
        $this->assertCount(2, $corps);
    }

    public function testListerDemandesFiltreSurStatut(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('dm.filtre.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('dm.filtre.j2@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur3 = $this->creerUtilisateur('dm.filtre.j3@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatch($joueur1, $tennis, '+30 days');
        $this->creerDemande($match, $joueur2, StatutDemande::EnAttente);
        $this->creerDemande($match, $joueur3, StatutDemande::Refusee);

        // Filtre ?statut=en_attente : 1 seule demande
        $this->requeteAuth('GET', "/api/matchs/{$match->getId()}/demandes?statut=en_attente", $this->obtenirToken($joueur1));

        $this->assertStatut(200);
        $this->assertCount(1, $this->reponseJson());
    }

    public function testListerDemandesParNonCreateurRefuse(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('dm.list403.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('dm.list403.j2@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatch($joueur1, $tennis, '+30 days');

        $this->requeteAuth('GET', "/api/matchs/{$match->getId()}/demandes", $this->obtenirToken($joueur2));

        $this->assertStatut(403);
    }

    // =========================================================================
    // GET /{id}/ma-demande
    // =========================================================================

    public function testMaDemandeAbsenteRetourne404(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('dm.mia.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('dm.mia.j2@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatch($joueur1, $tennis, '+30 days');

        $this->requeteAuth('GET', "/api/matchs/{$match->getId()}/ma-demande", $this->obtenirToken($joueur2));

        $this->assertStatut(404);
    }

    public function testMaDemandeExistanteRetourne200(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('dm.mib.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('dm.mib.j2@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match   = $this->creerMatch($joueur1, $tennis, '+30 days');
        $demande = $this->creerDemande($match, $joueur2);

        $this->requeteAuth('GET', "/api/matchs/{$match->getId()}/ma-demande", $this->obtenirToken($joueur2));

        $this->assertStatut(200);
        $corps = $this->reponseJson();
        $this->assertSame($demande->getId(), $corps['id']);
        $this->assertSame('en_attente', $corps['statut']);
    }

    // =========================================================================
    // PATCH /{id}/demandes/{demandeId} — repondre
    // =========================================================================

    public function testRefuserDemandeReussit(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('dm.refus.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('dm.refus.j2@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match   = $this->creerMatch($joueur1, $tennis, '+30 days');
        $demande = $this->creerDemande($match, $joueur2);

        $this->requeteAuth(
            'PATCH',
            "/api/matchs/{$match->getId()}/demandes/{$demande->getId()}",
            $this->obtenirToken($joueur1),
            ['statut' => 'refusee'],
        );

        $this->assertStatut(200);
        $this->assertSame('refusee', $this->reponseJson()['statut']);
    }

    public function testAccepterDemandeReussitEtConfirmeLeMatch(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('dm.accept.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('dm.accept.j2@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match   = $this->creerMatch($joueur1, $tennis, '+30 days');
        $demande = $this->creerDemande($match, $joueur2);

        $this->requeteAuth(
            'PATCH',
            "/api/matchs/{$match->getId()}/demandes/{$demande->getId()}",
            $this->obtenirToken($joueur1),
            ['statut' => 'acceptee'],
        );

        $this->assertStatut(200);
        $this->assertSame('acceptee', $this->reponseJson()['statut']);

        // Le match doit maintenant être confirmé (2 camps)
        $this->em->refresh($match);
        $this->assertSame(StatutGame::Confirme, $match->getStatut());
        $this->assertCount(2, $match->getCamps());
    }

    public function testAccepterDemandeEtPurgeLesAutresEnAttente(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('dm.purge.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('dm.purge.j2@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur3 = $this->creerUtilisateur('dm.purge.j3@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match    = $this->creerMatch($joueur1, $tennis, '+30 days');
        $demande2 = $this->creerDemande($match, $joueur2);
        $demande3 = $this->creerDemande($match, $joueur3);

        // Créateur accepte joueur2 → joueur3 doit être purgé (refusée)
        $this->requeteAuth(
            'PATCH',
            "/api/matchs/{$match->getId()}/demandes/{$demande2->getId()}",
            $this->obtenirToken($joueur1),
            ['statut' => 'acceptee'],
        );

        $this->assertStatut(200);

        $this->em->refresh($demande3);
        $this->assertSame(StatutDemande::Refusee, $demande3->getStatut());
    }

    public function testRepondreDemandeParNonCreateurRefuse(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('dm.rep403.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('dm.rep403.j2@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur3 = $this->creerUtilisateur('dm.rep403.j3@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match   = $this->creerMatch($joueur1, $tennis, '+30 days');
        $demande = $this->creerDemande($match, $joueur2);

        // joueur3 (non-créateur) tente de répondre
        $this->requeteAuth(
            'PATCH',
            "/api/matchs/{$match->getId()}/demandes/{$demande->getId()}",
            $this->obtenirToken($joueur3),
            ['statut' => 'acceptee'],
        );

        $this->assertStatut(403);
    }

    public function testAccepterDemandeNonEnAttenteRefuse(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('dm.nonea.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('dm.nonea.j2@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match   = $this->creerMatch($joueur1, $tennis, '+30 days');
        $demande = $this->creerDemande($match, $joueur2, StatutDemande::Refusee);

        $this->requeteAuth(
            'PATCH',
            "/api/matchs/{$match->getId()}/demandes/{$demande->getId()}",
            $this->obtenirToken($joueur1),
            ['statut' => 'acceptee'],
        );

        $this->assertStatut(422);
    }

    // =========================================================================
    // DELETE /{id}/demandes/{demandeId} — annuler
    // =========================================================================

    public function testAnnulerDemandeReussit(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('dm.annul.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('dm.annul.j2@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match   = $this->creerMatch($joueur1, $tennis, '+30 days');
        $demande = $this->creerDemande($match, $joueur2);

        $this->requeteAuth(
            'DELETE',
            "/api/matchs/{$match->getId()}/demandes/{$demande->getId()}",
            $this->obtenirToken($joueur2),
        );

        $this->assertStatut(204);

        $this->em->refresh($demande);
        $this->assertSame(StatutDemande::Annulee, $demande->getStatut());
    }

    public function testAnnulerDemandeParAutreUtilisateurRefuse(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('dm.ann403.j1@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('dm.ann403.j2@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur3 = $this->creerUtilisateur('dm.ann403.j3@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match   = $this->creerMatch($joueur1, $tennis, '+30 days');
        $demande = $this->creerDemande($match, $joueur2);

        // joueur3 tente d'annuler la demande de joueur2
        $this->requeteAuth(
            'DELETE',
            "/api/matchs/{$match->getId()}/demandes/{$demande->getId()}",
            $this->obtenirToken($joueur3),
        );

        $this->assertStatut(403);
    }
}
