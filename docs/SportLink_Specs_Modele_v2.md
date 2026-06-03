# SportLink — Spécifications du modèle (refonte v2)

> Document de référence pour la refonte du modèle de données et des règles métier.
> Décisions validées avec le développeur. Sert de base au code (back Symfony + front Vue).
> Remplace l'ancien modèle (Disputer + Participation + niveau en texte).

---

## 1. Principe fondateur

**C'est le SPORT qui détermine la nature d'un match**, pas le match lui-même :
- **Sport collectif** (foot, basket, volley, rugby, handball…) → match entre **2 équipes**
- **Sport individuel** (tennis, badminton, natation…) → match entre **2 joueurs** (1v1, double 2v2 prévu plus tard)

Un match oppose toujours **2 camps**. Un camp est soit une équipe (collectif), soit un ou des joueurs (individuel). Le résultat est toujours « score camp 1 / score camp 2 » : cohérent dans les deux cas.

---

## 2. Types d'utilisateur (2 seulement)

| Type | Peut faire |
|---|---|
| **Club** | Créer et gérer une ou des équipes ; inscrire ses équipes à des matchs de sport collectif ; inviter/accepter des matchs contre d'autres clubs |
| **Joueur** | Organiser et jouer ses propres matchs de sport individuel (inviter un adversaire) ; suivre les matchs de son/ses club(s) ; être membre d'une équipe |

Les permissions sont **strictes** (voir §6) et appliquées via des Voters Symfony.

---

## 3. Entités du modèle

### Sport (référence)
- id
- nom (Football, Tennis…) — unique
- type : `collectif` | `individuel`
- → OneToMany vers Niveau

### Niveau (référence, lié à un sport)
- id
- libelle ("D2 — Division 2", "30/1"…)
- ordre (int — hiérarchie, du plus faible au plus élevé, pour tri et comparaison)
- sport → ManyToOne vers Sport

### Utilisateur
- id, email (unique), password (hashé), roles
- nom, prenom
- type : `club` | `joueur`
- localisation
- dateInscription
- (le champ `niveau` direct est SUPPRIMÉ → voir UtilisateurNiveau)

### UtilisateurNiveau (liaison : un joueur a un niveau par sport)
- id
- utilisateur → ManyToOne Utilisateur
- sport → ManyToOne Sport
- niveau → ManyToOne Niveau
> Permet à un utilisateur de pratiquer plusieurs sports, chacun avec son niveau.

### Equipe
- id, nom
- sport → ManyToOne Sport (doit être un sport collectif)
- niveau → ManyToOne Niveau
- localisation, logo
- club (créateur) → ManyToOne Utilisateur (type club)
- → membres : OneToMany EquipeJoueur

### EquipeJoueur (liaison joueur ↔ équipe)
- id
- utilisateur → ManyToOne Utilisateur
- equipe → ManyToOne Equipe
- role : `gestionnaire` | `joueur`
- origine : `invitation_club` | `demande_joueur`
- statut : `en_attente` | `confirme` | `refuse`

### Game (le match) — table `game`
- id
- sport → ManyToOne Sport
- niveauRequis → ManyToOne Niveau (nullable)
- dateMatch (datetime)
- lieu
- statut : `en_attente` | `confirme` | `termine` | `annule`
- createur → ManyToOne Utilisateur
- → camps : OneToMany MatchCamp (toujours 2 à terme)

### MatchCamp (un camp d'un match — remplace Disputer ET Participation)
- id
- game → ManyToOne Game
- role : `camp_1` | `camp_2`
- statut : `invite` | `confirme` | `refuse`
- equipe → ManyToOne Equipe (nullable — rempli si sport collectif)
- joueur → ManyToOne Utilisateur (nullable — rempli si sport individuel)
> Règle d'intégrité : pour un camp, soit `equipe` est renseigné (collectif), soit `joueur` (individuel), jamais les deux. La structure encaisse le double (2v2) plus tard en autorisant plusieurs joueurs par camp via une table fille si besoin — pas codé maintenant.

### Resultat (un par match terminé)
- id
- game → OneToOne Game (FK unique)
- scoreCamp1 (int)
- scoreCamp2 (int)

### Message (communication liée à un match)
- id, contenu, dateEnvoi
- game → ManyToOne Game
- expediteur → ManyToOne Utilisateur

---

## 4. Schéma relationnel (vue d'ensemble)

```
Sport 1──n Niveau
Sport 1──n Equipe
Sport 1──n Game
Sport 1──n UtilisateurNiveau

Utilisateur 1──n UtilisateurNiveau
Utilisateur 1──n EquipeJoueur
Utilisateur 1──n Equipe   (en tant que club créateur)
Utilisateur 1──n Game     (créateur)
Utilisateur 1──n MatchCamp (joueur, si individuel)
Utilisateur 1──n Message

Niveau 1──n UtilisateurNiveau / Equipe / Game

Equipe 1──n EquipeJoueur
Equipe 1──n MatchCamp (si collectif)

Game 1──n MatchCamp (2 camps)
Game 1──1 Resultat
Game 1──n Message
```

