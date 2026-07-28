# CLAUDE.md — Projet SportLink

> Contexte complet du projet pour Claude Code. Lis-le en entier au démarrage.
> Lis AUSSI `docs/SportLink_Specs_Modele_v2.md` : c'est la spec de référence du modèle.

## 0. COMPORTEMENT ATTENDU (important)

- **Avant d'écrire du code, si une décision de conception est ambiguë ou si une info te manque, ARRÊTE-TOI et pose la question.** Ne suppose jamais une règle métier sans validation.
- **Présente ton plan étape par étape et attends le feu vert** avant d'implémenter.
- On avance **section par section** (voir ordre §9 des specs), pas tout d'un coup. Après chaque section : `doctrine:schema:validate` + vérif avant de continuer.

## 1. Présentation

**SportLink** : plateforme web de mise en relation d'équipes et de joueurs amateurs pour organiser des matchs amicaux. Projet fil rouge de la formation **CDA**, réalisé **individuellement** sur 6 mois (janvier → juin), jalons mensuels.

Ambition : **projet complet et soigné, rien n'est bâclé.** La priorisation sert l'ORDRE de réalisation (ne jamais arriver à un jalon avec une fonctionnalité cassée), pas à réduire le périmètre. Toutes les fonctionnalités du CDCF sont visées (y compris réputation, recommandations, géolocalisation).

Documents dans `docs/` : CDCF, méthodo (Jalon 2), MERISE initial (Jalon 3), **SportLink_Specs_Modele_v2.md (LA référence du modèle actuel)**, guides setup/entités.

## 2. Stack technique

- Back : Symfony 7.4 LTS, API REST, PHP 8.3
- Front : Vue 3 + Vite + Pinia + Vue Router (`front/`)
- BDD : MySQL 8
- Docker (tout dockerisé), Auth JWT (LexikJWTAuthenticationBundle), Git/GitHub

## 3. Structure

