# SportLink — Synthèse sécurité OWASP Top 10 (2021)

> Document de synthèse de la démarche sécurité appliquée à SportLink, structurée selon le référentiel OWASP Top 10 2021. Pour chaque catégorie : mesures mises en place, et le cas échéant, limites assumées / pistes d'amélioration.
>
> *Note : cette synthèse reflète une démarche de sécurité applicative rigoureuse dans le cadre d'un projet de formation. Elle ne constitue pas un audit de sécurité professionnel (qui nécessiterait des tests d'intrusion).*

---

## A01 — Broken Access Control (Contrôle d'accès défaillant)

**Statut : couvert (point fort)**

- Firewall JWT sur l'ensemble des routes `/api/*` (sauf login, register, sports publics).
- Système de permissions par **Voters Symfony** (GameVoter) : chaque action sur une ressource vérifie que l'utilisateur y a droit (modifier/supprimer un match, saisir un résultat, répondre à une invitation).
- Vérifications de permission systématiques dans les contrôleurs et services pour les équipes et adhésions (un utilisateur n'agit que sur ses propres ressources).
- Séparation stricte des rôles club / joueur (un joueur ne crée pas d'équipe, un club ne crée pas de match individuel, etc.).
- Règles métier gardées **côté serveur** (voir A04) : les « actions impossibles » le sont réellement au niveau API, pas seulement masquées côté interface.

*Amélioration possible : formaliser les permissions équipe dans un EquipeVoter dédié (actuellement via checks manuels corrects), pour une centralisation complète cohérente avec GameVoter.*

---

## A02 — Cryptographic Failures (Défaillances cryptographiques)

**Statut : couvert**

- Mots de passe hachés (algorithme robuste géré par Symfony), jamais stockés en clair.
- Authentification par jeton JWT signé (LexikJWTAuthenticationBundle).
- Clés privées JWT jamais versionnées (hors dépôt Git).
- Secrets applicatifs (APP_SECRET, passphrase JWT) sortis du code et du dépôt, gérés par variables d'environnement.
- HTTPS prévu en production (avec HSTS, voir A05).

---

## A03 — Injection

**Statut : couvert (vérifié)**

- **SQL** : toutes les requêtes passent par le QueryBuilder Doctrine avec paramètres liés (`setParameter`). Aucune concaténation de chaînes, aucune requête native construite à partir d'entrées utilisateur.
- **XSS** : le front (Vue) échappe automatiquement les interpolations `{{ }}`. Aucun usage de `v-html` avec du contenu utilisateur.
- Validation des entrées côté serveur sur les données sensibles (voir aussi A04).

---

## A04 — Insecure Design (Conception non sécurisée)

**Statut : couvert (point fort)**

- Un **audit de cohérence complet** a été mené sur toutes les règles métier (transitions d'état des matchs et adhésions, validations de données, filtrage, règles structurelles). Chaque règle est gardée **côté serveur**, garantissant que les contournements via appel direct à l'API sont bloqués.
- Décisions de conception documentées et tranchées (résultat figé après saisie, anonymisation plutôt que suppression pure pour préserver l'intégrité référentielle, etc.).
- Suite de **tests fonctionnels** vérifiant que les actions interdites échouent effectivement (permissions, transitions, validations).

---

## A05 — Security Misconfiguration (Mauvaise configuration)

**Statut : couvert**

- Configuration de production préparée : `APP_DEBUG=0`, `APP_ENV=prod` (pas de fuite de stack traces).
- **Headers de sécurité HTTP** ajoutés via un event listener : X-Content-Type-Options (nosniff), X-Frame-Options (DENY), Content-Security-Policy (frame-ancestors none), Referrer-Policy, et HSTS (production uniquement).
- **CORS** configuré strictement : origines autorisées par variable d'environnement (pas de wildcard), méthodes et en-têtes limités.
- Séparation des configurations dev / test / prod.
- docker-compose de production distinct (code figé dans l'image, pas de volume monté, sans outils de dev).

---

## A06 — Vulnerable and Outdated Components (Composants vulnérables)

**Statut : couvert (vérifié)**

- `composer audit` (dépendances PHP) : aucune vulnérabilité.
- `npm audit` (dépendances front) : les vulnérabilités identifiées concernent uniquement des outils de build/développement (Vite, PostCSS…), non inclus dans le bundle de production — impact limité. Correctifs disponibles.

*Amélioration possible : automatiser `composer audit` / `npm audit` dans la pipeline CI pour un suivi continu.*

---

## A07 — Identification and Authentication Failures

**Statut : couvert**

- **Rate limiting** sur l'authentification (`login_throttling` Symfony) : limitation des tentatives par IP+email (5/min) et par IP globale (20/min), politique sliding window, réponse 429 au-delà. Seuls les échecs sont comptés.
- **Politique de mot de passe** à l'inscription : minimum 8 caractères, au moins une lettre et un chiffre, validée côté serveur.
- JWT avec expiration ; déconnexion propre et redirection en cas de session expirée.

---

## A08 — Software and Data Integrity Failures

**Statut : peu concerné**

- Catégorie principalement liée aux pipelines CI/CD et aux dépendances non vérifiées. Surface d'exposition limitée pour ce projet.
- Les dépendances proviennent de sources officielles (Composer, npm) avec fichiers de lock versionnés.

---

## A09 — Security Logging and Monitoring Failures

**Statut : couvert (version applicative)**

- Canal Monolog dédié « security » écrivant dans un fichier séparé (`var/log/security.log`), avec rotation (rétention 90 jours).
- Événements de sécurité tracés : connexions échouées, déclenchement du rate limiting, accès refusés (403), anonymisation de compte.
- **Respect du RGPD dans les logs** : aucune donnée personnelle en clair — l'email est haché (SHA256) pour permettre la corrélation sans exposer de donnée personnelle ; mot de passe, nom, token ne sont jamais journalisés.

*Amélioration possible : en production, brancher un agrégateur de logs / supervision temps réel (Sentry, ELK) et des alertes automatiques.*

---

## A10 — Server-Side Request Forgery (SSRF)

**Statut : à surveiller lors de l'intégration de l'API externe**

- Peu concerné dans l'état actuel.
- **Vigilance** : lors de l'intégration de l'API externe (géolocalisation), toute entrée utilisateur servant à construire une requête serveur devra être validée pour éviter le SSRF (validation des entrées, pas d'URL arbitraire fournie par l'utilisateur).

---

## Synthèse

| Catégorie | Statut |
|---|---|
| A01 Contrôle d'accès | Couvert (point fort) |
| A02 Cryptographie | Couvert |
| A03 Injection | Couvert (vérifié) |
| A04 Conception | Couvert (point fort) |
| A05 Configuration | Couvert |
| A06 Dépendances | Couvert (vérifié) |
| A07 Authentification | Couvert |
| A08 Intégrité | Peu concerné |
| A09 Journalisation | Couvert (applicatif) |
| A10 SSRF | À surveiller (API externe) |

**Points forts à valoriser** : contrôle d'accès (Voters + règles serveur), conception sécurisée (audit de cohérence + tests), et journalisation respectueuse du RGPD (email haché).

**Limites assumées** (cohérentes avec le périmètre d'un projet de formation) : supervision temps réel non implémentée (A09), automatisation des audits de dépendances en CI à mettre en place (A06), EquipeVoter à formaliser (A01), vigilance SSRF à l'intégration de l'API externe (A10).
