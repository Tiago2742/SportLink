# SportLink — Audit de cohérence (règles & choses impossibles)

> Checklist exhaustive des règles métier à faire respecter côté **back** (source de vérité) et à refléter côté **front** (UX : boutons masqués/désactivés).
> Principe : une règle non gardée côté serveur = une « chose impossible » qui devient possible via l'API.
> Colonne **Vérifié** : `[ ]` à auditer, `[x]` confirmé gardé, `[!]` trou identifié à corriger.
>
> Cas tranchés : résultat figé une fois saisi · pas de message sur match annulé · re-demande autorisée après refus.

---

## A. Transitions d'état — Game (match)

Statuts : `en_attente` → `confirme` → `termine` / `annule`.

| # | Action | Autorisé si | Interdit si | Où garder | Vérifié |
|---|---|---|---|---|---|
| A1 | Rejoindre (créer camp 2) | statut `en_attente` ET date future ET sport déclaré | `confirme`/`termine`/`annule`, ou date passée | Service participation | [ ] |
| A2 | Quitter le match | statut `en_attente` ou `confirme` ET date future | `termine`/`annule`, ou date passée | Service participation | [ ] |
| A3 | Modifier le match (date/lieu/desc) | créateur ET statut `en_attente`/`confirme` ET date future | non-créateur, `termine`/`annule` | Voter + Service | [ ] |
| A4 | Annuler/supprimer manuellement | créateur ET `en_attente`/`confirme` | non-créateur, déjà `termine`/`annule` | Voter + Service | [ ] |
| A5 | Saisir un résultat | statut `termine` ET 2 camps confirmés ET pas de résultat (R3) | tout autre statut, 1 camp, résultat existant | MatchService::saisirResultat | [ ] |
| A6 | Modifier un résultat saisi | **jamais (figé)** | toujours une fois saisi | MatchService | [ ] |
| A7 | Envoyer un message | statut ≠ `annule` | match `annule` | MessageService/Controller | [ ] |
| A8 | Passage auto `en_attente`→`annule` | date passée ET pas 2 camps confirmés | — | Commande cloturer-matchs-expires | [x] |
| A9 | Passage auto `confirme`→`termine` | date passée ET 2 camps confirmés | — | Commande cloturer-matchs-expires | [x] |

---

## B. Transitions d'état — EquipeJoueur (adhésion)

Statuts : `en_attente` → `confirme` / `refuse`. Origine : `invitation_club` | `demande_joueur`. Rôle : `gestionnaire` | `joueur`.

| # | Action | Autorisé si | Interdit si | Où garder | Vérifié |
|---|---|---|---|---|---|
| B1 | Accepter une invitation | statut `en_attente`, origine `invitation_club`, par le joueur invité | déjà `confirme`/`refuse`, autre utilisateur | Service adhésion + Voter | [ ] |
| B2 | Accepter une demande | statut `en_attente`, origine `demande_joueur`, par le club gestionnaire | déjà traité, autre club | Service adhésion + Voter | [ ] |
| B3 | Refuser (invitation ou demande) | statut `en_attente`, par le destinataire légitime | déjà traité | Service adhésion + Voter | [ ] |
| B4 | Annuler (invitation ou demande) | statut `en_attente`, par l'émetteur | déjà traité | Service adhésion + Voter | [ ] |
| B5 | Quitter / retirer un membre | statut `confirme` ET rôle `joueur` | rôle `gestionnaire`, statut ≠ confirmé | supprimerAdhesion() + garde-fou | [x] |
| B6 | Re-demander après un refus | **autorisé** (nouvelle demande) | — | Service adhésion | [ ] |

---

## C. Validations de données (à la création / modification)

