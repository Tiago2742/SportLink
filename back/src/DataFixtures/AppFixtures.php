<?php

namespace App\DataFixtures;

use App\Entity\Equipe;
use App\Entity\EquipeJoueur;
use App\Entity\Game;
use App\Entity\MatchCamp;
use App\Entity\Message;
use App\Entity\Niveau;
use App\Entity\Resultat;
use App\Entity\Sport;
use App\Entity\Utilisateur;
use App\Entity\UtilisateurNiveau;
use App\Enum\RoleEquipe;
use App\Enum\RoleMatchCamp;
use App\Enum\StatutGame;
use App\Enum\StatutMatchCamp;
use App\Enum\OrigineMembreEquipe;
use App\Enum\StatutMembreEquipe;
use App\Enum\TypeSport;
use App\Enum\TypeUtilisateur;
use App\Reference\SportNiveaux;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $villes  = ['Paris', 'Lyon', 'Marseille', 'Lille', 'Arras', 'Bordeaux'];
        $prenoms = ['Jean', 'Marc', 'Thomas', 'Pierre', 'Antoine', 'Lucas', 'Hugo', 'Léa', 'Marie', 'Julie'];
        $noms    = ['Dupont', 'Dubois', 'Martin', 'Leroy', 'Moreau', 'Bernard', 'Petit', 'Durand', 'Robert', 'Richard'];
        $clubs   = ['AS Arras Sport', 'FC Lyon Métropole', 'Olympique Marseille Club'];

        // ---------- SPORTS & NIVEAUX ----------
        $sportEntites  = []; // ['Football' => Sport, ...]
        $niveauEntites = []; // ['Football' => ['D1' => Niveau, ...], ...]

        foreach (SportNiveaux::CATALOGUE as $nom => $config) {
            $sport = new Sport();
            $sport->setNom($nom);
            $sport->setType(TypeSport::from($config['type']));
            $sportEntites[$nom]  = $sport;
            $niveauEntites[$nom] = [];

            foreach ($config['niveaux'] as $i => $libelle) {
                $niveau = new Niveau();
                $niveau->setLibelle($libelle);
                $niveau->setOrdre($i + 1);
                $sport->addNiveau($niveau);
                $niveauEntites[$nom][$libelle] = $niveau;
            }

            $manager->persist($sport);
        }

        // ---------- UTILISATEURS ----------
        $utilisateurs = [];
        for ($i = 0; $i < 10; $i++) {
            $u = new Utilisateur();
            $estClub = $i < 3;
            $u->setEmail(
                $estClub
                    ? 'club' . ($i + 1) . '@test.com'
                    : strtolower($prenoms[$i]) . '.' . strtolower($noms[$i]) . '@test.com',
            );
            if ($estClub) {
                $u->setNom($clubs[$i]);
                $u->setPrenom(null);
            } else {
                $u->setNom($noms[$i]);
                $u->setPrenom($prenoms[$i]);
            }
            $u->setRoles(['ROLE_USER']);
            $u->setPassword($this->hasher->hashPassword($u, 'password'));
            $u->setType($estClub ? TypeUtilisateur::Club : TypeUtilisateur::Joueur);
            $u->setLocalisation($villes[$i % count($villes)]);
            $u->setDateInscription(new \DateTime('-' . rand(1, 200) . ' days'));

            // 1 à 3 sports avec niveaux cohérents
            $sportsNomsTous = array_keys(SportNiveaux::CATALOGUE);
            $nbSports       = rand(1, 3);
            $sportsChoisis  = (array) array_rand(array_flip($sportsNomsTous), $nbSports);
            foreach ($sportsChoisis as $sportNom) {
                $niveauxDispo = array_values($niveauEntites[$sportNom]);
                $un = new UtilisateurNiveau();
                $un->setSport($sportEntites[$sportNom]);
                $un->setNiveau($niveauxDispo[array_rand($niveauxDispo)]);
                $u->addNiveau($un);
            }

            $manager->persist($u);
            $utilisateurs[] = $u;
        }

        // ---------- ÉQUIPES (sports collectifs uniquement) ----------
        $nomsEquipes      = ['Les Aigles', 'FC Dynamite', 'Team Rocket', 'Les Lions', 'Spikers', 'Eagles'];
        $sportsCollectifs = array_keys(array_filter(
            SportNiveaux::CATALOGUE,
            fn($c) => $c['type'] === 'collectif',
        ));
        $equipes = [];
        for ($i = 0; $i < 6; $i++) {
            $sportNom     = $sportsCollectifs[$i % count($sportsCollectifs)];
            $niveauxDispo = array_values($niveauEntites[$sportNom]);
            $e = new Equipe();
            $e->setNom($nomsEquipes[$i]);
            $e->setSport($sportEntites[$sportNom]);
            $e->setNiveau($niveauxDispo[array_rand($niveauxDispo)]);
            $e->setLocalisation($villes[$i % count($villes)]);
            if ($i < 2) {
                $e->setLogo('https://placehold.co/96x96/png?text=' . rawurlencode(substr($nomsEquipes[$i], 0, 2)));
            }
            $club = $utilisateurs[$i % 3];
            $e->setClub($club);
            $manager->persist($e);
            $equipes[] = $e;

            $gestionnaireClub = new EquipeJoueur();
            $gestionnaireClub->setEquipe($e);
            $gestionnaireClub->setUtilisateur($club);
            $gestionnaireClub->setRole(RoleEquipe::Gestionnaire);
            $gestionnaireClub->setStatut(StatutMembreEquipe::Confirme);
            $gestionnaireClub->setOrigine(OrigineMembreEquipe::InvitationClub);
            $e->addMembre($gestionnaireClub);
            $manager->persist($gestionnaireClub);

            $joueursOnly = array_values(array_filter(
                $utilisateurs,
                fn($u) => $u->getType() === TypeUtilisateur::Joueur,
            ));
            for ($j = 0; $j < rand(2, 4); $j++) {
                $ej = new EquipeJoueur();
                $ej->setEquipe($e);
                $ej->setUtilisateur($joueursOnly[($i + $j) % count($joueursOnly)]);
                $ej->setRole(RoleEquipe::Joueur);
                $ej->setStatut(StatutMembreEquipe::Confirme);
                $ej->setOrigine(
                    $j % 2 === 0
                        ? OrigineMembreEquipe::InvitationClub
                        : OrigineMembreEquipe::DemandeJoueur,
                );
                $manager->persist($ej);
            }
        }

        // Invitation club en attente (démo étape 2)
        $joueurInvite = $utilisateurs[9];
        $invitationDemo = new EquipeJoueur();
        $invitationDemo->setEquipe($equipes[0]);
        $invitationDemo->setUtilisateur($joueurInvite);
        $invitationDemo->setRole(RoleEquipe::Joueur);
        $invitationDemo->setStatut(StatutMembreEquipe::EnAttente);
        $invitationDemo->setOrigine(OrigineMembreEquipe::InvitationClub);
        $manager->persist($invitationDemo);

        // ---------- MATCHS (Game) + MatchCamp ----------
        $lieux          = ['Stade Municipal', 'Gymnase Central', 'Court 5', 'Centre Sportif', 'Terrain Central'];
        $statutsCycles  = [StatutGame::EnAttente, StatutGame::EnAttente, StatutGame::Termine];
        $sportsIndividu = array_keys(array_filter(SportNiveaux::CATALOGUE, fn($c) => $c['type'] === 'individuel'));
        $joueurs        = array_filter($utilisateurs, fn($u) => $u->getType() === TypeUtilisateur::Joueur);
        $joueurs        = array_values($joueurs);

        for ($i = 0; $i < 8; $i++) {
            $statut     = $statutsCycles[$i % count($statutsCycles)];
            $estTermine = $statut === StatutGame::Termine;

            // Alterner sports collectifs et individuels
            if ($i % 2 === 0) {
                $sportNom = $sportsCollectifs[$i % count($sportsCollectifs)];
            } else {
                $sportNom = $sportsIndividu[$i % count($sportsIndividu)];
            }

            $sportEntite  = $sportEntites[$sportNom];
            $niveauxDispo = array_values($niveauEntites[$sportNom]);

            $g = new Game();
            $g->setSport($sportEntite);
            $g->setNiveauRequis($niveauxDispo[array_rand($niveauxDispo)]);
            $offset = $estTermine ? '-' . rand(1, 30) . ' days' : '+' . rand(1, 30) . ' days';
            $g->setDateMatch(new \DateTime($offset));
            $g->setLieu($lieux[$i % count($lieux)]);
            $g->setStatut($statut);
            $g->setCreateur($utilisateurs[$i % count($utilisateurs)]);
            $manager->persist($g);

            // 2 camps selon le type de sport
            $statutCamp = $estTermine ? StatutMatchCamp::Confirme : StatutMatchCamp::Invite;

            if ($sportEntite->getType() === TypeSport::Collectif) {
                foreach ([RoleMatchCamp::Camp1, RoleMatchCamp::Camp2] as $j => $role) {
                    $camp = new MatchCamp();
                    $camp->setGame($g);
                    $camp->setRole($role);
                    $camp->setStatut($statutCamp);
                    $camp->setEquipe($equipes[($i + $j) % count($equipes)]);
                    $manager->persist($camp);
                }
            } else {
                foreach ([RoleMatchCamp::Camp1, RoleMatchCamp::Camp2] as $j => $role) {
                    $camp = new MatchCamp();
                    $camp->setGame($g);
                    $camp->setRole($role);
                    $camp->setStatut($statutCamp);
                    $camp->setJoueur($joueurs[($i + $j) % count($joueurs)]);
                    $manager->persist($camp);
                }
            }

            $m = new Message();
            $m->setGame($g);
            $m->setExpediteur($utilisateurs[$i % count($utilisateurs)]);
            $m->setContenu('Salut, on confirme pour le match ?');
            $m->setDateEnvoi(new \DateTime('-' . rand(1, 10) . ' days'));
            $manager->persist($m);

            if ($estTermine) {
                $r = new Resultat();
                $r->setGame($g);
                $r->setScoreCamp1(rand(0, 5));
                $r->setScoreCamp2(rand(0, 5));
                $manager->persist($r);
            }
        }

        $manager->flush();
    }
}
