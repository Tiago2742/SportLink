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
use App\Enum\OrigineMembreEquipe;
use App\Enum\RoleEquipe;
use App\Enum\RoleMatchCamp;
use App\Enum\StatutGame;
use App\Enum\StatutMatchCamp;
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
        /** @var array<string, array<string, Niveau>> $niveaux  libelle → Niveau */
        $niveaux   = [];
        $sportsCol = [];  // noms des sports collectifs
        $sportsInd = [];  // noms des sports individuels

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

        // ─── Index croisé sportNom → utilisateurs ───────────────
        /** @var array<string, Utilisateur[]> $joueursDuSport  sport → joueurs */
        $joueursDuSport = array_fill_keys(array_keys(SportNiveaux::CATALOGUE), []);

        /** @var array<int, string[]> $sportsDeclares  spl_id → sportNoms */
        $sportsDeclares = [];

        // ═══════════════════════════════════════════════════════
        // 2. CLUBS DE TEST (sports collectifs explicites)
        // ═══════════════════════════════════════════════════════
        $clubs = [];

        $testClubsData = [
            ['club1@test.com', 'AS Arras Sport',           'Arras',
             'https://ui-avatars.com/api/?name=AS+Arras&size=200&background=2e7d32&color=ffffff&bold=true&rounded=true',
             ['Football', 'Handball']],
            ['club2@test.com', 'FC Lyon Métropole',        'Lyon',
             'https://ui-avatars.com/api/?name=FC+Lyon&size=200&background=c62828&color=ffffff&bold=true&rounded=true',
             ['Football', 'Basketball']],
            ['club3@test.com', 'Olympique Marseille Club', 'Marseille',
             'https://ui-avatars.com/api/?name=OM&size=200&background=009fda&color=ffffff&bold=true&rounded=true',
             ['Volleyball', 'Rugby']],
        ];

        foreach ($testClubsData as [$email, $nom, $ville, $logo, $sportsClub]) {
            $u = $this->makeUser($email, $nom, null, TypeUtilisateur::Club, $ville);
            $u->setLogo($logo);
            $this->ajouterSports($u, $sportsClub, $sports, $niveaux);
            $this->save($u);
            $clubs[] = $u;
            $sportsDeclares[spl_object_id($u)] = $sportsClub;
        }

        // ═══════════════════════════════════════════════════════
        // 3. JOUEURS DE TEST (sports explicites variés)
        // ═══════════════════════════════════════════════════════
        $joueurs = [];

        $testJoueursData = [
            ['jean.dupont@test.com',    'Dupont',  'Jean',    ['Football', 'Tennis']],
            ['marc.dubois@test.com',    'Dubois',  'Marc',    ['Football', 'Badminton']],
            ['thomas.martin@test.com',  'Martin',  'Thomas',  ['Handball', 'Tennis']],
            ['pierre.leroy@test.com',   'Leroy',   'Pierre',  ['Basketball', 'Badminton']],
            ['antoine.moreau@test.com', 'Moreau',  'Antoine', ['Volleyball', 'Badminton']],
            ['lea.bernard@test.com',    'Bernard', 'Léa',     ['Rugby', 'Tennis']],
            ['marie.petit@test.com',    'Petit',   'Marie',   ['Football', 'Handball', 'Tennis']],
        ];

        foreach ($testJoueursData as [$email, $nom, $prenom, $sportsJ]) {
            $u = $this->makeUser($email, $nom, $prenom, TypeUtilisateur::Joueur, $faker->city());
            $this->ajouterSports($u, $sportsJ, $sports, $niveaux);
            $this->save($u);
            $joueurs[] = $u;
            $sportsDeclares[spl_object_id($u)] = $sportsJ;
            foreach ($sportsJ as $s) {
                $joueursDuSport[$s][] = $u;
            }
        }

        // Marqueur de fin des comptes de test (indices 0–6)
        $nbJoueursTest = count($joueurs);

        // ═══════════════════════════════════════════════════════
        // 4. CLUBS GÉNÉRÉS (12 → total 15)
        //    Déclarent 1 ou 2 sports collectifs uniquement
        // ═══════════════════════════════════════════════════════
        $prefixesClub = ['AS', 'FC', 'US', 'SC', 'CA', 'Stade', 'Club Sportif', 'Racing Club', 'Entente Sportive'];
        for ($i = 0; $i < 12; $i++) {
            $pool      = $sportsCol;
            shuffle($pool);
            $sportsGen = array_slice($pool, 0, rand(1, 2));

            $u = $this->makeUser(
                "club{$i}@gen.sportlink.fr",
                $faker->randomElement($prefixesClub) . ' ' . $faker->city(),
                null,
                TypeUtilisateur::Club,
                $faker->city(),
            );
            $this->ajouterSports($u, $sportsGen, $sports, $niveaux);
            $this->save($u);
            $clubs[] = $u;
            $sportsDeclares[spl_object_id($u)] = $sportsGen;
        }

        // ═══════════════════════════════════════════════════════
        // 5. JOUEURS GÉNÉRÉS (73 → total 80)
        //    Déclarent 1 à 3 sports (tous types)
        // ═══════════════════════════════════════════════════════
        $sportsNoms = array_keys(SportNiveaux::CATALOGUE);
        for ($i = 0; $i < 73; $i++) {
            $pool      = $sportsNoms;
            shuffle($pool);
            $sportsGen = array_slice($pool, 0, rand(1, 3));

            $u = $this->makeUser(
                "joueur{$i}@gen.sportlink.fr",
                $faker->lastName(),
                $faker->firstName(),
                TypeUtilisateur::Joueur,
                $faker->city(),
            );
            $this->ajouterSports($u, $sportsGen, $sports, $niveaux);
            $this->save($u);
            $joueurs[] = $u;
            $sportsDeclares[spl_object_id($u)] = $sportsGen;
            foreach ($sportsGen as $s) {
                $joueursDuSport[$s][] = $u;
            }
        }
        $manager->flush();

        // ═══════════════════════════════════════════════════════
        // 6. ÉQUIPES (~30)
        //    Clubs de test : config fixe
        //    Clubs générés : 1-2 équipes par sport déclaré
        // ═══════════════════════════════════════════════════════
        /** @var Equipe[] $equipes */
        $equipes      = [];
        $equipesSport = array_fill_keys($sportsCol, []);  // sportNom → Equipe[]
        $equipesClub  = [];                               // spl_id($club) → Equipe[]
        $prefEq       = ['FC', 'AS', 'US', 'SC', 'Club', 'Les', 'Team', 'Racing', 'Stade'];

        $creerEquipe = function (Utilisateur $club, string $sNom) use (
            &$equipes, &$equipesSport, &$equipesClub, $prefEq, $sports, $niveaux, $faker
        ): Equipe {
            $nivsArr = array_values($niveaux[$sNom]);
            $e = new Equipe();
            $e->setNom($faker->randomElement($prefEq) . ' ' . $faker->lastName());
            $e->setSport($sports[$sNom]);
            $e->setNiveau($nivsArr[array_rand($nivsArr)]);
            $e->setLocalisation($club->getLocalisation() ?? $faker->city());
            $e->setClub($club);
            $this->save($e);

            $ej = new EquipeJoueur();
            $ej->setEquipe($e)->setUtilisateur($club)
               ->setRole(RoleEquipe::Gestionnaire)
               ->setStatut(StatutMembreEquipe::Confirme)
               ->setOrigine(OrigineMembreEquipe::InvitationClub);
            $this->save($ej);

            $equipes[] = $e;
            $equipesSport[$sNom][] = $e;
            $equipesClub[spl_object_id($club)][] = $e;

            return $e;
        };

        // ── Clubs de test (config fixe) ──
        $clubsTestEquipes = [
            [$clubs[0], 'Football',   3],
            [$clubs[0], 'Handball',   2],
            [$clubs[1], 'Football',   3],
            [$clubs[1], 'Basketball', 2],
            [$clubs[2], 'Volleyball', 2],
            [$clubs[2], 'Rugby',      2],
        ];
        foreach ($clubsTestEquipes as [$club, $sNom, $nbEq]) {
            for ($k = 0; $k < $nbEq; $k++) {
                $creerEquipe($club, $sNom);
            }
        }

        // ── Clubs générés : 1-2 équipes par sport déclaré ──
        foreach (array_slice($clubs, 3) as $club) {
            foreach ($sportsDeclares[spl_object_id($club)] ?? [] as $sNom) {
                $nbEq = $faker->numberBetween(1, 2);
                for ($k = 0; $k < $nbEq; $k++) {
                    $creerEquipe($club, $sNom);
                }
            }
        }
        $manager->flush();

        // ═══════════════════════════════════════════════════════
        // 7. ADHÉSIONS générales
        //    Source : $joueursDuSport[sport] → respect R3b
        //    Règle : max 1 équipe CONFIRMÉE par sport par joueur
        //    Skip : les 7 comptes de test joueur (gérés en §8)
        // ═══════════════════════════════════════════════════════
        // confirmeParSport[ spl_id($joueur) ][ sportNom ] = true
        $confirmeParSport = [];
        // dejaMembre[ spl_id($joueur) ][ spl_id($equipe) ] = true
        $dejaMembre = [];

        $joueursTestIds = array_map('spl_object_id', array_slice($joueurs, 0, $nbJoueursTest));

        foreach ($equipes as $equipe) {
            $sNom = $equipe->getSport()->getNom();

            // Candidats = joueurs qui déclarent ce sport, hors comptes de test
            $candidats = array_values(array_filter(
                $joueursDuSport[$sNom],
                fn ($j) => !\in_array(spl_object_id($j), $joueursTestIds, true),
            ));
            if (empty($candidats)) {
                continue;
            }

            shuffle($candidats);
            $objectif = $faker->numberBetween(3, 8);
            $ajouts   = 0;

            foreach ($candidats as $joueur) {
                if ($ajouts >= $objectif) {
                    break;
                }

                $jId = spl_object_id($joueur);
                $eId = spl_object_id($equipe);
                if (!empty($dejaMembre[$jId][$eId])) {
                    continue;
                }

                $roll = $faker->numberBetween(1, 100);

                if ($roll <= 65) {
                    if (!empty($confirmeParSport[$jId][$sNom])) {
                        continue;
                    }
                    $statut  = StatutMembreEquipe::Confirme;
                    $origine = $faker->boolean(60)
                        ? OrigineMembreEquipe::InvitationClub
                        : OrigineMembreEquipe::DemandeJoueur;
                    $confirmeParSport[$jId][$sNom] = true;
                } elseif ($roll <= 80) {
                    $statut  = StatutMembreEquipe::EnAttente;
                    $origine = OrigineMembreEquipe::InvitationClub;
                } else {
                    $statut  = StatutMembreEquipe::EnAttente;
                    $origine = OrigineMembreEquipe::DemandeJoueur;
                }

                $ej = new EquipeJoueur();
                $ej->setEquipe($equipe)->setUtilisateur($joueur)
                   ->setRole(RoleEquipe::Joueur)
                   ->setStatut($statut)
                   ->setOrigine($origine);
                $this->save($ej);
                $dejaMembre[$jId][$eId] = true;
                $ajouts++;
            }
        }
        $manager->flush();

        // ═══════════════════════════════════════════════════════
        // 8. CAS DE TEST — SCÉNARIOS CONCENTRÉS
        //    Memberships ajoutés sans doublon (via $dejaMembre)
        // ═══════════════════════════════════════════════════════
        $filterBySport = fn (array $eqs, string $s): array => array_values(
            array_filter($eqs, fn (Equipe $e) => $e->getSport()->getNom() === $s),
        );

        $cId0 = spl_object_id($clubs[0]);
        $cId1 = spl_object_id($clubs[1]);
        $cId2 = spl_object_id($clubs[2]);

        $eqC1F = $filterBySport($equipesClub[$cId0] ?? [], 'Football');    // 3 équipes
        $eqC1H = $filterBySport($equipesClub[$cId0] ?? [], 'Handball');    // 2 équipes
        $eqC2F = $filterBySport($equipesClub[$cId1] ?? [], 'Football');    // 3 équipes
        $eqC2B = $filterBySport($equipesClub[$cId1] ?? [], 'Basketball'); // 2 équipes
        $eqC3V = $filterBySport($equipesClub[$cId2] ?? [], 'Volleyball'); // 2 équipes
        $eqC3R = $filterBySport($equipesClub[$cId2] ?? [], 'Rugby');       // 2 équipes

        // Helper : ajouter membership sans doublon
        $addM = function (
            Equipe $equipe,
            Utilisateur $joueur,
            StatutMembreEquipe $statut,
            OrigineMembreEquipe $origine,
        ) use (&$dejaMembre): void {
            $jId = spl_object_id($joueur);
            $eId = spl_object_id($equipe);
            if (!empty($dejaMembre[$jId][$eId])) {
                return;
            }
            $ej = new EquipeJoueur();
            $ej->setEquipe($equipe)->setUtilisateur($joueur)
               ->setRole(RoleEquipe::Joueur)
               ->setStatut($statut)
               ->setOrigine($origine);
            $this->save($ej);
            $dejaMembre[$jId][$eId] = true;
        };

        // ── club1 : demandes de joueurs déclarant Football ──
        $jFoot = array_values(array_filter(
            $joueursDuSport['Football'],
            fn ($j) => !\in_array(spl_object_id($j), $joueursTestIds, true),
        ));
        foreach ($eqC1F as $idx => $eq) {
            for ($d = 0; $d < 3 && isset($jFoot[$idx * 6 + $d]); $d++) {
                $addM($eq, $jFoot[$idx * 6 + $d], StatutMembreEquipe::EnAttente, OrigineMembreEquipe::DemandeJoueur);
            }
        }

        // ── club1 : demandes de joueurs déclarant Handball ──
        $jHand = array_values(array_filter(
            $joueursDuSport['Handball'],
            fn ($j) => !\in_array(spl_object_id($j), $joueursTestIds, true),
        ));
        foreach ($eqC1H as $idx => $eq) {
            for ($d = 0; $d < 2 && isset($jHand[$idx * 4 + $d]); $d++) {
                $addM($eq, $jHand[$idx * 4 + $d], StatutMembreEquipe::EnAttente, OrigineMembreEquipe::DemandeJoueur);
            }
        }

        // ── club2 : demandes sur Football ──
        foreach ($eqC2F as $idx => $eq) {
            for ($d = 0; $d < 2 && isset($jFoot[20 + $idx * 4 + $d]); $d++) {
                $addM($eq, $jFoot[20 + $idx * 4 + $d], StatutMembreEquipe::EnAttente, OrigineMembreEquipe::DemandeJoueur);
            }
        }

        // ── club3 : demandes Volleyball + Rugby ──
        $jVoll = array_values(array_filter(
            $joueursDuSport['Volleyball'],
            fn ($j) => !\in_array(spl_object_id($j), $joueursTestIds, true),
        ));
        $jRugb = array_values(array_filter(
            $joueursDuSport['Rugby'],
            fn ($j) => !\in_array(spl_object_id($j), $joueursTestIds, true),
        ));
        foreach ($eqC3V as $idx => $eq) {
            for ($d = 0; $d < 2 && isset($jVoll[$idx * 3 + $d]); $d++) {
                $addM($eq, $jVoll[$idx * 3 + $d], StatutMembreEquipe::EnAttente, OrigineMembreEquipe::DemandeJoueur);
            }
        }
        foreach ($eqC3R as $idx => $eq) {
            for ($d = 0; $d < 2 && isset($jRugb[$idx * 3 + $d]); $d++) {
                $addM($eq, $jRugb[$idx * 3 + $d], StatutMembreEquipe::EnAttente, OrigineMembreEquipe::DemandeJoueur);
            }
        }

        // ── jean.dupont (Football + Tennis) ──
        // Invitation club2 → 1 invitation Football à accepter/refuser
        [$jean, $marc, $thomas, $pierre, $antoine, $lea, $marie] = $joueurs;
        if (!empty($eqC2F)) {
            $addM($eqC2F[0], $jean, StatutMembreEquipe::EnAttente, OrigineMembreEquipe::InvitationClub);
        }

        // ── marc.dubois (Football + Badminton) ──
        // Invitation club1 → 1 invitation Football
        if (!empty($eqC1F)) {
            $addM($eqC1F[0], $marc, StatutMembreEquipe::EnAttente, OrigineMembreEquipe::InvitationClub);
        }

        // ── thomas.martin (Handball + Tennis) ──
        // Invitation club1 → 1 invitation Handball
        if (!empty($eqC1H)) {
            $addM($eqC1H[0], $thomas, StatutMembreEquipe::EnAttente, OrigineMembreEquipe::InvitationClub);
        }

        // ── pierre.leroy (Basketball + Badminton) ──
        // Invitation club2 → Basketball
        if (!empty($eqC2B)) {
            $addM($eqC2B[0], $pierre, StatutMembreEquipe::EnAttente, OrigineMembreEquipe::InvitationClub);
        }

        // ── antoine.moreau (Volleyball + Badminton) ──
        // Invitation club3 → Volleyball
        if (!empty($eqC3V)) {
            $addM($eqC3V[0], $antoine, StatutMembreEquipe::EnAttente, OrigineMembreEquipe::InvitationClub);
        }

        // ── lea.bernard (Rugby + Tennis) ──
        // Invitation club3 → Rugby
        if (!empty($eqC3R)) {
            $addM($eqC3R[0], $lea, StatutMembreEquipe::EnAttente, OrigineMembreEquipe::InvitationClub);
        }

        // ── marie.petit (Football + Handball + Tennis) ──
        // 2 invitations dans des sports différents (légal par règle métier)
        if (count($eqC1F) >= 2) {
            $addM($eqC1F[1], $marie, StatutMembreEquipe::EnAttente, OrigineMembreEquipe::InvitationClub);
        } elseif (!empty($eqC1F)) {
            $addM($eqC1F[0], $marie, StatutMembreEquipe::EnAttente, OrigineMembreEquipe::InvitationClub);
        }
        if (!empty($eqC1H)) {
            $addM($eqC1H[0], $marie, StatutMembreEquipe::EnAttente, OrigineMembreEquipe::InvitationClub);
        }

        $manager->flush();

        // ═══════════════════════════════════════════════════════
        // 9. MATCHS (~200) + CAMPS + RÉSULTATS + MESSAGES
        //
        //    Statuts : 30 % en_attente | 20 % confirme
        //              40 % termine    | 10 % annule
        //    Types   : 60 % collectif  | 40 % individuel
        //
        //    R5 : equipe obligatoire si collectif, joueur si individuel
        //    R3 : résultat uniquement pour termine (2 camps, date passée)
        //    3b : joueurs individuels tirés depuis $joueursDuSport[sport]
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

        $statutPool = [
            StatutGame::EnAttente, StatutGame::EnAttente, StatutGame::EnAttente,
            StatutGame::Confirme,  StatutGame::Confirme,
            StatutGame::Termine,   StatutGame::Termine, StatutGame::Termine, StatutGame::Termine,
            StatutGame::Annule,
        ];

        for ($i = 0; $i < 185; $i++) {
            $statut    = $faker->randomElement($statutPool);
            $collectif = $faker->boolean(60);

            // Choisir un sport cohérent avec le type
            $sNom = null;
            if ($collectif) {
                $colPool = array_values(array_filter($sportsCol, fn ($s) => !empty($equipesSport[$s])));
                if (empty($colPool)) {
                    $collectif = false;
                } else {
                    $sNom = $faker->randomElement($colPool);
                }
            }
            if (!$collectif) {
                $indPool = array_values(array_filter($sportsInd, fn ($s) => count($joueursDuSport[$s]) >= 2));
                if (empty($indPool)) {
                    continue;
                }
                $sNom = $faker->randomElement($indPool);
            }

            $nivsArr = array_values($niveaux[$sNom]);
            $date    = match ($statut) {
                StatutGame::Termine  => $faker->dateTimeBetween('-180 days', '-1 day'),
                StatutGame::Confirme => $faker->dateTimeBetween('+1 day', '+90 days'),
                StatutGame::Annule   => $faker->boolean()
                    ? $faker->dateTimeBetween('-60 days', '-1 day')
                    : $faker->dateTimeBetween('+1 day', '+60 days'),
                default              => $faker->dateTimeBetween('+1 day', '+120 days'),
            };

            $g = new Game();
            $g->setSport($sports[$sNom]);
            if ($faker->boolean(70)) {
                $g->setNiveauRequis($nivsArr[array_rand($nivsArr)]);
            }
            $g->setDateMatch($date);
            $g->setLieu($faker->randomElement($lieuxBase) . ', ' . $faker->city());
            $g->setStatut($statut);
            if ($faker->boolean(25)) {
                $g->setDescription($faker->sentence($faker->numberBetween(5, 12)));
            }

            $nbCamps = 0;

            if ($collectif) {
                // ── R5 : equipe obligatoire ──
                $eqList = $equipesSport[$sNom];
                $eq1    = $faker->randomElement($eqList);
                $g->setCreateur($eq1->getClub());
                $this->save($g);

                $c1 = new MatchCamp();
                $c1->setGame($g)->setRole(RoleMatchCamp::Camp1)
                   ->setStatut(StatutMatchCamp::Confirme)->setEquipe($eq1);
                $this->save($c1);
                $nbCamps++;

                // Camp 2 : absent pour annule ou ~40 % des en_attente (rejoignables)
                $ajouterCamp2 = $statut !== StatutGame::Annule
                    && count($eqList) > 1
                    && !($statut === StatutGame::EnAttente && $faker->boolean(40));

                if ($ajouterCamp2) {
                    $eqAutres = array_values(array_filter($eqList, fn (Equipe $e) => $e !== $eq1));
                    if (!empty($eqAutres)) {
                        $eq2   = $faker->randomElement($eqAutres);
                        $stat2 = \in_array($statut, [StatutGame::Confirme, StatutGame::Termine], true)
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
                // ── R5 : joueur obligatoire (3b : depuis $joueursDuSport) ──
                $jPool = $joueursDuSport[$sNom];
                $j1    = $faker->randomElement($jPool);
                $g->setCreateur($j1);
                $this->save($g);

                $c1 = new MatchCamp();
                $c1->setGame($g)->setRole(RoleMatchCamp::Camp1)
                   ->setStatut(StatutMatchCamp::Confirme)->setJoueur($j1);
                $this->save($c1);
                $nbCamps++;

                // Camp 2 : absent pour annule ou ~45 % des en_attente
                $ajouterCamp2 = $statut !== StatutGame::Annule
                    && !($statut === StatutGame::EnAttente && $faker->boolean(45));

                if ($ajouterCamp2) {
                    $autres = array_values(array_filter($jPool, fn ($j) => $j !== $j1));
                    if (!empty($autres)) {
                        $j2    = $faker->randomElement($autres);
                        $stat2 = \in_array($statut, [StatutGame::Confirme, StatutGame::Termine], true)
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

            // R3 : résultat si termine + 2 camps (date passée par construction)
            if ($statut === StatutGame::Termine && $nbCamps === 2) {
                $r = new Resultat();
                $r->setGame($g);
                $r->setScoreCamp1($faker->numberBetween(0, $collectif ? 10 : 5));
                $r->setScoreCamp2($faker->numberBetween(0, $collectif ? 10 : 5));
                $this->save($r);
            }

            // Messages sur ~40 % des matchs (1 à 4 par match)
            if ($faker->boolean(40)) {
                for ($m = 0; $m < $faker->numberBetween(1, 4); $m++) {
                    $offset  = $faker->numberBetween(0, 12);
                    $msgDate = (clone $date)->modify("-{$offset} days");
                    $msg     = new Message();
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
        // 10. MATCHS REJOIGNABLES — COMPTES DE TEST
        //     Un seul camp, statut en_attente, date future
        // ═══════════════════════════════════════════════════════

        // ── club1 : 1 match ouvert par équipe (5 matchs) ──
        foreach ($equipesClub[$cId0] ?? [] as $eqTest) {
            $sNom    = $eqTest->getSport()->getNom();
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
               ->setStatut(StatutMatchCamp::Confirme)->setEquipe($eqTest);
            $this->save($c1);
        }

        // ── Joueurs de test : matchs individuels ouverts ──
        foreach ([
            [$jean,    'Tennis'],
            [$thomas,  'Tennis'],
            [$lea,     'Tennis'],
            [$marc,    'Badminton'],
            [$antoine, 'Badminton'],
        ] as [$jTest, $sNom]) {
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

    /** Crée un Utilisateur sans sports (sports ajoutés via ajouterSports). */
    private function makeUser(
        string $email,
        string $nom,
        ?string $prenom,
        TypeUtilisateur $type,
        string $localisation,
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

        return $u;
    }

    /**
     * Ajoute des UtilisateurNiveau à un utilisateur pour une liste de sports.
     *
     * @param string[]                             $sportsNoms
     * @param array<string, Sport>                 $sports
     * @param array<string, array<string, Niveau>> $niveaux
     */
    private function ajouterSports(
        Utilisateur $u,
        array $sportsNoms,
        array $sports,
        array $niveaux,
    ): void {
        foreach ($sportsNoms as $sNom) {
            if (!isset($sports[$sNom])) {
                continue;
            }
            $nivsArr = array_values($niveaux[$sNom]);
            $un      = new UtilisateurNiveau();
            $un->setSport($sports[$sNom]);
            $un->setNiveau($nivsArr[array_rand($nivsArr)]);
            $u->addNiveau($un);
        }
    }
}
