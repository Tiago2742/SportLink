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
use Faker\Factory as FakerFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private const BATCH_SIZE = 100;
    private int $count = 0;
    private ObjectManager $em;

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {}

    /** Persist + flush par batch (sans clear : les références restent valides). */
    private function save(object $entity): void
    {
        $this->em->persist($entity);
        if (++$this->count % self::BATCH_SIZE === 0) {
            $this->em->flush();
        }
    }

    public function load(ObjectManager $manager): void
    {
        $this->em = $manager;
        $faker    = FakerFactory::create('fr_FR');
        $faker->seed(1337);

        // ═══════════════════════════════════════════════════════
        // 1. SPORTS & NIVEAUX (depuis le catalogue de référence)
        // ═══════════════════════════════════════════════════════
        /** @var array<string, Sport> $sports */
        $sports    = [];
        /** @var array<string, Niveau[]> $niveaux */
        $niveaux   = [];
        $sportsCol = [];   // noms de sports collectifs
        $sportsInd = [];   // noms de sports individuels

        foreach (SportNiveaux::CATALOGUE as $nom => $cfg) {
            $sport = new Sport();
            $sport->setNom($nom);
            $sport->setType(TypeSport::from($cfg['type']));
            $sports[$nom]  = $sport;
            $niveaux[$nom] = [];
            $cfg['type'] === 'collectif' ? $sportsCol[] = $nom : $sportsInd[] = $nom;

            foreach ($cfg['niveaux'] as $ord => $lib) {
                $niv = new Niveau();
                $niv->setLibelle($lib);
                $niv->setOrdre($ord + 1);
                $sport->addNiveau($niv);
                $niveaux[$nom][$lib] = $niv;
            }
            $this->save($sport);
        }
        $manager->flush();

        // ═══════════════════════════════════════════════════════
        // 2. COMPTES DE TEST CONNUS (password "password")
        // ═══════════════════════════════════════════════════════
        $clubs = [];
        foreach ([
            ['club1@test.com', 'AS Arras Sport'],
            ['club2@test.com', 'FC Lyon Métropole'],
            ['club3@test.com', 'Olympique Marseille Club'],
        ] as [$email, $nom]) {
            $u = $this->makeUser($email, $nom, null, TypeUtilisateur::Club, 'Arras', $sports, $niveaux);
            $this->save($u);
            $clubs[] = $u;
        }

        $joueurs = [];
        foreach ([
            ['jean.dupont@test.com',    'Dupont',  'Jean'],
            ['marc.dubois@test.com',    'Dubois',  'Marc'],
            ['thomas.martin@test.com',  'Martin',  'Thomas'],
            ['pierre.leroy@test.com',   'Leroy',   'Pierre'],
            ['antoine.moreau@test.com', 'Moreau',  'Antoine'],
            ['lea.bernard@test.com',    'Bernard', 'Léa'],
            ['marie.petit@test.com',    'Petit',   'Marie'],
        ] as [$email, $nom, $prenom]) {
            $u = $this->makeUser($email, $nom, $prenom, TypeUtilisateur::Joueur, $faker->city(), $sports, $niveaux);
            $this->save($u);
            $joueurs[] = $u;
        }

        // ═══════════════════════════════════════════════════════
        // 3. CLUBS GÉNÉRÉS (~27 → total ~30)
        // ═══════════════════════════════════════════════════════
        $prefixesClub = ['AS', 'FC', 'US', 'SC', 'CA', 'Stade', 'Club Sportif', 'Racing Club', 'Entente Sportive'];
        for ($i = 0; $i < 27; $i++) {
            $u = $this->makeUser(
                "club{$i}@gen.sportlink.fr",
                $faker->randomElement($prefixesClub) . ' ' . $faker->city(),
                null,
                TypeUtilisateur::Club,
                $faker->city(),
                $sports,
                $niveaux,
            );
            $this->save($u);
            $clubs[] = $u;
        }

        // ═══════════════════════════════════════════════════════
        // 4. JOUEURS GÉNÉRÉS (~193 → total ~200)
        // ═══════════════════════════════════════════════════════
        for ($i = 0; $i < 193; $i++) {
            $u = $this->makeUser(
                "joueur{$i}@gen.sportlink.fr",
                $faker->lastName(),
                $faker->firstName(),
                TypeUtilisateur::Joueur,
                $faker->city(),
                $sports,
                $niveaux,
            );
            $this->save($u);
            $joueurs[] = $u;
        }
        $manager->flush();

        // ═══════════════════════════════════════════════════════
        // 5. ÉQUIPES — sports collectifs uniquement (~80)
        // ═══════════════════════════════════════════════════════
        /** @var Equipe[] $equipes */
        $equipes      = [];
        $equipesSport = array_fill_keys($sportsCol, []);   // sportNom → Equipe[]
        $equipesClub  = [];                                // spl_object_id($club) → Equipe[]
        $prefEq       = ['FC', 'AS', 'US', 'SC', 'Club', 'Les', 'Team', 'Racing', 'Stade', 'Entente'];

        for ($i = 0; $i < 80; $i++) {
            $sportNom = $sportsCol[$i % count($sportsCol)];
            $nivsArr  = array_values($niveaux[$sportNom]);
            $club     = $clubs[$i % count($clubs)];

            $e = new Equipe();
            $e->setNom($faker->randomElement($prefEq) . ' ' . $faker->lastName());
            $e->setSport($sports[$sportNom]);
            $e->setNiveau($nivsArr[array_rand($nivsArr)]);
            $e->setLocalisation($faker->city());
            $e->setClub($club);
            $this->save($e);

            $equipes[]                             = $e;
            $equipesSport[$sportNom][]             = $e;
            $equipesClub[spl_object_id($club)][]   = $e;

            // Le club est gestionnaire de ses propres équipes (toujours confirmé)
            $ej = new EquipeJoueur();
            $ej->setEquipe($e);
            $ej->setUtilisateur($club);
            $ej->setRole(RoleEquipe::Gestionnaire);
            $ej->setStatut(StatutMembreEquipe::Confirme);
            $ej->setOrigine(OrigineMembreEquipe::InvitationClub);
            $this->save($ej);
        }
        $manager->flush();

        // ═══════════════════════════════════════════════════════
        // 6. ADHÉSIONS joueurs ↔ équipes
        //    Règle : max 1 équipe CONFIRMÉE par sport par joueur
        // ═══════════════════════════════════════════════════════
        // confirmeParSport[ spl_object_id($joueur) ][ $sportNom ] = true
        $confirmeParSport = [];

        foreach ($equipes as $equipe) {
            $sportNom  = $equipe->getSport()->getNom();
            $objectif  = $faker->numberBetween(3, 8);
            $ajouts    = 0;
            $candidats = $faker->randomElements($joueurs, min(25, count($joueurs)));

            foreach ($candidats as $joueur) {
                if ($ajouts >= $objectif) {
                    break;
                }

                $jId = spl_object_id($joueur);
                $roll = $faker->numberBetween(1, 100);

                if ($roll <= 68) {
                    // Confirme — vérifie la contrainte "1 équipe confirmée max par sport"
                    if (!empty($confirmeParSport[$jId][$sportNom])) {
                        continue;
                    }
                    $statut  = StatutMembreEquipe::Confirme;
                    $origine = $faker->boolean(60)
                        ? OrigineMembreEquipe::InvitationClub
                        : OrigineMembreEquipe::DemandeJoueur;
                    $confirmeParSport[$jId][$sportNom] = true;
                } elseif ($roll <= 84) {
                    // En attente — invitation du club
                    $statut  = StatutMembreEquipe::EnAttente;
                    $origine = OrigineMembreEquipe::InvitationClub;
                } else {
                    // En attente — demande du joueur
                    $statut  = StatutMembreEquipe::EnAttente;
                    $origine = OrigineMembreEquipe::DemandeJoueur;
                }

                $ej = new EquipeJoueur();
                $ej->setEquipe($equipe);
                $ej->setUtilisateur($joueur);
                $ej->setRole(RoleEquipe::Joueur);
                $ej->setStatut($statut);
                $ej->setOrigine($origine);
                $this->save($ej);
                $ajouts++;
            }
        }
        $manager->flush();

        // ═══════════════════════════════════════════════════════
        // 7. CAS DE TEST — COMPTES CONNUS
        //    Scénarios visibles dès la première connexion
        // ═══════════════════════════════════════════════════════
        $eq1C1 = $equipesClub[spl_object_id($clubs[0])] ?? [];  // équipes de club1
        $eq1C2 = $equipesClub[spl_object_id($clubs[1])] ?? [];  // équipes de club2
        $eq1C3 = $equipesClub[spl_object_id($clubs[2])] ?? [];  // équipes de club3

        // ── club1 : demandes joueurs en attente à traiter sur ses 3 équipes ──
        // Joueurs générés (index 7+) pour ne pas mélanger les comptes de test
        foreach ($eq1C1 as $eqIdx => $eqC1) {
            for ($d = 0; $d < 3; $d++) {
                $candidat = $joueurs[10 + $eqIdx * 7 + $d]; // index fixe, pas de risque doublon
                $ej = new EquipeJoueur();
                $ej->setEquipe($eqC1)->setUtilisateur($candidat)
                   ->setRole(RoleEquipe::Joueur)
                   ->setStatut(StatutMembreEquipe::EnAttente)
                   ->setOrigine(OrigineMembreEquipe::DemandeJoueur);
                $this->save($ej);
            }
        }

        // ── jean.dupont : invitations à accepter / refuser ──
        $jDupont   = $joueurs[0];
        $jDupontId = spl_object_id($jDupont);
        // Invitation de club2 vers jean.dupont
        if (!empty($eq1C2)) {
            $ej = new EquipeJoueur();
            $ej->setEquipe($eq1C2[0])->setUtilisateur($jDupont)
               ->setRole(RoleEquipe::Joueur)
               ->setStatut(StatutMembreEquipe::EnAttente)
               ->setOrigine(OrigineMembreEquipe::InvitationClub);
            $this->save($ej);
        }
        if (count($eq1C2) >= 2) {
            $ej = new EquipeJoueur();
            $ej->setEquipe($eq1C2[1])->setUtilisateur($jDupont)
               ->setRole(RoleEquipe::Joueur)
               ->setStatut(StatutMembreEquipe::EnAttente)
               ->setOrigine(OrigineMembreEquipe::InvitationClub);
            $this->save($ej);
        }
        // Invitation de club3 vers jean.dupont
        if (!empty($eq1C3)) {
            $ej = new EquipeJoueur();
            $ej->setEquipe($eq1C3[0])->setUtilisateur($jDupont)
               ->setRole(RoleEquipe::Joueur)
               ->setStatut(StatutMembreEquipe::EnAttente)
               ->setOrigine(OrigineMembreEquipe::InvitationClub);
            $this->save($ej);
        }
        // Demande envoyée de jean.dupont vers une équipe d'un club généré
        if (!empty($equipes[5])) {
            $ej = new EquipeJoueur();
            $ej->setEquipe($equipes[5])->setUtilisateur($jDupont)
               ->setRole(RoleEquipe::Joueur)
               ->setStatut(StatutMembreEquipe::EnAttente)
               ->setOrigine(OrigineMembreEquipe::DemandeJoueur);
            $this->save($ej);
        }
        // jean.dupont membre confirmé de la 1ʳᵉ équipe de club1 (si sport pas déjà pris)
        if (!empty($eq1C1)) {
            $sNomC1 = $eq1C1[0]->getSport()->getNom();
            if (empty($confirmeParSport[$jDupontId][$sNomC1])) {
                $ej = new EquipeJoueur();
                $ej->setEquipe($eq1C1[0])->setUtilisateur($jDupont)
                   ->setRole(RoleEquipe::Joueur)
                   ->setStatut(StatutMembreEquipe::Confirme)
                   ->setOrigine(OrigineMembreEquipe::InvitationClub);
                $this->save($ej);
                $confirmeParSport[$jDupontId][$sNomC1] = true;
            }
        }

        // ── Autres joueurs de test : 1-2 invitations visibles dès connexion ──
        // [1] marc.dubois ← club2, [2] thomas.martin ← club3
        // [3] pierre.leroy ← club2, [4] antoine.moreau ← club3
        // [5] lea.bernard ← club2, [6] marie.petit ← club3
        $invPairs = [
            [1, $eq1C2, 0],   // marc ← club2 équipe 0
            [2, $eq1C3, 0],   // thomas ← club3 équipe 0
            [3, $eq1C2, count($eq1C2) >= 2 ? 1 : 0],  // pierre ← club2 équipe 1
            [4, $eq1C3, count($eq1C3) >= 2 ? 1 : 0],  // antoine ← club3 équipe 1
            [5, $eq1C2, 0],   // lea ← club2 équipe 0
            [6, $eq1C3, 0],   // marie ← club3 équipe 0
        ];
        foreach ($invPairs as [$jIdx, $eqList, $eqPos]) {
            if (empty($eqList)) {
                continue;
            }
            $ej = new EquipeJoueur();
            $ej->setEquipe($eqList[$eqPos])->setUtilisateur($joueurs[$jIdx])
               ->setRole(RoleEquipe::Joueur)
               ->setStatut(StatutMembreEquipe::EnAttente)
               ->setOrigine(OrigineMembreEquipe::InvitationClub);
            $this->save($ej);
        }
        $manager->flush();

        // ═══════════════════════════════════════════════════════
        // 8. MATCHS (~500) + CAMPS + RÉSULTATS + MESSAGES
        //
        //    Statuts : 30 % en_attente | 20 % confirme
        //              40 % termine    | 10 % annule
        //    Types   : 60 % collectif  | 40 % individuel
        //
        //    R2 : confirme quand les 2 camps sont confirmés
        //    R3 : résultat uniquement pour termine (date passée, 2 camps)
        //    R5 : equipe XOR joueur selon le type de sport
        // ═══════════════════════════════════════════════════════
        $lieuxBase = [
            'Stade Municipal', 'Gymnase Central', 'Court Couvert', 'Centre Sportif',
            'Terrain Synthétique', 'Salle Omnisports', 'Complexe Sportif',
            'Hall de Sport', 'Plateau Extérieur', 'Stade de la Victoire',
            'Terrain Annexe', 'Gymnase Polyvalent',
        ];
        $msgTemplates = [
            'On confirme pour le match ?',
            'Je serai là, hâte de jouer !',
            'Quel est l\'horaire exact ?',
            'Rendez-vous au parking à 14h.',
            'Super match, à la prochaine !',
            'Besoin d\'un remplaçant, quelqu\'un de dispo ?',
            'Le terrain est réservé jusqu\'à 18h.',
            'Attention, match décalé d\'une heure.',
            'On s\'échauffe à quelle heure ?',
            'Vestiaire disponible dès 13h30.',
            'N\'oubliez pas votre maillot !',
            'Le match est annulé, on reporte la semaine prochaine.',
        ];

        // Pool pondéré pour le tirage des statuts
        $statutPool = [
            StatutGame::EnAttente, StatutGame::EnAttente, StatutGame::EnAttente,
            StatutGame::Confirme,  StatutGame::Confirme,
            StatutGame::Termine,   StatutGame::Termine, StatutGame::Termine, StatutGame::Termine,
            StatutGame::Annule,
        ];

        for ($i = 0; $i < 500; $i++) {
            $statut    = $faker->randomElement($statutPool);
            $collectif = $faker->boolean(60) && !empty($sportsCol);

            // Choisir un sport cohérent avec le type
            if ($collectif) {
                $sportNom = $faker->randomElement($sportsCol);
                if (empty($equipesSport[$sportNom])) {
                    $collectif = false;
                }
            }
            if (!$collectif) {
                $sportNom = $faker->randomElement($sportsInd);
            }

            $nivsArr = array_values($niveaux[$sportNom]);

            // Date cohérente avec le statut
            $date = match ($statut) {
                StatutGame::Termine  => $faker->dateTimeBetween('-180 days', '-1 day'),
                StatutGame::Confirme => $faker->dateTimeBetween('+1 day', '+90 days'),
                StatutGame::Annule   => $faker->boolean()
                    ? $faker->dateTimeBetween('-60 days', '-1 day')
                    : $faker->dateTimeBetween('+1 day', '+60 days'),
                default              => $faker->dateTimeBetween('+1 day', '+120 days'),
            };

            $g = new Game();
            $g->setSport($sports[$sportNom]);
            if ($faker->boolean(70)) {
                $g->setNiveauRequis($nivsArr[array_rand($nivsArr)]);
            }
            $g->setDateMatch($date);
            $g->setLieu($faker->randomElement($lieuxBase) . ', ' . $faker->city());
            $g->setStatut($statut);
            if ($faker->boolean(30)) {
                $g->setDescription($faker->sentence($faker->numberBetween(6, 15)));
            }

            $nbCamps = 0;

            if ($collectif) {
                // ── R5 : equipe obligatoire (sport collectif) ──
                $eqList = $equipesSport[$sportNom];
                $eq1    = $faker->randomElement($eqList);
                $g->setCreateur($eq1->getClub());
                $this->save($g);

                $c1 = new MatchCamp();
                $c1->setGame($g)->setRole(RoleMatchCamp::Camp1)
                   ->setStatut(StatutMatchCamp::Confirme)->setEquipe($eq1);
                $this->save($c1);
                $nbCamps++;

                // Camp 2 : absent uniquement pour annule ou si un seul équipe dans ce sport
                if ($statut !== StatutGame::Annule && count($eqList) > 1) {
                    $eqAutres = array_values(array_filter($eqList, fn(Equipe $e) => $e !== $eq1));
                    if (!empty($eqAutres)) {
                        $eq2   = $faker->randomElement($eqAutres);
                        $stat2 = in_array($statut, [StatutGame::Confirme, StatutGame::Termine], true)
                            ? StatutMatchCamp::Confirme
                            : StatutMatchCamp::Invite;

                        $c2 = new MatchCamp();
                        $c2->setGame($g)->setRole(RoleMatchCamp::Camp2)
                           ->setStatut($stat2)->setEquipe($eq2);
                        $this->save($c2);
                        $nbCamps++;
                    }
                }
            } else {
                // ── R5 : joueur obligatoire (sport individuel) ──
                $j1 = $faker->randomElement($joueurs);
                $g->setCreateur($j1);
                $this->save($g);

                $c1 = new MatchCamp();
                $c1->setGame($g)->setRole(RoleMatchCamp::Camp1)
                   ->setStatut(StatutMatchCamp::Confirme)->setJoueur($j1);
                $this->save($c1);
                $nbCamps++;

                if ($statut !== StatutGame::Annule) {
                    // Tire un 2ᵉ joueur distinct
                    $j2    = $j1;
                    $tries = 0;
                    while ($j2 === $j1 && $tries++ < 15) {
                        $j2 = $faker->randomElement($joueurs);
                    }
                    if ($j2 !== $j1) {
                        $stat2 = in_array($statut, [StatutGame::Confirme, StatutGame::Termine], true)
                            ? StatutMatchCamp::Confirme
                            : StatutMatchCamp::Invite;

                        $c2 = new MatchCamp();
                        $c2->setGame($g)->setRole(RoleMatchCamp::Camp2)
                           ->setStatut($stat2)->setJoueur($j2);
                        $this->save($c2);
                        $nbCamps++;
                    }
                }
            }

            // R3 : résultat seulement si termine + 2 camps (date passée par construction)
            if ($statut === StatutGame::Termine && $nbCamps === 2) {
                $r = new Resultat();
                $r->setGame($g);
                if ($collectif) {
                    // Score buts (0–10, validé par le frontend ≤ 200)
                    $r->setScoreCamp1($faker->numberBetween(0, 10));
                    $r->setScoreCamp2($faker->numberBetween(0, 10));
                } else {
                    // Score sets (0–5, validé par le frontend ≤ 5)
                    $r->setScoreCamp1($faker->numberBetween(0, 5));
                    $r->setScoreCamp2($faker->numberBetween(0, 5));
                }
                $this->save($r);
            }

            // Messages sur ~60 % des matchs (1 à 4 par match)
            if ($faker->boolean(60)) {
                $nbMsg = $faker->numberBetween(1, 4);
                for ($m = 0; $m < $nbMsg; $m++) {
                    $offset  = $faker->numberBetween(0, 10);
                    $msgDate = (clone $date)->modify("-{$offset} days");

                    $msg = new Message();
                    $msg->setGame($g);
                    $msg->setExpediteur($faker->randomElement($joueurs));
                    $msg->setContenu($faker->randomElement($msgTemplates));
                    $msg->setDateEnvoi($msgDate);
                    $this->save($msg);
                }
            }
        }
        $manager->flush();

        // ═══════════════════════════════════════════════════════
        // 9. MATCHS REJOIGNABLES OUVERTS (~65 matchs)
        //    statut en_attente + date future + UN SEUL camp
        //    → visibles dans Recherche par tous les adversaires
        // ═══════════════════════════════════════════════════════

        // ── 9a. Collectifs génériques : round-robin sur toutes les équipes ──
        for ($i = 0; $i < 36; $i++) {
            $eq       = $equipes[$i % count($equipes)];
            $sNom     = $eq->getSport()->getNom();
            $nivsArr  = array_values($niveaux[$sNom]);

            $g = new Game();
            $g->setSport($sports[$sNom]);
            $g->setNiveauRequis($nivsArr[array_rand($nivsArr)]);
            $g->setDateMatch($faker->dateTimeBetween('+2 days', '+90 days'));
            $g->setLieu($faker->randomElement($lieuxBase) . ', ' . $faker->city());
            $g->setStatut(StatutGame::EnAttente);
            $g->setCreateur($eq->getClub());
            if ($faker->boolean(50)) {
                $g->setDescription('Cherche adversaire · ' . $sNom);
            }
            $this->save($g);

            $c1 = new MatchCamp();
            $c1->setGame($g)->setRole(RoleMatchCamp::Camp1)
               ->setStatut(StatutMatchCamp::Confirme)->setEquipe($eq);
            $this->save($c1);
        }

        // ── 9b. Individuels génériques ──
        for ($i = 0; $i < 18; $i++) {
            $sNom    = $sportsInd[$i % count($sportsInd)];
            $nivsArr = array_values($niveaux[$sNom]);
            $joueur  = $joueurs[7 + $i];  // joueurs générés uniquement

            $g = new Game();
            $g->setSport($sports[$sNom]);
            $g->setNiveauRequis($nivsArr[array_rand($nivsArr)]);
            $g->setDateMatch($faker->dateTimeBetween('+2 days', '+90 days'));
            $g->setLieu($faker->randomElement($lieuxBase) . ', ' . $faker->city());
            $g->setStatut(StatutGame::EnAttente);
            $g->setCreateur($joueur);
            $this->save($g);

            $c1 = new MatchCamp();
            $c1->setGame($g)->setRole(RoleMatchCamp::Camp1)
               ->setStatut(StatutMatchCamp::Confirme)->setJoueur($joueur);
            $this->save($c1);
        }

        // ── 9c. Matchs ouverts de club1 (visibles dès connexion) ──
        foreach ($eq1C1 as $eqC1) {
            $sNom    = $eqC1->getSport()->getNom();
            $nivsArr = array_values($niveaux[$sNom]);

            $g = new Game();
            $g->setSport($sports[$sNom]);
            $g->setNiveauRequis($nivsArr[0]);
            $g->setDateMatch($faker->dateTimeBetween('+7 days', '+60 days'));
            $g->setLieu('Stade Municipal, Arras');
            $g->setStatut(StatutGame::EnAttente);
            $g->setDescription('Club1 cherche adversaire — ' . $sNom . ' · ' . $nivsArr[0]->getLibelle());
            $g->setCreateur($clubs[0]);
            $this->save($g);

            $c1 = new MatchCamp();
            $c1->setGame($g)->setRole(RoleMatchCamp::Camp1)
               ->setStatut(StatutMatchCamp::Confirme)->setEquipe($eqC1);
            $this->save($c1);
        }

        // ── 9d. Matchs ouverts des joueurs de test ──
        $joueursTestMatch = [
            [$joueurs[0], $sportsInd[0]],                                // jean   → individuel sport 0
            [$joueurs[2], $sportsInd[count($sportsInd) > 1 ? 1 : 0]],   // thomas → individuel sport 1
            [$joueurs[4], $sportsInd[0]],                                // antoine → individuel sport 0
        ];
        foreach ($joueursTestMatch as [$jTest, $sNom]) {
            $nivsArr = array_values($niveaux[$sNom]);

            $g = new Game();
            $g->setSport($sports[$sNom]);
            $g->setNiveauRequis($nivsArr[array_rand($nivsArr)]);
            $g->setDateMatch($faker->dateTimeBetween('+3 days', '+45 days'));
            $g->setLieu($faker->randomElement($lieuxBase) . ', ' . $faker->city());
            $g->setStatut(StatutGame::EnAttente);
            $g->setCreateur($jTest);
            $this->save($g);

            $c1 = new MatchCamp();
            $c1->setGame($g)->setRole(RoleMatchCamp::Camp1)
               ->setStatut(StatutMatchCamp::Confirme)->setJoueur($jTest);
            $this->save($c1);
        }

        $manager->flush();
    }

    /**
     * Crée un Utilisateur complet avec 1 à 3 sports/niveaux aléatoires.
     *
     * @param array<string, Sport>    $sports
     * @param array<string, Niveau[]> $niveaux
     */
    private function makeUser(
        string $email,
        string $nom,
        ?string $prenom,
        TypeUtilisateur $type,
        string $localisation,
        array $sports,
        array $niveaux,
    ): Utilisateur {
        $u = new Utilisateur();
        $u->setEmail($email);
        $u->setNom($nom);
        $u->setPrenom($prenom);
        $u->setRoles(['ROLE_USER']);
        $u->setPassword($this->hasher->hashPassword($u, 'password'));
        $u->setType($type);
        $u->setLocalisation($localisation);
        $u->setDateInscription(new \DateTime('-' . rand(1, 730) . ' days'));

        $sportsNoms = array_keys($sports);
        $nb         = rand(1, min(3, count($sportsNoms)));
        $choisis    = (array) array_rand(array_flip($sportsNoms), $nb);
        foreach ($choisis as $sNom) {
            $nivsArr = array_values($niveaux[$sNom]);
            $un      = new UtilisateurNiveau();
            $un->setSport($sports[$sNom]);
            $un->setNiveau($nivsArr[array_rand($nivsArr)]);
            $u->addNiveau($un);
        }

        return $u;
    }
}
