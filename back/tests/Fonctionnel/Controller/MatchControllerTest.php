<?php

namespace App\Tests\Fonctionnel\Controller;

use App\Entity\Game;
use App\Entity\MatchCamp;
use App\Entity\Sport;
use App\Entity\Utilisateur;
use App\Enum\RoleMatchCamp;
use App\Enum\StatutGame;
use App\Enum\StatutMatchCamp;
use App\Enum\TypeSport;
use App\Enum\TypeUtilisateur;
use App\Tests\Fonctionnel\BaseTestFonctionnel;

class MatchControllerTest extends BaseTestFonctionnel
{
    // ---- Helper privé (état hors-service, bypass rules métier intentionnel) ---

    /**
     * Crée un match Terminé avec 2 camps confirmés (sport individuel).
     * État valide pour saisir un résultat.
     */
    private function creerMatchTermineDeuxCamps(
        Utilisateur $joueur1,
        Utilisateur $joueur2,
        Sport $sport,
    ): Game {
        $match = new Game();
        $match->setSport($sport);
        $match->setDateMatch(new \DateTime('-1 day'));
        $match->setLieu('Terrain test');
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
    // F3/F4 — Cohérence type utilisateur / type sport à la création
    // =========================================================================

    public function testClubNePeutPasCreerMatchIndividuel(): void
    {
        $tennis   = $this->getSport('Tennis');
        $football = $this->getSport('Football');
        $club     = $this->creerUtilisateur('club.individuel@test.fr', TypeUtilisateur::Club, $football);

        $this->requeteAuth('POST', '/api/matchs', $this->obtenirToken($club), [
            'sportId'   => $tennis->getId(),
            'dateMatch' => (new \DateTime('+30 days'))->format('Y-m-d H:i'),
            'lieu'      => 'Court test',
        ]);

        $this->assertStatut(403);
    }

    public function testJoueurNePeutPasCreerMatchCollectif(): void
    {
        $football = $this->getSport('Football');
        $joueur   = $this->creerUtilisateur('joueur.collectif@test.fr', TypeUtilisateur::Joueur, $football);

        $this->requeteAuth('POST', '/api/matchs', $this->obtenirToken($joueur), [
            'sportId'   => $football->getId(),
            'dateMatch' => (new \DateTime('+30 days'))->format('Y-m-d H:i'),
            'lieu'      => 'Stade test',
        ]);

        $this->assertStatut(403);
    }

    public function testJoueurPeutCreerMatchIndividuel(): void
    {
        $tennis = $this->getSport('Tennis');
        $joueur = $this->creerUtilisateur('joueur.tennis@test.fr', TypeUtilisateur::Joueur, $tennis);

        $this->requeteAuth('POST', '/api/matchs', $this->obtenirToken($joueur), [
            'sportId'   => $tennis->getId(),
            'dateMatch' => (new \DateTime('+30 days'))->format('Y-m-d H:i'),
            'lieu'      => 'Court central',
        ]);

        $this->assertStatut(201);
    }

    public function testClubPeutCreerMatchCollectif(): void
    {
        $football = $this->getSport('Football');
        $club     = $this->creerUtilisateur('club.football@test.fr', TypeUtilisateur::Club, $football);
        $equipe   = $this->creerEquipe($club, $football, 'AS Test FC');

        $this->requeteAuth('POST', '/api/matchs', $this->obtenirToken($club), [
            'sportId'   => $football->getId(),
            'equipeId'  => $equipe->getId(),
            'dateMatch' => (new \DateTime('+30 days'))->format('Y-m-d H:i'),
            'lieu'      => 'Stade municipal',
        ]);

        $this->assertStatut(201);
    }

    // =========================================================================
    // F6 — Permissions saisie résultat
    // =========================================================================

    public function testNonParticipantNePeutPasSaisirResultat(): void
    {
        $tennis   = $this->getSport('Tennis');
        $joueur1  = $this->creerUtilisateur('j1.f6@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2  = $this->creerUtilisateur('j2.f6@test.fr', TypeUtilisateur::Joueur, $tennis);
        $etranger = $this->creerUtilisateur('etranger.f6@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatchTermineDeuxCamps($joueur1, $joueur2, $tennis);

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/resultat", $this->obtenirToken($etranger), [
            'scoreCamp1' => 3,
            'scoreCamp2' => 1,
        ]);

        $this->assertStatut(403);
    }

    public function testParticipantCamp2PeutSaisirResultat(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('j1.camp2@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('j2.camp2@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatchTermineDeuxCamps($joueur1, $joueur2, $tennis);

        // joueur2 = camp_2, non-créateur — doit quand même pouvoir saisir
        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/resultat", $this->obtenirToken($joueur2), [
            'scoreCamp1' => 2,
            'scoreCamp2' => 1,
        ]);

        $this->assertStatut(201);
    }

