# SportLink — Audit de cohérence (règles & choses impossibles)

> Checklist des règles métier gardées côté **back** (source de vérité) et reflétées côté **front** (UX).
> Principe : une règle non gardée côté serveur = une « chose impossible » qui devient possible via l'API.
> Légende **Vérifié** : `[x]` gardé et confirmé · `[!]` trou connu accepté (non corrigé volontairement) · `[ ]` reste à faire.
>
> Cas tranchés : résultat figé une fois saisi · pas de message sur match annulé · re-demande autorisée après refus · un match individuel ne se crée que par un joueur, un collectif que par un club · les deux participants légitimes peuvent saisir le résultat.
>
> **État global : audit bouclé sur A, B, C, E, F. Reste : tests fonctionnels (en cours).**

---

## A. Transitions d'état — Game (match)

Statuts : `en_attente` → `confirme` → `termine` / `annule`.

| # | Action | Règle | Où gardé | Vérifié |
|---|---|---|---|---|
| A1 | Rejoindre (créer camp 2) | statut `en_attente` + date future + sport déclaré | MatchService::creerCamp | [x] |
| A2 | Quitter le match | statut non terminé/annulé + date future | MatchService::supprimerCamp | [x] |
| A3 | Modifier (date/lieu/desc) | créateur + statut en_attente/confirmé + date future | GameVoter + MatchService | [x] |
| A4 | Supprimer manuellement | créateur + statut en_attente/confirmé | GameVoter + MatchController | [x] |
| A5 | Saisir un résultat | statut `termine` + 2 camps confirmés + pas de résultat (R3) | MatchService::saisirResultat | [x] |
| A6 | Modifier un résultat saisi | interdit (figé) — endpoint supprimé | — (endpoint retiré) | [x] |
| A7 | Envoyer un message | interdit si match annulé | MatchController::envoyerMessage | [x] |
| A8 | Auto `en_attente`→`annule` | date passée + pas 2 camps confirmés | Commande + clôture à la volée | [x] |
| A9 | Auto `confirme`→`termine` | date passée + 2 camps confirmés | Commande + clôture à la volée | [x] |

---

## B. Transitions d'état — EquipeJoueur (adhésion)

| # | Action | Règle | Où gardé | Vérifié |
|---|---|---|---|---|
| B1 | Accepter une invitation | statut en_attente + origine invitation_club + par le joueur invité | EquipeService::repondreAdhesion | [x] |
| B2 | Accepter une demande | statut en_attente + origine demande_joueur + par le club gestionnaire | EquipeService::repondreAdhesion | [x] |
| B3 | Refuser (invitation ou demande) | statut en_attente + par le destinataire légitime | EquipeService | [x] |
| B4 | Annuler (invitation ou demande) | statut en_attente + par l'émetteur | EquipeService::supprimerAdhesion | [!] |
| B5 | Quitter / retirer un membre | statut confirmé + rôle joueur (jamais gestionnaire) | supprimerAdhesion() + garde-fou | [x] |
| B6 | Re-demander après un refus | autorisé (réactivation du record refusé) | EquipeService::inviter/demander | [x] |

