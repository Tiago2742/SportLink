<?php

namespace App\DataFixtures;

use App\Entity\Avis;
use App\Entity\DemandeMatch;
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
use App\Enum\StatutDemande;
use App\Enum\StatutGame;
use App\Enum\StatutMatchCamp;
use App\Enum\StatutMembreEquipe;
use App\Enum\TypeSport;
use App\Enum\TypeUtilisateur;
use App\Reference\SportNiveaux;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Jeu de données de DÉMONSTRATION (soutenance).
 *
 * Toutes les données sont FICTIVES (RGPD) : aucun nom, email ou contact réel.
 *
 * Chargement local (base recréée) :
 *   php bin/console doctrine:fixtures:load --group=demo --no-interaction
 *
 * Chargement prod (référentiel déjà présent via app:reference:load) :
 *   php bin/console doctrine:fixtures:load --group=demo --append --no-interaction
 *
 * IDEMPOTENCE : les Sport/Niveau existants sont RÉUTILISÉS, jamais recréés
 * (même stratégie que App\Command\LoadReferenceDataCommand).
 * Un garde-fou empêche le double chargement des comptes de démo (UNIQUE email).
 */
class DemoFixtures extends Fixture implements FixtureGroupInterface
{
    public const PASSWORD     = 'Demo1234!';
    public const JOUEUR_EMAIL = 'joueur@demo.fr';
    public const CLUB_EMAIL   = 'club@demo.fr';

    private const BATCH_SIZE = 100;

    /** Villes françaises réelles : nom => [latitude, longitude]. */
    private const VILLES = [
        'Lille'     => [50.6292, 3.0573],
        'Paris'     => [48.8566, 2.3522],
        'Lyon'      => [45.7640, 4.8357],
        'Marseille' => [43.2965, 5.3698],
        'Bordeaux'  => [44.8378, -0.5792],
        'Nantes'    => [47.2184, -1.5536],
        'Toulouse'  => [43.6047, 1.4442],
        'Arras'     => [50.2910, 2.7778],
    ];

    // ═══════════════════════════════════════════════════════════════════
    // CLUBS : [email, nom, ville, couleur logo, sports déclarés]
    // ═══════════════════════════════════════════════════════════════════
    private const CLUBS = [
        ['club@demo.fr',                'Olympique Lillois',     'Lille',    '1b5e20', ['Football', 'Handball']],
        ['as.bordeaux@example.com',     'AS Bordeaux Métropole', 'Bordeaux', '6a1b9a', ['Football', 'Basketball']],
        ['fc.lyon@example.com',         'FC Lyon Presqu\'île',   'Lyon',     'c62828', ['Football', 'Volleyball']],
        ['us.nantes@example.com',       'US Nantes Atlantique',  'Nantes',   '00695c', ['Handball', 'Basketball']],
        ['sc.toulouse@example.com',     'SC Toulouse Garonne',   'Toulouse', 'ef6c00', ['Rugby', 'Football']],
    ];

    // ═══════════════════════════════════════════════════════════════════
    // JOUEURS : [email, nom, prénom, ville, sports déclarés]
    // 1 seul niveau par (utilisateur, sport) → sports distincts par ligne.
    // ═══════════════════════════════════════════════════════════════════
    private const JOUEURS = [
        ['joueur@demo.fr',             'Moreau',     'Camille',  'Lille',    ['Football', 'Tennis', 'Badminton']],
        ['lucas.bernard@example.com',  'Bernard',    'Lucas',    'Lille',    ['Football', 'Tennis']],
        ['nina.lefevre@example.com',   'Lefèvre',    'Nina',     'Lille',    ['Football', 'Badminton']],
        ['hugo.marchand@example.com',  'Marchand',   'Hugo',     'Lille',    ['Football', 'Handball']],
        ['sarah.delacroix@example.com','Delacroix',  'Sarah',    'Lille',    ['Handball', 'Tennis']],
        ['yanis.fontaine@example.com', 'Fontaine',   'Yanis',    'Lille',    ['Handball', 'Badminton']],
        ['elena.rossi@example.com',    'Rossi',      'Elena',    'Lille',    ['Football', 'Handball']],
        ['thomas.gauthier@example.com','Gauthier',   'Thomas',   'Bordeaux', ['Football', 'Tennis']],
        ['maya.dubreuil@example.com',  'Dubreuil',   'Maya',     'Bordeaux', ['Football', 'Basketball']],
        ['samir.benali@example.com',   'Benali',     'Samir',    'Bordeaux', ['Basketball', 'Badminton']],
        ['clara.vasseur@example.com',  'Vasseur',    'Clara',    'Lyon',     ['Football', 'Volleyball']],
        ['noah.perrin@example.com',    'Perrin',     'Noah',     'Lyon',     ['Volleyball', 'Tennis']],
        ['ines.chauvet@example.com',   'Chauvet',    'Inès',     'Lyon',     ['Football', 'Tennis']],
        ['gabriel.roussel@example.com','Roussel',    'Gabriel',  'Nantes',   ['Handball', 'Badminton']],
        ['lina.granger@example.com',   'Granger',    'Lina',     'Nantes',   ['Handball', 'Basketball']],
        ['adam.leclerc@example.com',   'Leclerc',    'Adam',     'Nantes',   ['Basketball', 'Tennis']],
        ['jade.marceau@example.com',   'Marceau',    'Jade',     'Toulouse', ['Rugby', 'Tennis']],
        ['oscar.tissot@example.com',   'Tissot',     'Oscar',    'Toulouse', ['Rugby', 'Badminton']],
    ];