| # | Règle | Détail | Où garder | Vérifié |
|---|---|---|---|---|
| C1 | Score dans les bornes | individuel 0–5, collectif 0–200, ≥ 0 | MatchService (saisie résultat) | [x] |
| C2 | Niveau cohérent avec sport (R4) | le niveau doit appartenir au sport (Game/Equipe/UtilisateurNiveau) | Services concernés + contrainte | [ ] |
| C3 | Camp = équipe XOR joueur (R5) | collectif → equipe obligatoire/joueur null ; individuel → l'inverse | Service participation | [ ] |
| C4 | Club → sports collectifs uniquement | un club ne déclare/crée que du collectif | ProfilController + CreerEquipe | [x] |
| C5 | Prénom conditionnel | requis si `joueur`, optionnel si `club` | Register (validation) | [x] |
| C6 | Un match a 2 camps possibles max | pas de 3e camp | Service participation | [ ] |
| C7 | Inscription : au moins 1 sport déclaré | interdit de créer un compte sans sport (sinon site vide) | Register (validation) | [!] |

---

## D. Filtrage / visibilité (étapes 3a + 3b)

| # | Règle | Détail | Où garder | Vérifié |
|---|---|---|---|---|
| D1 | Matchs limités aux sports déclarés | création + recherche + disponibles | GameRepository + MatchController | [x] |
| D2 | Création match : sport déclaré | 422 si sport non déclaré | MatchController::creer | [x] |
| D3 | Équipes limitées aux sports déclarés | « trouver une équipe » | EquipeController::lister | [x] |
| D4 | Invitation club→joueur : sport déclaré par le joueur | recherche de joueurs + validation | EquipeService::inviterJoueur | [x] |
| D5 | Demande joueur→club : sport déclaré | validation | EquipeService::demanderAdhesion | [x] |
| D6 | Recherche de joueurs filtrée sur le sport de l'équipe | back | UtilisateurRepository::rechercherJoueurs | [x] |
| D7 | Recherche de matchs : ne montrer que les disponibles ? | *décision de conception en attente* | RechercheView + back | [ ] |

---

## E. Règles structurelles

| # | Règle | Détail | Où garder | Vérifié |
|---|---|---|---|---|
| E1 | 1 équipe confirmée max par sport par joueur | à l'adhésion (invitation + demande) | EquipeService::verifierUneEquipeParSport | [ ] |
| E2 | Club créateur = gestionnaire auto | à la création d'équipe | EquipeService/CreerEquipe | [x] |
| E3 | Résultat : 1 seul par match | contrainte OneToOne + check R3c | entité + service | [ ] |

---

## F. Permissions — Voters Symfony (§6 des specs) — NON IMPLÉMENTÉ

> Aujourd'hui les endpoints ne sont pas finement protégés par des Voters. Chantier à part.

| # | Action | Club | Joueur | Vérifié |
|---|---|---|---|---|
| F1 | Créer une équipe | ✅ | ❌ | [ ] |
| F2 | Gérer membres (inviter/retirer) | ✅ ses équipes | ❌ | [ ] |
| F3 | Créer un match collectif | ✅ | ❌ | [ ] |
| F4 | Créer un match individuel | ❌ | ✅ | [ ] |
| F5 | Accepter/refuser invitation de match | ✅ son équipe | ✅ lui-même | [ ] |
| F6 | Saisir un résultat | ✅ ses équipes | ✅ ses matchs | [ ] |
| F7 | Consulter / suivre | ✅ | ✅ | [ ] |

---

## Méthode d'audit

1. Faire vérifier par Claude Code, section par section : pour chaque ligne `[ ]`, confirmer que la garde existe **côté back** (pas juste front). Marquer `[x]` si gardé, `[!]` si trou.
2. Corriger les trous `[!]` par lots (un lot = une section), tester après chaque lot, commit.
3. Figer chaque règle par un **test fonctionnel** PHPUnit (« l'action interdite échoue »).
4. Les Voters (section F) = chantier dédié, à planifier séparément.

## Trous déjà connus (au moment de la rédaction)
- **C7** : création de compte sans sport possible → à interdire.
- **A7** : messages sur match annulé → à bloquer.
- **A1/A2** : participation modifiable sur match annulé/terminé → à figer (bug prioritaire signalé).
- **D7** : décision de conception (recherche = disponibles seulement ?) à trancher.
- **Section F entière** : Voters non implémentés.