> **B4 `[!]`** : trou théorique accepté — le destinataire peut supprimer (DELETE) une invitation au lieu de la refuser (PATCH), sans laisser de trace. Impact nul (les refus ne servent à rien de spécial, le front n'expose pas ce DELETE). Choix assumé de ne pas corriger.

---

## C. Validations de données

| # | Règle | Où gardé | Vérifié |
|---|---|---|---|
| C1 | Score dans les bornes (indiv. 0–5, collectif 0–200, ≥ 0) | MatchService::validerScoresResultat | [x] |
| C2 | Niveau cohérent avec sport (R4) | Profil, Equipe, Match (modifier aligné) | [x] |
| C3 | Camp = équipe XOR joueur (R5) | MatchService::creerCamp | [x] |
| C4 | Club → sports collectifs uniquement | ProfilController + CreerEquipe + Register | [x] |
| C5 | Prénom requis si joueur | RegistrationController::inscrire | [x] |
| C6 | Un match a 2 camps max | MatchService::creerCamp | [x] |
| C7 | Inscription : au moins 1 sport déclaré | RegistrationController::inscrire | [x] |

---

## D. Filtrage / visibilité (étapes 3a + 3b)

| # | Règle | Où gardé | Vérifié |
|---|---|---|---|
| D1 | Matchs limités aux sports déclarés | GameRepository + MatchController | [x] |
| D2 | Création match : sport déclaré | MatchController::creer | [x] |
| D3 | Équipes limitées aux sports déclarés | EquipeController::lister | [x] |
| D4 | Invitation club→joueur : sport déclaré par le joueur | EquipeService::inviterJoueur | [x] |
| D5 | Demande joueur→club : sport déclaré | EquipeService::demanderAdhesion | [x] |
| D6 | Recherche de joueurs filtrée sur le sport de l'équipe | UtilisateurRepository::rechercherJoueurs | [x] |
| D7 | Recherche de matchs = uniquement les rejoignables | RechercheView (statut disponible forcé) | [x] |

> **D7 tranché** : la recherche ne montre que les matchs rejoignables (en_attente + date future + une place libre + sport déclaré). La consultation des autres matchs se fait via « Mes matchs » et l'accueil.

---

## E. Règles structurelles

| # | Règle | Où gardé | Vérifié |
|---|---|---|---|
| E1 | 1 équipe confirmée max par sport par joueur | EquipeService::verifierUneEquipeParSport | [x] |
| E2 | Club créateur = gestionnaire auto | EquipeService::creer | [x] |
| E3 | Résultat : 1 seul par match | contrainte OneToOne + check R3c | [x] |

---

## F. Permissions — Voters Symfony (§6 des specs)

Firewall JWT sur tout `/api/*` (sauf login/register/sports). Permissions fines via checks + **GameVoter** (formalisé). EquipeVoter : à formaliser (les checks manuels sont en place et fonctionnels en attendant).

| # | Action | Règle | Où gardé | Vérifié |
|---|---|---|---|---|
| F1 | Créer une équipe | club uniquement | EquipeController + Service | [x] |
| F2 | Gérer membres (inviter/retirer) | club, ses équipes | EquipeController + Service | [x] |
| F3 | Créer un match collectif | club uniquement | MatchController::creer (403) | [x] |
| F4 | Créer un match individuel | joueur uniquement | MatchController::creer (403) | [x] |
| F5 | Accepter/refuser invitation de match | club de l'équipe / joueur concerné | GameVoter (GAME_REPONDRE_CAMP) | [x] |
| F6 | Saisir un résultat | participant légitime (créateur, joueur d'un camp, club d'un camp) | GameVoter (GAME_SAISIR_RESULTAT) | [x] |
| F7 | Consulter / suivre | tout utilisateur connecté | Firewall JWT | [x] |

> **GameVoter** créé et branché (GAME_MODIFIER, GAME_SUPPRIMER, GAME_SAISIR_RESULTAT, GAME_REPONDRE_CAMP).
> **EquipeVoter** : à formaliser pour finir la centralisation (F1/F2). Les permissions équipe sont déjà gardées par des checks manuels corrects — la formalisation en Voter est de la mise au propre, pas un trou de sécurité.

---

## État d'avancement de l'audit

- **A à E** : bouclés, toutes les règles gardées côté back.
- **F** : GameVoter fait ; EquipeVoter reste à formaliser (checks manuels fonctionnels en attendant).
- **Tests fonctionnels** : setup en cours ; objectif = couvrir largement ces règles (« l'action interdite échoue »).

## Trous connus assumés
- **B4** : DELETE d'invitation par le destinataire (au lieu de PATCH refus) — impact nul, non corrigé volontairement.
- **EquipeVoter** non formalisé — permissions gardées par checks manuels, à mettre au propre.

## Reste hors audit (chantiers séparés)
- Nettoyage des migrations (ne se rejouent pas de zéro — utiliser schema:create).
- RGPD / mentions légales / sécurité technique (headers, CORS, secrets, HTTPS).
- Mise en production.