    // ═══════════════════════════════════════════════════════════════════
    // ÉQUIPES : clé => [email club, nom, sport, libellé niveau, couleur]
    // ═══════════════════════════════════════════════════════════════════
    private const EQUIPES = [
        'lille_foot'  => ['club@demo.fr',            'Olympique Lillois A',     'Football',   'D2',            '1b5e20'],
        'lille_hand'  => ['club@demo.fr',            'Olympique Lillois HB',    'Handball',   'Régionale',     '2e7d32'],
        'bdx_foot'    => ['as.bordeaux@example.com', 'AS Bordeaux Métropole',   'Football',   'D3',            '6a1b9a'],
        'bdx_basket'  => ['as.bordeaux@example.com', 'Bordeaux Basket Club',    'Basketball', 'Départementale','8e24aa'],
        'lyon_foot'   => ['fc.lyon@example.com',     'FC Lyon Presqu\'île',     'Football',   'D2',            'c62828'],
        'nantes_hand' => ['us.nantes@example.com',   'US Nantes Handball',      'Handball',   'Régionale',     '00695c'],
        'nantes_bask' => ['us.nantes@example.com',   'Nantes Basket Atlantique','Basketball', 'Départementale','00897b'],
        'tls_rugby'   => ['sc.toulouse@example.com', 'SC Toulouse XV',          'Rugby',      'Fédérale 3',    'ef6c00'],
    ];

    // ═══════════════════════════════════════════════════════════════════
    // MEMBRES : [clé équipe, email joueur, statut, origine]
    // Règle R3b : au plus 1 équipe CONFIRMÉE par (joueur, sport).
    // ═══════════════════════════════════════════════════════════════════
    private const MEMBRES = [
        // -- Olympique Lillois A (Football) — équipe du joueur de démo
        ['lille_foot',  'joueur@demo.fr',              'confirme',  'demande_joueur'],
        ['lille_foot',  'lucas.bernard@example.com',   'confirme',  'invitation_club'],
        ['lille_foot',  'nina.lefevre@example.com',    'confirme',  'demande_joueur'],
        ['lille_foot',  'hugo.marchand@example.com',   'confirme',  'invitation_club'],
        ['lille_foot',  'elena.rossi@example.com',     'en_attente','invitation_club'],
        // -- Olympique Lillois HB (Handball)
        ['lille_hand',  'hugo.marchand@example.com',   'confirme',  'invitation_club'],
        ['lille_hand',  'sarah.delacroix@example.com', 'confirme',  'demande_joueur'],
        ['lille_hand',  'yanis.fontaine@example.com',  'confirme',  'invitation_club'],
        ['lille_hand',  'elena.rossi@example.com',     'confirme',  'demande_joueur'],
        // -- AS Bordeaux Métropole (Football)
        ['bdx_foot',    'thomas.gauthier@example.com', 'confirme',  'invitation_club'],
        ['bdx_foot',    'maya.dubreuil@example.com',   'confirme',  'demande_joueur'],
        ['bdx_foot',    'ines.chauvet@example.com',    'en_attente','demande_joueur'],
        // -- Bordeaux Basket Club
        ['bdx_basket',  'maya.dubreuil@example.com',   'confirme',  'invitation_club'],
        ['bdx_basket',  'samir.benali@example.com',    'confirme',  'invitation_club'],
        ['bdx_basket',  'adam.leclerc@example.com',    'en_attente','demande_joueur'],
        // -- FC Lyon Presqu'île (Football) — invitation en attente pour le joueur de démo
        ['lyon_foot',   'clara.vasseur@example.com',   'confirme',  'invitation_club'],
        ['lyon_foot',   'ines.chauvet@example.com',    'confirme',  'demande_joueur'],
        ['lyon_foot',   'lucas.bernard@example.com',   'en_attente','invitation_club'],
        ['lyon_foot',   'joueur@demo.fr',              'en_attente','invitation_club'],
        // -- US Nantes Handball
        ['nantes_hand', 'gabriel.roussel@example.com', 'confirme',  'invitation_club'],
        ['nantes_hand', 'lina.granger@example.com',    'confirme',  'demande_joueur'],
        ['nantes_hand', 'sarah.delacroix@example.com', 'en_attente','invitation_club'],
        // -- Nantes Basket Atlantique
        ['nantes_bask', 'lina.granger@example.com',    'confirme',  'invitation_club'],
        ['nantes_bask', 'adam.leclerc@example.com',    'confirme',  'invitation_club'],
        // -- SC Toulouse XV (Rugby)
        ['tls_rugby',   'jade.marceau@example.com',    'confirme',  'invitation_club'],
        ['tls_rugby',   'oscar.tissot@example.com',    'confirme',  'demande_joueur'],
    ];

