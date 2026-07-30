# SportLink — Conventions à respecter pour tout nouvel ajout

> À lire / rappeler à chaque nouvelle fonctionnalité (à intégrer dans CLAUDE.md pour que l'assistant de code l'ait en contexte). Ces conventions découlent des audits de cohérence et de sécurité déjà réalisés : les respecter d'emblée évite de réintroduire des trous déjà corrigés et de tout re-vérifier à chaque fois.

## 1. Règles métier — toujours gardées côté serveur

- Toute règle métier (« telle action est interdite dans tel état ») doit être **appliquée côté back** (service ou contrôleur), pas seulement masquée côté front.
- Le front reflète la règle pour l'UX (bouton masqué/désactivé), mais **ne constitue jamais la sécurité** : un appel direct à l'API doit être bloqué côté serveur.
- Distinguer les codes : **403** pour un refus de permission (« tu n'as pas le droit »), **422** pour une règle métier violée (« l'action n'est pas possible dans cet état »), **400** pour une entrée invalide.

## 2. Permissions

- Tout endpoint qui agit sur une ressource appartenant à un utilisateur doit vérifier la permission : via un **Voter** (préféré, cf. GameVoter) ou un check explicite.
- Un utilisateur ne doit pouvoir agir que sur **ses propres** ressources.
- Respecter la séparation club / joueur (un club ne fait pas d'action réservée au joueur et inversement).
- Le Voter gère « qui a le droit » ; la règle d'état (statut, date…) reste dans le service. Ne pas mélanger les deux.

## 3. Exposition de données (RGPD / minimisation)

- **L'email d'un utilisateur n'est jamais exposé à un tiers.** Pour sérialiser un utilisateur vu par d'autres, utiliser le groupe `utilisateur:public` (id, nom, prénom, type, localisation, logo — sans email). L'email n'apparaît que sur le profil de l'utilisateur courant (`utilisateur:read`).
- Ne jamais sérialiser `password` ni `roles`.
- N'exposer que les données nécessaires à l'affichage (principe de minimisation).

## 4. Validation des entrées

- Toute donnée entrante est **validée côté serveur** (présence des champs requis, format, bornes, cohérence).
- Ne jamais faire confiance à la validation front seule.
- Pour les requêtes en base : toujours passer par le QueryBuilder Doctrine avec paramètres liés (`setParameter`). Jamais de concaténation de chaînes avec une entrée utilisateur.

## 5. Tests

- Tout comportement critique (permission, règle d'état, validation) doit être **figé par un test fonctionnel** (« l'action interdite renvoie l'erreur attendue » + « l'action autorisée réussit »).
- Après chaque ajout, lancer la suite complète : elle doit rester 100 % verte.
- Ne pas casser les fixtures/tests existants : les nouvelles validations d'inscription (mot de passe, sports…) ne s'appliquent qu'au flux API, pas aux fixtures qui créent les entités directement via Doctrine.

## 6. Secrets & configuration

- Aucun secret en dur dans le code ni dans un fichier versionné. Tout secret passe par une variable d'environnement (`.env.local` en dev, variables serveur en prod).
- Toute nouvelle intégration (API externe…) stocke ses clés en variable d'environnement, jamais en clair.

## 7. Logs de sécurité

- Un nouvel événement de sécurité sensible (échec d'accès, action critique) peut être ajouté au canal `security` (Monolog).
- **Ne jamais journaliser de donnée personnelle en clair** : email haché, jamais de mot de passe, nom, ou token.

## 8. Sécurité spécifique aux API externes (SSRF — A10)

- Lors de l'appel à une API externe, ne jamais construire l'URL de requête à partir d'une entrée utilisateur non validée (risque SSRF).
- Valider et contraindre les entrées (ex : une adresse à géocoder est passée en paramètre à un service de confiance, pas une URL arbitraire fournie par l'utilisateur).
- Gérer les erreurs de l'API externe (indisponibilité, réponse inattendue) sans exposer de détails internes.

## Réflexes techniques récurrents (environnement)

- Après toute modification back / config : `docker compose exec php php bin/console cache:clear` (opcache `validate_timestamps=0` → sinon changements non pris en compte).
- Fixtures dev : `doctrine:fixtures:load --group=dev --no-interaction` (le `--group=dev` est obligatoire).
- Reset base : `doctrine:database:drop --force` puis `create` puis `migrations:migrate` (migration unique, CREATE FROM SCRATCH).
- Synchroniser les deux côtés des relations Doctrine bidirectionnelles (`addCamp`/`addMembre`) pour éviter les collections « stale ».
