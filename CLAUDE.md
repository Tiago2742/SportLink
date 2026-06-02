# CLAUDE.md — Projet SportLink

> Ce fichier donne le contexte complet du projet à Claude Code. Lis-le en entier au démarrage.

## 1. Présentation

**SportLink** est une plateforme web de mise en relation d'équipes et de joueurs amateurs pour organiser des matchs amicaux. C'est le projet fil rouge de la formation **CDA (Concepteur Développeur d'Applications)**, réalisé **individuellement** sur 6 mois (janvier → juin), avec des jalons mensuels.

Documents de conception de référence dans `docs/` : CDCF (cahier des charges fonctionnel), méthodologie projet (Jalon 2), modélisation BDD MERISE (Jalon 3). À consulter pour comprendre les besoins fonctionnels et le modèle de données d'origine.

## 2. Stack technique

- **Back-end** : Symfony 7.4 LTS, API REST, PHP 8.3
- **Front-end** : Vue 3 + Vite + Pinia + Vue Router (dans `front/`)
- **Base de données** : MySQL 8
- **Conteneurisation** : Docker (tout l'environnement est dockerisé)
- **Auth** : JWT via LexikJWTAuthenticationBundle
- **Versioning** : Git / GitHub

> Le CDCF impose Symfony + MySQL + Docker + Git + intégration d'une API externe (cartographie/géoloc/notifications). Le front Vue est un choix assumé du développeur.

## 3. Structure du projet

```
SportLink/
├── docker-compose.yml      # à la racine, orchestre tous les services
├── CLAUDE.md               # ce fichier
├── docs/                   # CDCF, méthodo, MERISE, guides
├── back/                   # API Symfony
│   ├── Dockerfile
│   ├── src/Entity/         # entités Doctrine
│   ├── src/Repository/
│   ├── src/Controller/     # contrôleurs API (à venir)
│   ├── config/
│   └── migrations/
└── front/                  # SPA Vue
    └── src/
```

Services Docker (`docker-compose.yml`) : `php` (Symfony, port 8000), `db` (MySQL, port 3306), `node` (Vue/Vite, port 5173), `phpmyadmin` (port 8080).

## 4. Règles d'environnement (IMPORTANT)

- **Toutes les commandes Symfony/Composer passent par Docker**, jamais en local. Format :
  ```
  docker compose exec php php bin/console <commande>
  ```
- **`docker compose` se lance toujours depuis la racine** du projet (là où est le `docker-compose.yml`), jamais depuis `back/`.
- L'OS de dev est **Windows / PowerShell**. Attention : pas de `\` en fin de ligne (syntaxe bash), tout sur une ligne. Pour curl, utiliser `curl.exe` et échapper les guillemets, ou pointer un fichier `.json` avec `-d "@fichier.json"`.
- Connexion BDD depuis Symfony (interne Docker) : host = `db`, port `3306`. `DATABASE_URL` :
  ```
  mysql://sportlink:sportlink@db:3306/sportlink?serverVersion=8.0&charset=utf8mb4
  ```
- Connexion BDD depuis Windows (phpMyAdmin/clients) : host = `localhost`, port `3306`. Identifiants : `sportlink` / `sportlink`.

## 5. Conventions de code (décisions déjà prises)

- **Entité Match → nommée `Game`, table `game`.** `match` est un mot réservé MySQL. L'entité a obligatoirement :
  ```php
  #[ORM\Entity(repositoryClass: GameRepository::class)]
  #[ORM\Table(name: 'game')]
  class Game { ... }
  ```
  Les propriétés qui pointent vers un match s'appellent `game` (pas `match`).
- **Identifiant de connexion = `email`** (pas `username`). Le provider de sécurité utilise `property: email`.
- **Tables de liaison** : faites en deux `ManyToOne` + champ de données (jamais en ManyToMany), car elles portent des attributs (`role`, `statut`).
- **`orphanRemoval: true`** sur les collections enfant qui n'ont pas de vie propre (membres d'équipe, participations, messages, résultat, disputer). **PAS** sur les collections d'un Utilisateur vers ses équipes/matchs créés (une équipe/un match a une vie propre).
- **Contrôleurs fins** : la logique métier va dans `src/Service/`, pas dans les contrôleurs (cf. méthodo : séparation MVC/services/repositories).
- Préférer `DateTimeImmutable` à `DateTime` pour les nouveaux champs date.

- **Sports & niveaux** : un utilisateur peut pratiquer plusieurs sports, chacun
  avec son propre niveau, via une entité de liaison `UtilisateurSport`
  (utilisateur ManyToOne, sport, niveau). Le champ `niveau` direct sur Utilisateur
  est SUPPRIMÉ au profit de cette table.
- **Référence sport→niveaux** : une seule source de vérité côté back (classe
  `SportLevels` / enum), exposée via `GET /api/sports`, consommée par le front
  pour peupler dynamiquement les listes (sport choisi → niveaux proposés).
  Ex : Football → D1, D2, R1, R2, R3, Amateur ; Tennis → 15/4, 15/5, 30/1, 30/2…

## 6. Modèle de données (entités + relations)

Entités : Utilisateur, Equipe, Game, EquipeJoueur, Participation, Disputer, Message, Resultat.

| Entité | Champs principaux | Relations |
|---|---|---|
| Utilisateur | email (unique), password (hashé), roles, nom, prenom, type, niveau, localisation, dateInscription | — |
| Equipe | nom, sport, niveau, localisation, logo | createur → Utilisateur (ManyToOne) ; membres (OneToMany EquipeJoueur) |
| Game | sport, dateMatch, lieu, niveauRequis, statut | createur → Utilisateur (ManyToOne) |
| EquipeJoueur | role | utilisateur → Utilisateur ; equipe → Equipe (2x ManyToOne) |
| Participation | statut (invité/confirmé/refusé) | game → Game ; utilisateur → Utilisateur |
| Disputer | role (equipe_1/equipe_2) | game → Game ; equipe → Equipe |
| Message | contenu, dateEnvoi | game → Game ; expediteur → Utilisateur |
| Resultat | scoreEquipe1, scoreEquipe2 | game → Game (**OneToOne**, FK unique) |
| UtilisateurSport | sport, niveau | utilisateur → Utilisateur (ManyToOne) |

Règle relationnelle : chaque `inversedBy` (côté propriétaire) doit avoir son `mappedBy` correspondant (côté inverse), sinon Doctrine crée des colonnes/tables en trop. Vérifier avec `doctrine:schema:validate` avant toute migration.

## 7. État d'avancement actuel

**Fait :**
- Environnement Docker complet fonctionnel (php, db, node, phpmyadmin)
- Skeleton Symfony 7.4 + bundles (orm, maker, security, lexik jwt, nelmio cors, validator, serializer)
- Front Vue 3 initialisé et accessible sur :5173
- Auth JWT configurée (clés générées, security.yaml, route /api/login_check)
- Entités créées : Utilisateur (avec email), Equipe, Game, EquipeJoueur (et en cours : Participation, Disputer, Message, Resultat)

**En cours / à vérifier :**
- Finir la création des entités restantes (Participation, Disputer, Message, Resultat)
- Vérifier les `mappedBy`/`inversedBy` appariés (ex: `membres` dans Equipe doit avoir `mappedBy: 'equipe'`)
- Lancer LA migration unique une fois toutes les entités créées :
  ```
  docker compose exec php php bin/console make:migration
  docker compose exec php php bin/console doctrine:migrations:migrate
  ```
- Valider : `docker compose exec php php bin/console doctrine:schema:validate` (doit donner 2x [OK])

## 8. Prochaines étapes (ordre conseillé)

1. Finaliser entités + migration + validation du schéma
2. **Route d'inscription** `POST /api/register` (crée un Utilisateur, hash le mot de passe) — exigence CDCF "création de compte"
3. Vérifier le login JWT de bout en bout (token retourné)
4. **CRUD API** dans l'ordre des dépendances : Equipe → Game → Participation
5. Logique métier dans `src/Service/`
6. Tests (PHPUnit back, Vitest front)
7. Front Vue : pages Accueil, Recherche, Créer un match, Mes matchs, Profil (maquettes Figma dans docs/)
8. Intégration API externe (cartographie/géoloc)
9. CI/CD GitHub Actions, finalisation Docker (sprint de juin)

## 9. Stratégie Git

- `main` : versions stables des jalons
- `test` : tests avant prod
- `develop` : intégration des fonctionnalités
- `feature/*` : une branche par fonctionnalité (ex: `feature/authentication`, `feature/team-management`)
- Commits conventionnels : `feat:`, `fix:`, `refactor:`, `docs:`

## 10. Contraintes projet

- Ambition : projet réalisé de façon complète et soignée, sans bâcler les
  fonctionnalités optionnelles. La priorisation sert l'ORDRE de réalisation
  (ne jamais arriver à un jalon avec une fonctionnalité cassée), pas à réduire
  le périmètre. Toutes les fonctionnalités du CDCF sont visées, y compris
  réputation, recommandations, géolocalisation.
- Sécurité : protection OWASP, conformité RGPD, mots de passe hashés.
- Qualité : normes PSR, code maintenable, documentation des choix techniques.