    // ═══════════════════════════════════════════════════════════════════
    // MATCHS : clé => [sport, niveau requis|null, statut, jours/aujourd'hui,
    //                  heure, ville, lieu, description|null,
    //                  camp1, camp2|null, statut camp2|null]
    // Camp : 'eq:<clé équipe>' (collectif) | 'us:<email>' (individuel) — R5.
    // ═══════════════════════════════════════════════════════════════════
    private const GAMES = [
        // ── Collectifs terminés (R3 : date passée + 2 camps confirmés) ──
        'g1' => ['Football', 'D2', 'termine', -21, '15:00', 'Lille', 'Stade Grimonprez',
            'Match amical de reprise.', 'eq:lille_foot', 'eq:bdx_foot', 'confirme'],
        'g2' => ['Handball', 'Régionale', 'termine', -35, '18:30', 'Lille', 'Salle Omnisports Vauban',
            null, 'eq:lille_hand', 'eq:nantes_hand', 'confirme'],
        'g3' => ['Football', 'D3', 'termine', -14, '16:00', 'Bordeaux', 'Stade Chaban Annexe',
            null, 'eq:bdx_foot', 'eq:lyon_foot', 'confirme'],
        // ── Collectifs confirmés (à venir, 2 camps confirmés — R2) ──
        'g4' => ['Football', 'D2', 'confirme', 10, '15:30', 'Lille', 'Stade Municipal de Lille',
            'Rencontre amicale, 2x45 min.', 'eq:lille_foot', 'eq:lyon_foot', 'confirme'],
        'g5' => ['Basketball', 'Départementale', 'confirme', 17, '19:00', 'Bordeaux', 'Gymnase Bacalan',
            null, 'eq:bdx_basket', 'eq:nantes_bask', 'confirme'],
        // ── Collectifs ouverts (1 camp, en attente de demandes) ──
        'g6' => ['Football', 'D2', 'en_attente', 24, '14:00', 'Lille', 'Stade Municipal de Lille',
            'Olympique Lillois cherche adversaire niveau D2.', 'eq:lille_foot', null, null],
        'g7' => ['Handball', 'Régionale', 'en_attente', 12, '20:00', 'Nantes', 'Palais des Sports de Beaulieu',
            'Recherche équipe régionale pour un amical.', 'eq:nantes_hand', null, null],
        'g8' => ['Basketball', 'Départementale', 'en_attente', 30, '18:00', 'Nantes', 'Gymnase Mangin',
            null, 'eq:nantes_bask', null, null],
        'g9' => ['Rugby', 'Fédérale 3', 'en_attente', 20, '15:00', 'Toulouse', 'Stade des Sept Deniers',
            'Amical de préparation, XV complet.', 'eq:tls_rugby', null, null],
        // ── Individuels terminés ──
        'g10' => ['Tennis', '30/2', 'termine', -28, '10:00', 'Lille', 'Tennis Club du Parc Barbieux',
            null, 'us:joueur@demo.fr', 'us:lucas.bernard@example.com', 'confirme'],
        'g11' => ['Badminton', 'Intermédiaire', 'termine', -9, '19:30', 'Lille', 'Halle Badminton Saint-Sauveur',
            null, 'us:joueur@demo.fr', 'us:nina.lefevre@example.com', 'confirme'],
        'g12' => ['Tennis', '15/4', 'termine', -18, '11:00', 'Bordeaux', 'Tennis Club de Bordeaux Lac',
            null, 'us:thomas.gauthier@example.com', 'us:jade.marceau@example.com', 'confirme'],
        // ── Individuels : invitation directe en attente (camp 2 invité) ──
        'g13' => ['Tennis', '30/3', 'en_attente', 5, '09:30', 'Lille', 'Court Couvert de Lambersart',
            null, 'us:joueur@demo.fr', 'us:sarah.delacroix@example.com', 'invite'],
        // ── Individuels ouverts ──
        'g14' => ['Tennis', '30/2', 'en_attente', 8, '17:00', 'Lille', 'Tennis Club du Parc Barbieux',
            'Cherche partenaire pour un set en 3 jeux gagnants.', 'us:joueur@demo.fr', null, null],
        'g15' => ['Badminton', 'Intermédiaire', 'en_attente', 15, '20:00', 'Lille', 'Halle Badminton Saint-Sauveur',
            null, 'us:yanis.fontaine@example.com', null, null],
        'g17' => ['Badminton', 'Confirmé', 'en_attente', 21, '18:30', 'Nantes', 'Gymnase de la Halvêque',
            null, 'us:gabriel.roussel@example.com', null, null],
        // ── Individuels confirmés (à venir) ──
        'g16' => ['Tennis', '15/5', 'confirme', 6, '14:00', 'Lyon', 'Tennis Club de Gerland',
            null, 'us:noah.perrin@example.com', 'us:ines.chauvet@example.com', 'confirme'],
        'g18' => ['Tennis', '30/2', 'confirme', 13, '13:00', 'Paris', 'Centre Tennis de la Porte Dorée',
            'Match retour, on se retrouve sur terre battue.', 'us:joueur@demo.fr', 'us:thomas.gauthier@example.com', 'confirme'],
    ];

