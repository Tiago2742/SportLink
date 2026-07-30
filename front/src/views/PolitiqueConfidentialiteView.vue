<template>
  <div class="page-legale">
    <div class="conteneur page-corps">

      <div class="page-entete">
        <RouterLink to="/" class="retour-lien">← Retour</RouterLink>
        <span class="page-eyebrow">Légal</span>
        <h1 class="page-titre">Politique de confidentialité</h1>
        <p class="page-meta">Dernière mise à jour : 30/07/2026</p>
      </div>

      <div class="prose">

        <div class="note-info">
          <strong>Note</strong> : SportLink est une application développée dans le cadre d'un projet
          de formation (Concepteur Développeur d'Applications). Le présent document décrit le
          traitement des données personnelles au sein de l'application.
        </div>

        <h2>1. Responsable du traitement</h2>
        <p>Le responsable du traitement des données est :</p>
        <ul>
          <li><strong>Éditeur</strong> : DUVAL Tiago</li>
          <li><strong>Contact</strong> : <a href="mailto:tiago.duval5@gmail.com">tiago.duval5@gmail.com</a></li>
        </ul>

        <h2>2. Données collectées</h2>
        <p>
          SportLink collecte et traite les données suivantes, fournies directement par l'utilisateur
          lors de son inscription et de l'utilisation du service :
        </p>

        <h3>Données de compte</h3>
        <ul>
          <li>Adresse e-mail (identifiant de connexion)</li>
          <li>Mot de passe (stocké de manière chiffrée / haché, jamais en clair)</li>
          <li>Type de compte (joueur ou club)</li>
          <li>Nom (et prénom pour les comptes joueur ; raison sociale pour les comptes club)</li>
        </ul>

        <h3>Données de profil</h3>
        <ul>
          <li>Localisation (ville / zone géographique déclarée)</li>
          <li>Sports pratiqués et niveaux déclarés</li>
          <li>Logo (pour les comptes club) — via une URL fournie par l'utilisateur</li>
        </ul>

        <h3>Données d'activité</h3>
        <ul>
          <li>Équipes créées ou rejointes</li>
          <li>Matchs créés, rejoints, et leurs résultats</li>
          <li>Messages échangés dans le cadre des matchs</li>
        </ul>

        <h2>3. Finalités du traitement</h2>
        <p>Ces données sont utilisées exclusivement pour :</p>
        <ul>
          <li>Permettre l'authentification et la gestion du compte</li>
          <li>Mettre en relation les joueurs et les clubs pour l'organisation de matchs amicaux</li>
          <li>Afficher les informations nécessaires au fonctionnement du service (équipes, matchs, participants)</li>
          <li>Filtrer les contenus pertinents selon les sports déclarés par l'utilisateur</li>
        </ul>
        <p>Aucune donnée n'est utilisée à des fins commerciales, publicitaires, ni cédée à des tiers.</p>

        <h2>4. Base légale</h2>
        <p>
          Le traitement repose sur le <strong>consentement</strong> de l'utilisateur, recueilli lors
          de l'inscription (acceptation explicite de la présente politique), et sur
          l'<strong>exécution du service</strong> demandé par l'utilisateur (mise en relation sportive).
        </p>

        <h2>5. Destinataires des données</h2>
        <ul>
          <li>
            Les autres utilisateurs de la plateforme ont accès aux données d'affichage nécessaires
            au service : nom / raison sociale, type de compte, localisation, logo, sports, ainsi que
            la participation aux équipes et matchs.
          </li>
          <li>
            <strong>L'adresse e-mail n'est jamais visible par les autres utilisateurs.</strong>
            Elle sert uniquement à l'authentification et n'est affichée qu'à l'utilisateur lui-même
            sur son propre profil.
          </li>
          <li>Aucune donnée n'est transmise à des tiers extérieurs à l'application.</li>
        </ul>

        <h2>6. Durée de conservation</h2>
        <p>
          Les données sont conservées tant que le compte est actif. En cas de suppression du compte
          par l'utilisateur, les données personnelles sont <strong>anonymisées</strong> (voir section 8) :
          elles ne permettent plus d'identifier la personne.
        </p>

        <h2>7. Sécurité</h2>
        <p>SportLink met en œuvre des mesures de protection des données :</p>
        <ul>
          <li>Mots de passe hachés (jamais stockés en clair)</li>
          <li>Authentification par jeton sécurisé (JWT)</li>
          <li>Accès aux données restreint par un système de permissions</li>
          <li>Communication chiffrée (HTTPS) en production</li>
          <li>Minimisation des données exposées (l'e-mail notamment n'est jamais partagé avec les autres utilisateurs)</li>
        </ul>

        <h2>8. Vos droits</h2>
        <p>
          Conformément au Règlement Général sur la Protection des Données (RGPD), vous disposez
          des droits suivants :
        </p>
        <ul>
          <li>
            <strong>Droit d'accès</strong> : vous pouvez consulter vos données personnelles depuis
            votre profil.
          </li>
          <li>
            <strong>Droit de rectification</strong> : vous pouvez modifier vos données (profil,
            sports, niveaux, logo) à tout moment.
          </li>
          <li>
            <strong>Droit à l'effacement</strong> : vous pouvez supprimer votre compte depuis votre
            profil. Vos données personnelles sont alors anonymisées de manière irréversible (votre
            e-mail, votre nom et vos informations d'identification sont effacés). L'historique des
            matchs et messages est conservé sous une forme anonymisée afin de préserver l'intégrité
            des données des autres utilisateurs.
          </li>
          <li>
            <strong>Droit d'opposition et de limitation</strong> : vous pouvez contacter le
            responsable du traitement pour toute demande relative à vos données.
          </li>
        </ul>
        <p>Pour exercer ces droits, contactez : <a href="mailto:tiago.duval5@gmail.com">tiago.duval5@gmail.com</a>.</p>

        <h2>9. Cookies et traceurs</h2>
        <p>
          SportLink utilise uniquement un stockage local (navigateur) pour conserver le jeton
          d'authentification nécessaire à la session. Aucun cookie de suivi, de mesure d'audience
          ou de publicité n'est utilisé.
        </p>

        <h2>10. Modification de la politique</h2>
        <p>
          Cette politique peut être mise à jour. Les utilisateurs seront informés de toute
          modification substantielle.
        </p>

      </div>
    </div>
  </div>
</template>

<style scoped>
.page-legale {
  background: #f5f9f5;
  min-height: 100vh;
}

.page-corps {
  padding-top: var(--espace-xl);
  padding-bottom: var(--espace-xxl);
  display: flex;
  flex-direction: column;
  gap: var(--espace-l);
}

/* ── En-tête ── */
.retour-lien {
  display: inline-block;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--couleur-primaire);
  text-decoration: none;
  margin-bottom: var(--espace-s);
}
.retour-lien:hover { text-decoration: underline; }

.page-eyebrow {
  display: inline-flex;
  align-items: center;
  background: var(--couleur-accent-fond);
  color: var(--couleur-accent-texte);
  padding: 0.2rem 0.7rem;
  border-radius: var(--rayon-badge);
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-bottom: var(--espace-s);
}

.page-titre {
  font-size: 1.6rem;
  font-weight: 800;
  letter-spacing: -0.025em;
  color: var(--couleur-titre);
  margin-bottom: 0.25rem;
}

.page-meta {
  font-size: 0.82rem;
  color: var(--couleur-texte-discret);
}

/* ── Prose ── */
.prose {
  background: white;
  border: 1.5px solid #dde8dd;
  border-radius: var(--rayon-carte);
  padding: var(--espace-xl) var(--espace-xl);
  max-width: 740px;
  line-height: 1.75;
  color: var(--couleur-texte);
  font-size: 0.92rem;
}

.prose h2 {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--couleur-titre);
  margin-top: var(--espace-xl);
  margin-bottom: var(--espace-s);
  padding-bottom: 0.35rem;
  border-bottom: 1px solid var(--couleur-bordure);
}

.prose h3 {
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--couleur-titre);
  margin-top: var(--espace-m);
  margin-bottom: var(--espace-xs);
}

.prose p {
  margin-bottom: var(--espace-s);
}

.prose ul {
  padding-left: 1.25rem;
  margin-bottom: var(--espace-s);
}

.prose li {
  margin-bottom: 0.3rem;
}

.prose strong {
  color: var(--couleur-titre);
  font-weight: 600;
}

.prose a {
  color: var(--couleur-primaire);
  text-decoration: none;
}
.prose a:hover { text-decoration: underline; }

.note-info {
  background: var(--couleur-accent-fond);
  color: var(--couleur-accent-texte);
  border-left: 3px solid var(--couleur-primaire);
  padding: var(--espace-s) var(--espace-m);
  border-radius: 0 6px 6px 0;
  font-size: 0.85rem;
  margin-bottom: var(--espace-m);
}

@media (max-width: 640px) {
  .prose {
    padding: var(--espace-l);
  }
  .page-titre { font-size: 1.3rem; }
}
</style>
