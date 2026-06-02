<?php

namespace App\DataFixtures;

use App\Entity\Disputer;
use App\Entity\Equipe;
use App\Entity\EquipeJoueur;
use App\Entity\Game;
use App\Entity\Message;
use App\Entity\Participation;
use App\Entity\Resultat;
use App\Entity\Utilisateur;
use App\Entity\UtilisateurSport;
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
        $sports  = SportNiveaux::getSports();
        $villes  = ['Paris', 'Lyon', 'Marseille', 'Lille', 'Arras', 'Bordeaux'];
        $prenoms = ['Jean', 'Marc', 'Thomas', 'Pierre', 'Antoine', 'Lucas', 'Hugo', 'Léa', 'Marie', 'Julie'];
        $noms    = ['Dupont', 'Dubois', 'Martin', 'Leroy', 'Moreau', 'Bernard', 'Petit', 'Durand', 'Robert', 'Richard'];

        // ---------- UTILISATEURS ----------
        $utilisateurs = [];
        for ($i = 0; $i < 10; $i++) {
            $u = new Utilisateur();
            $u->setEmail(strtolower($prenoms[$i]) . '.' . strtolower($noms[$i]) . '@test.com');
            $u->setNom($noms[$i]);
            $u->setPrenom($prenoms[$i]);
            $u->setRoles(['ROLE_USER']);
            $u->setPassword($this->hasher->hashPassword($u, 'password'));
            $u->setType($i < 3 ? 'organisateur' : 'joueur');
            $u->setLocalisation($villes[$i % count($villes)]);
            $u->setDateInscription(new \DateTime('-' . rand(1, 200) . ' days'));

            // 1 à 3 sports avec niveaux cohérents
            $nbSports = rand(1, 3);
            $sportsChoisis = (array) array_rand(array_flip($sports), $nbSports);
            foreach ($sportsChoisis as $sport) {
                $niveaux = SportNiveaux::getNiveaux($sport);
                $us = new UtilisateurSport();
                $us->setSport($sport);
                $us->setNiveau($niveaux[array_rand($niveaux)]);
                $u->addSport($us);
                $manager->persist($us);
            }

            $manager->persist($u);
            $utilisateurs[] = $u;
        }

        // ---------- ÉQUIPES ----------
        $nomsEquipes = ['Les Aigles', 'FC Dynamite', 'Team Rocket', 'Les Lions', 'Spikers', 'Eagles'];
        $equipes = [];
        for ($i = 0; $i < 6; $i++) {
            $sport = $sports[$i % count($sports)];
            $niveaux = SportNiveaux::getNiveaux($sport);
            $e = new Equipe();
            $e->setNom($nomsEquipes[$i]);
            $e->setSport($sport);
            $e->setNiveau($niveaux[array_rand($niveaux)]);
            $e->setLocalisation($villes[$i % count($villes)]);
            $e->setCreateur($utilisateurs[$i % count($utilisateurs)]);
            $manager->persist($e);
            $equipes[] = $e;

            // 3 à 5 membres par équipe
            $nbMembres = rand(3, 5);
            for ($j = 0; $j < $nbMembres; $j++) {
                $ej = new EquipeJoueur();
                $ej->setEquipe($e);
                $ej->setUtilisateur($utilisateurs[($i + $j) % count($utilisateurs)]);
                $ej->setRole($j === 0 ? 'capitaine' : 'joueur');
                $manager->persist($ej);
            }
        }

        // ---------- MATCHS (Game) ----------
        $lieux   = ['Stade Municipal', 'Gymnase Central', 'Court 5', 'Centre Sportif', 'Terrain Central'];
        $statuts = ['ouvert', 'ouvert', 'terminé'];
        $games = [];
        for ($i = 0; $i < 8; $i++) {
            $sport = $sports[$i % count($sports)];
            $niveaux = SportNiveaux::getNiveaux($sport);
            $statut = $statuts[$i % count($statuts)];
            $g = new Game();
            $g->setSport($sport);
            $offset = $statut === 'terminé' ? '-' . rand(1, 30) . ' days' : '+' . rand(1, 30) . ' days';
            $g->setDateMatch(new \DateTime($offset));
            $g->setLieu($lieux[$i % count($lieux)]);
            $g->setNiveauRequis($niveaux[array_rand($niveaux)]);
            $g->setStatut($statut);
            $g->setCreateur($utilisateurs[$i % count($utilisateurs)]);
            $manager->persist($g);
            $games[] = $g;

            // Participations
            $nbPart = rand(2, 4);
            for ($j = 0; $j < $nbPart; $j++) {
                $p = new Participation();
                $p->setGame($g);
                $p->setUtilisateur($utilisateurs[($i + $j) % count($utilisateurs)]);
                $p->setStatut(['invité', 'confirmé', 'refusé'][rand(0, 2)]);
                $manager->persist($p);
            }

            // Deux équipes
            $d1 = new Disputer();
            $d1->setGame($g);
            $d1->setEquipe($equipes[$i % count($equipes)]);
            $d1->setRole('equipe_1');
            $manager->persist($d1);
            $d2 = new Disputer();
            $d2->setGame($g);
            $d2->setEquipe($equipes[($i + 1) % count($equipes)]);
            $d2->setRole('equipe_2');
            $manager->persist($d2);

            // Message
            $m = new Message();
            $m->setGame($g);
            $m->setExpediteur($utilisateurs[$i % count($utilisateurs)]);
            $m->setContenu('Salut, on confirme pour le match ?');
            $m->setDateEnvoi(new \DateTime('-' . rand(1, 10) . ' days'));
            $manager->persist($m);

            if ($statut === 'terminé') {
                $r = new Resultat();
                $r->setGame($g);
                $r->setScoreEquipe1(rand(0, 5));
                $r->setScoreEquipe2(rand(0, 5));
                $manager->persist($r);
            }
        }

        $manager->flush();
    }
}