    // ═══════════════════════════════════════════════════════════════════
    // DEMANDES : [clé match, email demandeur, clé équipe|null, statut]
    // UNIQUE (game, demandeur) → chaque paire est unique dans ce tableau.
    // Règles DemandeMatchService : le demandeur déclare le sport ; en
    // collectif l'équipe lui appartient et pratique ce sport ; une demande
    // acceptée correspond au camp 2 du match confirmé, les autres demandes
    // du même match sont refusées.
    // ═══════════════════════════════════════════════════════════════════
    private const DEMANDES = [
        ['g4',  'fc.lyon@example.com',         'lyon_foot',   'acceptee'],
        ['g4',  'as.bordeaux@example.com',     'bdx_foot',    'refusee'],
        ['g6',  'as.bordeaux@example.com',     'bdx_foot',    'en_attente'],
        ['g6',  'fc.lyon@example.com',         'lyon_foot',   'en_attente'],
        ['g7',  'club@demo.fr',                'lille_hand',  'en_attente'],
        ['g8',  'as.bordeaux@example.com',     'bdx_basket',  'refusee'],
        ['g14', 'ines.chauvet@example.com',    null,          'en_attente'],
        ['g14', 'adam.leclerc@example.com',    null,          'en_attente'],
        ['g15', 'joueur@demo.fr',              null,          'en_attente'],
        ['g16', 'ines.chauvet@example.com',    null,          'acceptee'],
        ['g16', 'adam.leclerc@example.com',    null,          'refusee'],
        ['g17', 'oscar.tissot@example.com',    null,          'refusee'],
    ];

    // ═══════════════════════════════════════════════════════════════════
    // RÉSULTATS : [clé match, score camp 1, score camp 2]
    // R3 : uniquement des matchs « termine » à 2 camps confirmés, 1 max.
    // R6 : scoreCamp1 ↔ camp_1, scoreCamp2 ↔ camp_2.
    // ═══════════════════════════════════════════════════════════════════
    private const RESULTATS = [
        ['g1',  3,  1],
        ['g2',  28, 31],
        ['g3',  2,  2],
        ['g10', 2,  1],
        ['g11', 2,  0],
        ['g12', 1,  2],
    ];

    // ═══════════════════════════════════════════════════════════════════
    // AVIS : [clé match, email notant, email évalué, ponctualité, fair-play, niveau conforme]
    // UNIQUE (notant, game) → 1 seule ligne par (match, notant).
    // ═══════════════════════════════════════════════════════════════════
    private const AVIS = [
        ['g1',  'club@demo.fr',                'as.bordeaux@example.com',     5, 5, 4],
        ['g1',  'as.bordeaux@example.com',     'club@demo.fr',                4, 5, 5],
        ['g2',  'club@demo.fr',                'us.nantes@example.com',       4, 4, 5],
        ['g2',  'us.nantes@example.com',       'club@demo.fr',                5, 5, 4],
        ['g3',  'as.bordeaux@example.com',     'fc.lyon@example.com',         3, 4, 4],
        ['g3',  'fc.lyon@example.com',         'as.bordeaux@example.com',     4, 4, 3],
        ['g10', 'joueur@demo.fr',              'lucas.bernard@example.com',   5, 5, 4],
        ['g10', 'lucas.bernard@example.com',   'joueur@demo.fr',              5, 4, 5],
        ['g11', 'joueur@demo.fr',              'nina.lefevre@example.com',    4, 5, 3],
        ['g11', 'nina.lefevre@example.com',    'joueur@demo.fr',              5, 5, 5],
        ['g12', 'thomas.gauthier@example.com', 'jade.marceau@example.com',    4, 4, 4],
        ['g12', 'jade.marceau@example.com',    'thomas.gauthier@example.com', 5, 4, 4],
    ];