---

## 5. Règles métier (à appliquer dans les Services)

### R1 — Création d'un match
- Le créateur choisit un sport. Le `type` du sport détermine la nature :
  - collectif → le créateur (un club) engage une de ses équipes comme camp_1, et invite une équipe adverse (camp_2, statut `invite`)
  - individuel → le créateur (un joueur) est camp_1, et invite un joueur adverse (camp_2, statut `invite`)
- Statut initial du match : `en_attente`

### R2 — Confirmation d'un match
- Un match passe en `confirme` uniquement quand **les 2 camps ont le statut `confirme`** (l'adversaire invité a accepté).

### R3 — Saisie d'un résultat (conditions cumulatives)
Un résultat ne peut être saisi QUE si :
1. les 2 camps sont `confirme` (le match a réellement 2 participants)
2. `dateMatch` est passée (< maintenant)
3. le match n'a pas déjà un résultat
- À la saisie, le statut du match passe à `termine`.
- → Interdit de saisir un résultat sur un match que personne n'a rejoint, ou pas encore joué.

### R4 — Cohérence niveau/sport
- Un Niveau appartient à un Sport. On ne peut pas affecter à un Game/Equipe/UtilisateurNiveau un Niveau qui n'appartient pas au sport concerné. (Validation applicative + idéalement contrainte.)

### R5 — Cohérence camp/sport
- Sport collectif → MatchCamp.equipe obligatoire, MatchCamp.joueur null
- Sport individuel → MatchCamp.joueur obligatoire, MatchCamp.equipe null

### R6 — Lisibilité du résultat (UI)
- Le résultat affiche explicitement le nom de chaque camp en face de son score :
  « Les Aigles 3 – 1 FC Dynamite » ou « J. Dupont 6/4 6/2 M. Martin ».
  scoreCamp1 ↔ camp ayant role=camp_1, scoreCamp2 ↔ camp_2.

---

## 6. Permissions (Voters Symfony)

| Action | Club | Joueur |
|---|---|---|
| Créer une équipe | ✅ | ❌ |
| Gérer membres d'une équipe (inviter/retirer) | ✅ (ses équipes) | ❌ |
| Créer un match collectif | ✅ | ❌ |
| Créer un match individuel | ❌ | ✅ |
| Rejoindre une équipe (accepter invitation) | — | ✅ |
| Accepter/refuser une invitation de match | ✅ (son équipe) | ✅ (lui-même) |
| Saisir un résultat | ✅ (matchs de ses équipes) | ✅ (ses matchs) |
| Consulter / suivre | ✅ | ✅ |

---

## 7. Endpoints API principaux (à construire)

- `POST /api/register` — inscription (choix du type club/joueur)
- `POST /api/login_check` — login JWT (existant)
- `GET /api/sports` — liste des sports + leur type
- `GET /api/sports/{id}/niveaux` — niveaux d'un sport (triés par `ordre`)
- CRUD `Equipe`, gestion membres
- CRUD `Game` + invitation/acceptation des camps
- `POST /api/games/{id}/resultat` — saisie résultat (avec règles R3)
- CRUD `Message` par match

---

## 8. Fixtures (données de test)

- Créer les **Sport** (avec type) et leurs **Niveau** (avec ordre) à partir du catalogue PHP existant (`SportNiveaux::CATALOGUE`). Ce catalogue devient la source d'alimentation des fixtures.
- Quelques **clubs** + **joueurs**.
- Des **équipes** (clubs, sports collectifs) avec membres.
- Des **matchs** collectifs (2 équipes) et individuels (2 joueurs), à divers statuts.
- Des **résultats** sur les matchs terminés uniquement.
- Des **messages**.

---

## 9. Ordre de réalisation conseillé

1. Entités de référence : **Sport**, **Niveau** (+ ordre)
2. Refonte **Utilisateur** (type club/joueur) + **UtilisateurNiveau**
3. **Equipe** + **EquipeJoueur** (avec statut invitation)
4. **Game** + **MatchCamp** (remplace Disputer/Participation)
5. **Resultat**, **Message**
6. Migration unique propre (repartir d'un schéma neuf)
7. Fixtures depuis le catalogue
8. Endpoints API + Services (règles métier R1–R6)
9. Voters (permissions §6)
10. Front Vue : sélecteur sport → niveaux dynamiques, parcours équipe, parcours match, saisie résultat lisible

---

## 10. Notes de migration depuis l'ancien modèle

- Supprimer les entités **Disputer** et **Participation** (remplacées par MatchCamp).
- Supprimer le champ `niveau` (texte) de Utilisateur, Equipe, Game → remplacé par relations vers Niveau.
- Le champ `sport` (texte) partout → remplacé par relation vers Sport.
- Comme il n'y a pas encore de données de production, on régénère un schéma propre (drop + migrate + fixtures).