\`\`\`
SportLink/
├── docker-compose.yml      # racine, services: php(8000) db(3306) node(5173) phpmyadmin(8080)
├── CLAUDE.md
├── docs/
├── back/ (Symfony : src/Entity, src/Repository, src/Controller, src/Service, config, migrations)
└── front/ (Vue : src/)
\`\`\`

## 4. Règles d'environnement

- Commandes Symfony/Composer via Docker : \`docker compose exec php php bin/console <cmd>\`
- \`docker compose\` toujours depuis la racine (où est le yml), jamais depuis \`back/\`
- OS dev : Windows/PowerShell. Pas de \`\\\` en fin de ligne (bash). curl → \`curl.exe\` ou fichier \`.json\`.
- **Front en dev** : ouvrir \`http://localhost:5173\` (Vite). Si le hot-reload Docker ne réagit pas, lancer Vite sur l’hôte : \`cd front && npm install && npm run dev\` (API Docker sur :8000 reste OK). Après changement \`docker-compose.yml\` / \`vite.config.ts\` : \`docker compose up -d node\`.
- **Perf API (Windows)** : ne jamais activer \`opcache.validate_timestamps=1\` sur le volume \`./back\` (≈10 s/requête). \`vendor\` est dans le volume Docker \`back_vendor\`. Après \`docker compose up\` : \`docker compose exec php composer install\`. Code PHP modifié → \`docker compose restart php\`. Idéal long terme : cloner le projet dans le filesystem WSL2 (\`~/...\`), pas sous \`C:\\Users\`.
- DATABASE_URL : \`mysql://sportlink:sportlink@db:3306/sportlink?serverVersion=8.0&charset=utf8mb4\` (host = \`db\` en interne Docker)
- phpMyAdmin / clients Windows : host \`localhost\`, port 3306, \`sportlink\`/\`sportlink\`
- **Reset base dev (base vierge OU ancienne)** — une seule procédure, toujours la même (la migration consolidée est un CREATE FROM SCRATCH, pas de chemin incremental) :
  ```bash
  docker compose exec php php bin/console doctrine:database:drop --force
  docker compose exec php php bin/console doctrine:database:create
  docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
  docker compose exec php php bin/console doctrine:fixtures:load --group=dev --no-interaction
  ```
- **Reset base test** : utilise `doctrine:schema:create --env=test` (pas `migrations:migrate`) — base vierge uniquement, schéma généré depuis les entités :
  ```bash
  docker compose exec php php bin/console doctrine:database:drop --force --env=test
  docker compose exec php php bin/console doctrine:database:create --env=test
  docker compose exec php php bin/console doctrine:schema:create --env=test
  docker compose exec php php bin/console doctrine:fixtures:load --env=test --group=test --no-interaction
  ```

## 5. Conventions de code

- **Entité Match → \`Game\`, table \`game\`** (\`match\` = mot réservé MySQL). \`#[ORM\\Table(name: 'game')]\` obligatoire. Propriétés pointant vers un match = \`game\`, pas \`match\`.
- **Identifiant de connexion = \`email\`** (provider sécurité \`property: email\`).
- Tables de liaison = 2 ManyToOne + champs de données (jamais ManyToMany).
- Chaque \`inversedBy\` doit avoir son \`mappedBy\` correspondant. Vérifier \`doctrine:schema:validate\` avant toute migration.
- **Contrôleurs fins** : logique métier dans \`src/Service/\`.
- Dates : préférer \`DateTimeImmutable\`.

## 6. MODÈLE DE DONNÉES (refonte v2 — voir specs pour le détail complet)

> ⚠️ L'ancien modèle (Disputer, Participation, niveau/sport en texte) est REMPLACÉ. Ne pas le réutiliser.

**Principe :** c'est le SPORT qui définit la nature du match. Sport \`collectif\` → match entre 2 équipes. Sport \`individuel\` → match entre 2 joueurs (1v1, double 2v2 prévu plus tard). Un match a toujours 2 CAMPS (équipe OU joueur selon le sport).

**2 types d'utilisateur :** \`club\` (crée/gère équipes, matchs collectifs) et \`joueur\` (matchs individuels, membre d'équipe, suit son club).

Entités :
- **Sport** : nom (unique), type (\`collectif\`|\`individuel\`) ; OneToMany Niveau
- **Niveau** : libelle, **ordre** (int, hiérarchie), sport (ManyToOne)
- **Utilisateur** : email, password, roles, nom, prenom, type (\`club\`|\`joueur\`), localisation, dateInscription. PAS de champ niveau direct.
- **UtilisateurNiveau** : utilisateur, sport, niveau (un niveau par sport pratiqué)
- **Equipe** : nom, sport (collectif), niveau, localisation, logo, club (créateur) ; OneToMany EquipeJoueur
- **EquipeJoueur** : utilisateur, equipe, role (\`gestionnaire\`|\`joueur\`), origine, statut (\`en_attente\`|\`confirme\`|\`refuse\`)
- **Game** (table \`game\`) : sport, niveauRequis (nullable), dateMatch, lieu, statut (\`en_attente\`|\`confirme\`|\`termine\`|\`annule\`), createur ; OneToMany MatchCamp
- **MatchCamp** (remplace Disputer + Participation) : game, role (\`camp_1\`|\`camp_2\`), statut (\`invite\`|\`confirme\`|\`refuse\`), equipe (nullable, si collectif), joueur (nullable, si individuel). Règle : equipe XOR joueur.
- **Resultat** : game (OneToOne, FK unique), scoreCamp1, scoreCamp2
- **Message** : contenu, dateEnvoi, game, expediteur

## 7. RÈGLES MÉTIER (dans les Services — détail dans specs §5)

- **R1** Création match : sport collectif → club engage une équipe (camp_1) + invite équipe adverse (camp_2). Individuel → joueur = camp_1 + invite joueur adverse. Statut initial \`en_attente\`.
- **R2** Match \`confirme\` uniquement quand les 2 camps sont \`confirme\`.
- **R3** Résultat saisissable SEULEMENT si : 2 camps confirmés + dateMatch passée + pas déjà de résultat. À la saisie → statut \`termine\`.
- **R4** Niveau cohérent avec le sport (un Niveau appartient à un Sport).
- **R5** Camp cohérent : collectif → equipe obligatoire/joueur null ; individuel → joueur obligatoire/equipe null.
- **R6** UI résultat : afficher nom du camp ↔ son score (camp_1↔scoreCamp1).

## 8. PERMISSIONS (Voters Symfony — détail specs §6)

Club : créer/gérer équipes, créer matchs collectifs, saisir résultats de ses équipes.
Joueur : créer matchs individuels, rejoindre équipe, accepter/refuser invitations, saisir résultats de ses matchs.

## 9. ÉTAT & PROCHAINES ÉTAPES

**Fait :** env Docker complet, skeleton Symfony + bundles, front Vue initialisé, auth JWT configurée, anciennes entités (à refondre).

**À faire (ordre — specs §9) :**
1. Sport + Niveau (avec ordre)
2. Refonte Utilisateur (type club/joueur) + UtilisateurNiveau
3. Equipe + EquipeJoueur (statut invitation)
4. Game + MatchCamp (supprimer Disputer + Participation)
5. Resultat + Message
6. Migration propre unique
7. Fixtures depuis le catalogue PHP (\`src/Reference/SportNiveaux.php\`) → alimente Sport + Niveau
8. Endpoints API + Services (R1–R6)
9. Voters (permissions)
10. Front Vue : sélecteur sport→niveaux dynamiques, parcours équipe, parcours match, saisie résultat lisible

> Le catalogue \`SportNiveaux::CATALOGUE\` (sports + niveaux ordonnés) devient la SOURCE des fixtures, pas une référence runtime.

## 10. Git

\`main\` (jalons) ← \`test\` ← \`develop\` ← \`feature/*\`. Commits : \`feat:\`, \`fix:\`, \`refactor:\`, \`docs:\`.

## 11. Contraintes

Dev individuel en parallèle d'une activité pro. Sécurité OWASP, RGPD, mots de passe hashés. Normes PSR, code maintenable, doc des choix techniques.