    // ═══════════════════════════════════════════════════════════════════
    // MESSAGES : [clé match, email expéditeur, contenu, il y a N jours]
    // Expéditeurs = participants au match (occupants des camps).
    // ═══════════════════════════════════════════════════════════════════
    private const MESSAGES = [
        ['g4',  'club@demo.fr',                'Bonjour, terrain réservé de 15h à 17h30, vestiaires dispos dès 14h45.', 6],
        ['g4',  'fc.lyon@example.com',         'Parfait, on arrive en bus vers 14h30. Prévoyez-vous un arbitre ?',      5],
        ['g4',  'club@demo.fr',                'Oui, arbitre officiel confirmé. À dimanche !',                          4],
        ['g6',  'as.bordeaux@example.com',     'Bonjour, notre équipe D3 est intéressée. Le niveau D2 reste ouvert ?',  3],
        ['g6',  'club@demo.fr',                'Oui, un écart d\'une division ne pose aucun souci.',                    2],
        ['g14', 'ines.chauvet@example.com',    'Salut ! Dispo pour ce créneau, je viens de Lyon en train.',             4],
        ['g14', 'joueur@demo.fr',              'Super, je confirme le court dans la journée.',                          3],
        ['g14', 'adam.leclerc@example.com',    'Je suis aussi partant si le créneau se libère.',                        2],
        ['g18', 'thomas.gauthier@example.com', 'On garde bien 13h ? J\'ai un train qui arrive à 12h15.',                5],
        ['g18', 'joueur@demo.fr',              '13h c\'est confirmé, terre battue court 3. Bon voyage !',               4],
        ['g1',  'club@demo.fr',                'Merci pour la rencontre, très bon état d\'esprit.',                     20],
        ['g1',  'as.bordeaux@example.com',     'Merci à vous, on remet ça au retour avec plaisir.',                     19],
        ['g11', 'nina.lefevre@example.com',    'Beau match, tu progresses vite au filet !',                             8],
        ['g16', 'noah.perrin@example.com',     'Court réservé, prends une seconde raquette au cas où.',                 3],
    ];

    private ObjectManager $em;
    private int $count = 0;

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {}

    public static function getGroups(): array
    {
        return ['demo'];
    }

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
        mt_srand(2026);   // jeu de démo reproductible à l'identique

        $this->verifierAbsenceDemo();

        // 1. Référentiel (idempotent : réutilise l'existant en prod)
        [$sports, $niveaux] = $this->chargerReferentiel();

        $niv = function (string $sportNom, ?string $libelle) use ($niveaux): ?Niveau {
            if ($libelle === null) {
                return null;
            }
            if (!isset($niveaux[$sportNom][$libelle])) {
                throw new \RuntimeException(sprintf('Niveau "%s" introuvable pour le sport "%s".', $libelle, $sportNom));
            }
            return $niveaux[$sportNom][$libelle];
        };

        // 2. Utilisateurs (clubs + joueurs)
        /** @var array<string, Utilisateur> $users  email => Utilisateur */
        $users = [];

        foreach (self::CLUBS as [$email, $nom, $ville, $couleur, $sportsClub]) {
            $u = $this->makeUser($email, $nom, null, TypeUtilisateur::Club, $ville);
            $u->setLogo($this->avatarUrl($nom, $couleur));
            $this->ajouterSports($u, $sportsClub, $sports, $niveaux);
            $this->save($u);
            $users[$email] = $u;
        }

        foreach (self::JOUEURS as [$email, $nom, $prenom, $ville, $sportsJoueur]) {
            $u = $this->makeUser($email, $nom, $prenom, TypeUtilisateur::Joueur, $ville);
            $u->setLogo($this->avatarUrl($prenom . ' ' . $nom, '37474f'));
            $this->ajouterSports($u, $sportsJoueur, $sports, $niveaux);
            $this->save($u);
            $users[$email] = $u;
        }
        $manager->flush();

        $user = function (string $email) use ($users): Utilisateur {
            if (!isset($users[$email])) {
                throw new \RuntimeException(sprintf('Utilisateur de démo inconnu : %s', $email));
            }
            return $users[$email];
        };

        // 3. Équipes
        /** @var array<string, Equipe> $equipes  clé => Equipe */
        $equipes = [];

        foreach (self::EQUIPES as $cle => [$clubEmail, $nomEq, $sportNom, $libNiveau, $couleur]) {
            $club = $user($clubEmail);

            $e = new Equipe();
            $e->setNom($nomEq);
            $e->setSport($sports[$sportNom]);
            $e->setNiveau($niv($sportNom, $libNiveau));   // R4 : niveau du bon sport
            $e->setLocalisation($club->getLocalisation());
            $e->setLogo($this->avatarUrl($nomEq, $couleur));
            $e->setClub($club);
            $this->save($e);
            $equipes[$cle] = $e;

            // Le club est gestionnaire de ses propres équipes
            $ej = new EquipeJoueur();
            $ej->setEquipe($e)
               ->setUtilisateur($club)
               ->setRole(RoleEquipe::Gestionnaire)
               ->setStatut(StatutMembreEquipe::Confirme)
               ->setOrigine(OrigineMembreEquipe::InvitationClub);
            $this->save($ej);
        }
        $manager->flush();