    // =========================================================================
    // A1 — Rejoindre un match : gardes de statut
    // =========================================================================

    public function testRejoindreMatchTermineEchoue(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('j1.rejoin.t@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('j2.rejoin.t@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatchAvecStatut($joueur1, $tennis, StatutGame::Termine);

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/demandes", $this->obtenirToken($joueur2));

        $this->assertStatut(422);
    }

    public function testRejoindreMatchAnnuleEchoue(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('j1.rejoin.a@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('j2.rejoin.a@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatchAvecStatut($joueur1, $tennis, StatutGame::Annule);

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/demandes", $this->obtenirToken($joueur2));

        $this->assertStatut(422);
    }

    public function testDemanderRejoindreMatchEnAttenteFuturReussit(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('j1.rejoin.ok@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('j2.rejoin.ok@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatch($joueur1, $tennis, '+30 days');

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/demandes", $this->obtenirToken($joueur2));

        $this->assertStatut(201);
    }

    // =========================================================================
    // A4 — Supprimer un match
    // =========================================================================

    public function testNonCreateurNePeutPasSupprimerMatch(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('j1.del@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('j2.del@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatch($joueur1, $tennis, '+30 days');

        $this->requeteAuth('DELETE', "/api/matchs/{$match->getId()}", $this->obtenirToken($joueur2));

        $this->assertStatut(403);
    }

    public function testSupprimerMatchTermineEchoue(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('j1.del.t@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatchAvecStatut($joueur1, $tennis, StatutGame::Termine);

        // Le créateur lui-même ne peut pas supprimer un match terminé (A4)
        $this->requeteAuth('DELETE', "/api/matchs/{$match->getId()}", $this->obtenirToken($joueur1));

        $this->assertStatut(422);
    }

    // =========================================================================
    // A5/A6 — Saisir un résultat : gardes de statut et double saisie
    // =========================================================================

    public function testSaisirResultatMatchNonTermineEchoue(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('j1.res.nt@test.fr', TypeUtilisateur::Joueur, $tennis);

        // Match en_attente, date future — statut non "terminé" → 422
        $match = $this->creerMatch($joueur1, $tennis, '+30 days');

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/resultat", $this->obtenirToken($joueur1), [
            'scoreCamp1' => 2,
            'scoreCamp2' => 1,
        ]);

        $this->assertStatut(422);
    }

    public function testSaisirResultatMatchTermineReussit(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('j1.res.ok@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('j2.res.ok@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatchTermineDeuxCamps($joueur1, $joueur2, $tennis);

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/resultat", $this->obtenirToken($joueur1), [
            'scoreCamp1' => 3,
            'scoreCamp2' => 2,
        ]);

        $this->assertStatut(201);
        $reponse = $this->reponseJson();
        $this->assertSame(3, $reponse['scoreCamp1']);
        $this->assertSame(2, $reponse['scoreCamp2']);
    }

    public function testDoubleSaisieResultatEchoue(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('j1.double@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('j2.double@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatchTermineDeuxCamps($joueur1, $joueur2, $tennis);
        $token = $this->obtenirToken($joueur1);

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/resultat", $token, [
            'scoreCamp1' => 3,
            'scoreCamp2' => 2,
        ]);
        $this->assertStatut(201);

        // Deuxième saisie : interdit (A6 / R3c)
        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/resultat", $token, [
            'scoreCamp1' => 1,
            'scoreCamp2' => 0,
        ]);
        $this->assertStatut(422);
    }

    // =========================================================================
    // A7 — Messages sur match annulé
    // =========================================================================

    public function testEnvoyerMessageMatchAnnuleEchoue(): void
    {
        $tennis   = $this->getSport('Tennis');
        $createur = $this->creerUtilisateur('createur.msg@test.fr', TypeUtilisateur::Joueur, $tennis);

        // Le créateur est toujours considéré participant (estParticipant) :
        // passe la garde 403, puis frappe la garde 422 sur statut annulé.
        $match = $this->creerMatchAvecStatut($createur, $tennis, StatutGame::Annule);

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/messages", $this->obtenirToken($createur), [
            'contenu' => 'Message sur match annulé',
        ]);

        $this->assertStatut(422);
    }

    // =========================================================================
    // C1 — Validation des scores
    // =========================================================================

    public function testScoreNegatifRefuse(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('j1.c1a@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('j2.c1a@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatchTermineDeuxCamps($joueur1, $joueur2, $tennis);

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/resultat", $this->obtenirToken($joueur1), [
            'scoreCamp1' => -1,
            'scoreCamp2' => 2,
        ]);

        $this->assertStatut(422);
    }

    public function testScoreIndividuelTropEleveRefuse(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('j1.c1b@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('j2.c1b@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatchTermineDeuxCamps($joueur1, $joueur2, $tennis);

        // Tennis : max 5 par camp ; 6 dépasse la borne
        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/resultat", $this->obtenirToken($joueur1), [
            'scoreCamp1' => 6,
            'scoreCamp2' => 3,
        ]);

        $this->assertStatut(422);
    }

    public function testScoreCollectifTropEleveRefuse(): void
    {
        $football = $this->getSport('Football');
        $club     = $this->creerUtilisateur('club.c1c@test.fr', TypeUtilisateur::Club, $football);

        // La validation du score se fait avant le contrôle du nombre de camps :
        // un match avec un seul camp suffit pour déclencher le rejet de score > 200.
        $match = $this->creerMatchAvecStatut($club, $football, StatutGame::Termine);

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/resultat", $this->obtenirToken($club), [
            'scoreCamp1' => 201,
            'scoreCamp2' => 0,
        ]);

        $this->assertStatut(422);
    }

    public function testScoreCollectifValideAccepte(): void
    {
        $football = $this->getSport('Football');
        $club1    = $this->creerUtilisateur('club1.c1d@test.fr', TypeUtilisateur::Club, $football);
        $club2    = $this->creerUtilisateur('club2.c1d@test.fr', TypeUtilisateur::Club, $football);
        $equipe1  = $this->creerEquipe($club1, $football, 'AS C1d A');
        $equipe2  = $this->creerEquipe($club2, $football, 'AS C1d B');

        $match = new Game();
        $match->setSport($football);
        $match->setDateMatch(new \DateTime('-1 day'));
        $match->setLieu('Stade C1d');
        $match->setStatut(StatutGame::Termine);
        $match->setCreateur($club1);
        $this->em->persist($match);

        $camp1 = new MatchCamp();
        $camp1->setRole(RoleMatchCamp::Camp1);
        $camp1->setStatut(StatutMatchCamp::Confirme);
        $camp1->setEquipe($equipe1);
        $match->addCamp($camp1);
        $this->em->persist($camp1);

        $camp2 = new MatchCamp();
        $camp2->setRole(RoleMatchCamp::Camp2);
        $camp2->setStatut(StatutMatchCamp::Confirme);
        $camp2->setEquipe($equipe2);
        $match->addCamp($camp2);
        $this->em->persist($camp2);

        $this->em->flush();

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/resultat", $this->obtenirToken($club1), [
            'scoreCamp1' => 2,
            'scoreCamp2' => 1,
        ]);

        $this->assertStatut(201);
    }

    // =========================================================================
    // C2 — Niveau incompatible avec le sport du match
    // =========================================================================

    public function testModifierMatchNiveauIncompatibleRefuse(): void
    {
        $tennis   = $this->getSport('Tennis');
        $football = $this->getSport('Football');
        $joueur   = $this->creerUtilisateur('joueur.c2b@test.fr', TypeUtilisateur::Joueur, $tennis);
        $niveauFoot = $this->getPremierNiveau($football);

        $match = $this->creerMatch($joueur, $tennis, '+30 days');

        // Tenter de modifier le match en spécifiant un niveau Football (R4)
        $this->requeteAuth('PUT', "/api/matchs/{$match->getId()}", $this->obtenirToken($joueur), [
            'niveauRequisId' => $niveauFoot->getId(),
        ]);

        $this->assertStatut(422);
    }

    // =========================================================================
    // C7 — Date future obligatoire + niveau déduit automatiquement
    // =========================================================================

    public function testCreerMatchDatePasseeRefuse(): void
    {
        $tennis = $this->getSport('Tennis');
        $joueur = $this->creerUtilisateur('joueur.c7a@test.fr', TypeUtilisateur::Joueur, $tennis);

        $this->requeteAuth('POST', '/api/matchs', $this->obtenirToken($joueur), [
            'sportId'   => $tennis->getId(),
            'dateMatch' => (new \DateTime('-1 day'))->format('Y-m-d H:i'),
            'lieu'      => 'Court test',
        ]);

        $this->assertStatut(422);
        $this->assertStringContainsString('futur', $this->reponseJson()['erreur']);
    }

    public function testNiveauDeduiteJoueurIndividuel(): void
    {
        $tennis = $this->getSport('Tennis');
        $joueur = $this->creerUtilisateur('joueur.c7b@test.fr', TypeUtilisateur::Joueur, $tennis);

        $this->requeteAuth('POST', '/api/matchs', $this->obtenirToken($joueur), [
            'sportId'   => $tennis->getId(),
            'dateMatch' => (new \DateTime('+30 days'))->format('Y-m-d H:i'),
            'lieu'      => 'Court central',
        ]);

        $this->assertStatut(201);
        $reponse = $this->reponseJson();
        $this->assertNotNull($reponse['niveauRequis'], 'Le niveau requis doit être déduit du profil du joueur.');
        $niveauAttendu = $this->getPremierNiveau($tennis);
        $this->assertSame($niveauAttendu->getId(), $reponse['niveauRequis']['id']);
    }

    public function testNiveauDeduiteEquipeCollectif(): void
    {
        $football = $this->getSport('Football');
        $club     = $this->creerUtilisateur('club.c7c@test.fr', TypeUtilisateur::Club, $football);
        $equipe   = $this->creerEquipe($club, $football, 'AS Niveau FC');

        // Ajouter un niveau à l'équipe (non défini par défaut dans creerEquipe)
        $niveauFoot = $this->getPremierNiveau($football);
        $equipe->setNiveau($niveauFoot);
        $this->em->flush();

        $this->requeteAuth('POST', '/api/matchs', $this->obtenirToken($club), [
            'sportId'   => $football->getId(),
            'equipeId'  => $equipe->getId(),
            'dateMatch' => (new \DateTime('+30 days'))->format('Y-m-d H:i'),
            'lieu'      => 'Stade municipal',
        ]);

        $this->assertStatut(201);
        $reponse = $this->reponseJson();
        $this->assertNotNull($reponse['niveauRequis'], 'Le niveau requis doit être déduit du niveau de l\'équipe.');
        $this->assertSame($niveauFoot->getId(), $reponse['niveauRequis']['id']);
    }

    // =========================================================================
    // C3 — XOR équipe/joueur à l'ajout d'un camp
    // =========================================================================

    public function testDemanderRejoindreMatchIndividuelAvecEquipeRefuse(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('j1.c3@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('j2.c3@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatch($joueur1, $tennis, '+30 days');

        // Match individuel : fournir equipeId est interdit (422)
        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/demandes", $this->obtenirToken($joueur2), [
            'equipeId' => 999,
        ]);

        $this->assertStatut(422);
    }

    // =========================================================================
    // C6 — Demande refusée sur un match déjà complet
    // =========================================================================

    public function testDemanderRejoindreMatchDejaCompletRefuse(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('j1.c6@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('j2.c6@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur3 = $this->creerUtilisateur('j3.c6@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatch($joueur1, $tennis, '+30 days');

        // Ajout direct de camp_2 sans passer par le service (statut reste EnAttente)
        $camp2 = new MatchCamp();
        $camp2->setRole(RoleMatchCamp::Camp2);
        $camp2->setStatut(StatutMatchCamp::Confirme);
        $camp2->setJoueur($joueur2);
        $match->addCamp($camp2);
        $this->em->persist($camp2);
        $this->em->flush();

        // Joueur3 tente de demander alors que le match est déjà complet (2 camps)
        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/demandes", $this->obtenirToken($joueur3));

        $this->assertStatut(422);
    }

    // =========================================================================
    // Restriction POST /camps — non-créateur reçoit 403
    // =========================================================================

    public function testAjouterCampParNonCreateurRefuse(): void
    {
        $tennis  = $this->getSport('Tennis');
        $joueur1 = $this->creerUtilisateur('j1.403.camps@test.fr', TypeUtilisateur::Joueur, $tennis);
        $joueur2 = $this->creerUtilisateur('j2.403.camps@test.fr', TypeUtilisateur::Joueur, $tennis);

        $match = $this->creerMatch($joueur1, $tennis, '+30 days');

        $this->requeteAuth('POST', "/api/matchs/{$match->getId()}/camps", $this->obtenirToken($joueur2));

        $this->assertStatut(403);
    }
}