        $equipe = function (string $cle) use ($equipes): Equipe {
            if (!isset($equipes[$cle])) {
                throw new \RuntimeException(sprintf('Équipe de démo inconnue : %s', $cle));
            }
            return $equipes[$cle];
        };

        // 4. Membres des équipes
        // Garde-fous : pas de doublon (joueur, équipe), max 1 confirmé par (joueur, sport).
        $dejaMembre       = [];  // [email][clé équipe] = true
        $confirmeParSport = [];  // [email][sport]      = true

        foreach (self::MEMBRES as [$cleEq, $email, $statut, $origine]) {
            if (isset($dejaMembre[$email][$cleEq])) {
                throw new \RuntimeException(sprintf('Doublon membre : %s / %s', $email, $cleEq));
            }
            $eq       = $equipe($cleEq);
            $sportNom = $eq->getSport()->getNom();

            if ($statut === 'confirme') {
                if (isset($confirmeParSport[$email][$sportNom])) {
                    throw new \RuntimeException(sprintf(
                        '%s est déjà confirmé dans une équipe de %s.', $email, $sportNom
                    ));
                }
                $confirmeParSport[$email][$sportNom] = true;
            }

            $ej = new EquipeJoueur();
            $ej->setEquipe($eq)
               ->setUtilisateur($user($email))
               ->setRole(RoleEquipe::Joueur)
               ->setStatut(StatutMembreEquipe::from($statut))
               ->setOrigine(OrigineMembreEquipe::from($origine));
            $this->save($ej);
            $dejaMembre[$email][$cleEq] = true;
        }
        $manager->flush();

        // 5. Matchs + camps (R5 : équipe XOR joueur ; R2 : 2 camps confirmés)
        /** @var array<string, Game> $games */
        $games = [];
        /** @var array<string, array<string, Utilisateur>> $occupants  clé match => role => occupant */
        $occupants = [];

        $poserCamp = function (
            Game $g,
            string $spec,
            RoleMatchCamp $role,
            StatutMatchCamp $statutCamp,
        ) use ($equipe, $user): Utilisateur {
            [$kind, $ref] = explode(':', $spec, 2);

            $camp = new MatchCamp();
            $camp->setRole($role)->setStatut($statutCamp);
            $g->addCamp($camp);   // côté propriétaire + collection en mémoire

            if ($kind === 'eq') {
                $eq = $equipe($ref);
                if ($eq->getSport() !== $g->getSport()) {
                    throw new \RuntimeException(sprintf('Équipe %s incompatible avec le sport du match.', $ref));
                }
                $camp->setEquipe($eq);          // R5 : joueur reste null
                $occupant = $eq->getClub();
            } else {
                $camp->setJoueur($user($ref));  // R5 : equipe reste null
                $occupant = $user($ref);
            }

            $this->save($camp);

            return $occupant;
        };

        foreach (self::GAMES as $cle => [
            $sportNom, $libNiveau, $statut, $jours, $heure, $ville, $lieu, $description,
            $camp1Spec, $camp2Spec, $statutCamp2,
        ]) {
            $sport = $sports[$sportNom];

            // Cohérence type de sport ↔ nature des camps (R5)
            $attendu = str_starts_with($camp1Spec, 'eq:') ? TypeSport::Collectif : TypeSport::Individuel;
            if ($sport->getType() !== $attendu) {
                throw new \RuntimeException(sprintf('Match %s : camps incohérents avec le type de sport.', $cle));
            }

            $g = new Game();
            $g->setSport($sport);
            $g->setNiveauRequis($niv($sportNom, $libNiveau));   // R4
            $g->setDateMatch($this->dateRelative($jours, $heure));
            $g->setLieu($lieu . ', ' . $ville);
            $g->setLatitude(self::VILLES[$ville][0]);
            $g->setLongitude(self::VILLES[$ville][1]);
            $g->setDescription($description);
            $g->setStatut(StatutGame::from($statut));

            // Créateur = occupant du camp 1 (club pour un collectif, joueur sinon)
            [$kind1, $ref1] = explode(':', $camp1Spec, 2);
            $g->setCreateur($kind1 === 'eq' ? $equipe($ref1)->getClub() : $user($ref1));
            $this->save($g);

            $occ1 = $poserCamp($g, $camp1Spec, RoleMatchCamp::Camp1, StatutMatchCamp::Confirme);
            $occupants[$cle] = [RoleMatchCamp::Camp1->value => $occ1];

            if ($camp2Spec !== null) {
                $occ2 = $poserCamp($g, $camp2Spec, RoleMatchCamp::Camp2, StatutMatchCamp::from($statutCamp2));
                $occupants[$cle][RoleMatchCamp::Camp2->value] = $occ2;
            }

            $games[$cle] = $g;
        }
        $manager->flush();

        $game = function (string $cle) use ($games): Game {
            if (!isset($games[$cle])) {
                throw new \RuntimeException(sprintf('Match de démo inconnu : %s', $cle));
            }
            return $games[$cle];
        };

        // 6. Demandes de match — UNIQUE (game, demandeur)
        $dejaDemande = [];  // [clé match][email] = true

        foreach (self::DEMANDES as [$cleGame, $email, $cleEq, $statut]) {
            if (isset($dejaDemande[$cleGame][$email])) {
                throw new \RuntimeException(sprintf('Doublon demande : %s / %s', $cleGame, $email));
            }
            $g          = $game($cleGame);
            $demandeur  = $user($email);
            $statutEnum = StatutDemande::from($statut);

            // Une demande en attente ne vit que sur un match encore ouvert et incomplet
            if ($statutEnum === StatutDemande::EnAttente
                && ($g->getStatut() !== StatutGame::EnAttente || $g->getCamps()->count() >= 2)) {
                throw new \RuntimeException(sprintf('Demande en attente impossible sur le match %s.', $cleGame));
            }

            // Cohérence collectif / individuel
            $estCollectif = $g->getSport()->getType() === TypeSport::Collectif;
            if ($estCollectif && $cleEq === null) {
                throw new \RuntimeException(sprintf('Demande collective sans équipe : %s / %s', $cleGame, $email));
            }
            if (!$estCollectif && $cleEq !== null) {
                throw new \RuntimeException(sprintf('Demande individuelle avec équipe : %s / %s', $cleGame, $email));
            }

            $eqDemande = null;
            if ($cleEq !== null) {
                $eqDemande = $equipe($cleEq);
                if ($eqDemande->getClub() !== $demandeur) {
                    throw new \RuntimeException(sprintf('L\'équipe %s n\'appartient pas à %s.', $cleEq, $email));
                }
                if ($eqDemande->getSport() !== $g->getSport()) {
                    throw new \RuntimeException(sprintf('L\'équipe %s ne pratique pas le sport du match %s.', $cleEq, $cleGame));
                }
            }

            $d = new DemandeMatch();
            $d->setGame($g);
            $d->setDemandeur($demandeur);
            $d->setEquipe($eqDemande);
            $d->setStatut($statutEnum);
            $d->setDateCreation($this->dateImmutableRelative(-mt_rand(3, 20)));
            $this->save($d);
            $dejaDemande[$cleGame][$email] = true;
        }
        $manager->flush();

        // 7. Résultats — R3 : match terminé, 2 camps confirmés, date passée, 1 seul par match
        $maintenant = new \DateTime();

        foreach (self::RESULTATS as [$cleGame, $s1, $s2]) {
            $g = $game($cleGame);

            if ($g->getStatut() !== StatutGame::Termine) {
                throw new \RuntimeException(sprintf('Résultat sur un match non terminé : %s', $cleGame));
            }
            if ($g->getCamps()->count() !== 2) {
                throw new \RuntimeException(sprintf('Résultat sur un match à %d camp(s) : %s', $g->getCamps()->count(), $cleGame));
            }
            if ($g->getDateMatch() >= $maintenant) {
                throw new \RuntimeException(sprintf('Résultat sur un match à venir : %s', $cleGame));
            }

            $r = new Resultat();
            $r->setGame($g);
            $r->setScoreCamp1($s1);   // R6 : camp_1 ↔ scoreCamp1
            $r->setScoreCamp2($s2);
            $this->save($r);
        }
        $manager->flush();

        // 8. Avis — UNIQUE (notant, game), uniquement sur des matchs terminés
        $dejaAvis = [];  // [clé match][email notant] = true

        foreach (self::AVIS as [$cleGame, $emailNotant, $emailEvalue, $ponctualite, $fairPlay, $niveauConforme]) {
            if (isset($dejaAvis[$cleGame][$emailNotant])) {
                throw new \RuntimeException(sprintf('Doublon avis : %s / %s', $cleGame, $emailNotant));
            }
            $g = $game($cleGame);

            if ($g->getStatut() !== StatutGame::Termine) {
                throw new \RuntimeException(sprintf('Avis sur un match non terminé : %s', $cleGame));
            }
            $occ = array_values($occupants[$cleGame] ?? []);
            $ids = array_map(static fn (Utilisateur $u) => spl_object_id($u), $occ);
            if (!\in_array(spl_object_id($user($emailNotant)), $ids, true)
                || !\in_array(spl_object_id($user($emailEvalue)), $ids, true)) {
                throw new \RuntimeException(sprintf('Avis entre non-participants sur %s.', $cleGame));
            }

            $a = new Avis();
            $a->setGame($g);
            $a->setNotant($user($emailNotant));
            $a->setEvalue($user($emailEvalue));
            $a->setPonctualite($ponctualite);
            $a->setFairPlay($fairPlay);
            $a->setNiveauConforme($niveauConforme);
            $a->setDateCreation(\DateTimeImmutable::createFromMutable($g->getDateMatch())->modify('+1 day'));
            $this->save($a);
            $dejaAvis[$cleGame][$emailNotant] = true;
        }
        $manager->flush();

        // 9. Messages
        foreach (self::MESSAGES as [$cleGame, $email, $contenu, $ilYaJours]) {
            $m = new Message();
            $m->setGame($game($cleGame));
            $m->setExpediteur($user($email));
            $m->setContenu($contenu);
            $m->setDateEnvoi($this->dateRelative(-$ilYaJours, '12:00'));
            $this->save($m);
        }

        $manager->flush();
    }

    // ═══════════════════════════════════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Garde-fou anti double chargement : en --append, relancer les fixtures
     * démo violerait la contrainte UNIQUE sur l'email.
     */
    private function verifierAbsenceDemo(): void
    {
        $repo = $this->em->getRepository(Utilisateur::class);

        foreach ([self::JOUEUR_EMAIL, self::CLUB_EMAIL] as $email) {
            if ($repo->findOneBy(['email' => $email]) !== null) {
                throw new \RuntimeException(sprintf(
                    'Les fixtures "demo" semblent déjà chargées (%s existe). '
                    . 'Supprimez les données de démo avant de relancer.',
                    $email,
                ));
            }
        }
    }

    /**
     * Charge Sport + Niveau de façon IDEMPOTENTE :
     *  - sport absent  → création du sport et de tous ses niveaux (catalogue) ;
     *  - sport présent → réutilisation de l'entité en base, niveaux indexés
     *    depuis la base, et création des seuls niveaux manquants.
     *
     * @return array{0: array<string, Sport>, 1: array<string, array<string, Niveau>>}
     */
    private function chargerReferentiel(): array
    {
        $repoSport = $this->em->getRepository(Sport::class);

        /** @var array<string, Sport> $sports */
        $sports = [];
        /** @var array<string, array<string, Niveau>> $niveaux  sport => libelle => Niveau */
        $niveaux = [];

        foreach (SportNiveaux::CATALOGUE as $nom => $cfg) {
            $sport = $repoSport->findOneBy(['nom' => $nom]);

            if ($sport === null) {
                $sport = new Sport();
                $sport->setNom($nom);
                $sport->setType(TypeSport::from($cfg['type']));
                $this->em->persist($sport);
            }

            $sports[$nom]  = $sport;
            $niveaux[$nom] = [];

            // Niveaux déjà rattachés au sport (base ou création en cours)
            foreach ($sport->getNiveaux() as $existant) {
                $niveaux[$nom][$existant->getLibelle()] = $existant;
            }

            // Compléter uniquement ce qui manque
            foreach ($cfg['niveaux'] as $ordre => $libelle) {
                if (isset($niveaux[$nom][$libelle])) {
                    continue;
                }
                $niveau = new Niveau();
                $niveau->setLibelle($libelle);
                $niveau->setOrdre($ordre + 1);
                $sport->addNiveau($niveau);
                $this->em->persist($niveau);
                $niveaux[$nom][$libelle] = $niveau;
            }
        }

        $this->em->flush();

        return [$sports, $niveaux];
    }

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
        $u->setPassword($this->hasher->hashPassword($u, self::PASSWORD));
        $u->setType($type);
        $u->setLocalisation($localisation);
        $u->setDateInscription($this->dateRelative(-mt_rand(60, 540), '09:00'));

        return $u;
    }

    /**
     * Un UtilisateurNiveau par sport déclaré — UNIQUE métier (utilisateur, sport).
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
        $deja = [];

        foreach ($sportsNoms as $sNom) {
            if (isset($deja[$sNom])) {
                throw new \RuntimeException(sprintf('Sport "%s" déclaré deux fois pour %s.', $sNom, $u->getEmail()));
            }
            if (!isset($sports[$sNom])) {
                throw new \RuntimeException(sprintf('Sport "%s" inconnu du catalogue.', $sNom));
            }
            $deja[$sNom] = true;

            // Niveau médian du sport : lisible en démo, cohérent avec le sport (R4)
            $nivs = array_values($niveaux[$sNom]);
            $un   = new UtilisateurNiveau();
            $un->setSport($sports[$sNom]);
            $un->setNiveau($nivs[intdiv(count($nivs), 2)]);
            $u->addNiveau($un);
        }
    }

    private function dateRelative(int $jours, string $heure): \DateTime
    {
        [$h, $m] = array_map('intval', explode(':', $heure));

        return (new \DateTime())
            ->modify(sprintf('%+d days', $jours))
            ->setTime($h, $m);
    }

    private function dateImmutableRelative(int $jours): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->modify(sprintf('%+d days', $jours));
    }

    private function avatarUrl(string $nom, string $couleur): string
    {
        return 'https://ui-avatars.com/api/?name=' . rawurlencode($nom)
            . '&size=200&background=' . $couleur
            . '&color=ffffff&bold=true&rounded=true';
    }
}
