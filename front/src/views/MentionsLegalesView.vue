<template>
  <div class="page-legale">
    <div class="conteneur page-corps">

      <div class="page-entete">
        <RouterLink to="/" class="retour-lien">← Retour</RouterLink>
        <span class="page-eyebrow">Légal</span>
        <h1 class="page-titre">Mentions légales</h1>
        <p class="page-meta">Dernière mise à jour : 30/07/2026</p>
      </div>

      <div class="prose">

        <div class="note-info">
          <strong>Note</strong> : SportLink est une application développée dans le cadre d'un projet
          de formation (Concepteur Développeur d'Applications). Ces mentions légales sont fournies
          à titre pédagogique et de conformité.
        </div>

        <h2>1. Éditeur du site</h2>
        <ul>
          <li><strong>Nom / Prénom</strong> : DUVAL Tiago</li>
          <li><strong>Statut</strong> : Projet de formation — étudiant en formation CDA</li>
          <li><strong>Adresse e-mail de contact</strong> : <a href="mailto:tiago.duval5@gmail.com">tiago.duval5@gmail.com</a></li>
          <li><strong>Adresse</strong> : non communiquée dans le cadre d'un projet de formation</li>
        </ul>

        <h2>2. Directeur de la publication</h2>
        <p>DUVAL Tiago</p>

        <h2>3. Hébergement</h2>
        <p>Le site est hébergé par :</p>
        <ul>
          <li><strong>Hébergeur</strong> : [À compléter — nom de l'hébergeur, ex : OVH, Hetzner, Railway, ou « environnement local dans le cadre du projet de formation »]</li>
          <li><strong>Adresse de l'hébergeur</strong> : [À compléter]</li>
          <li><strong>Contact de l'hébergeur</strong> : [À compléter]</li>
        </ul>

        <h2>4. Objet du site</h2>
        <p>
          SportLink est une plateforme web de mise en relation d'équipes et de joueurs amateurs pour
          l'organisation de matchs amicaux. L'application permet aux clubs et aux joueurs de créer
          des équipes, d'organiser des rencontres et d'en suivre les résultats.
        </p>

        <h2>5. Propriété intellectuelle</h2>
        <p>
          L'ensemble des éléments constituant l'application (code source, structure, textes, éléments
          graphiques originaux) est la propriété de l'éditeur, sauf mention contraire.
        </p>
        <p>
          Le logo SportLink et les éléments de charte graphique sont réservés à l'usage de l'application.
        </p>
        <p>
          Les logos et contenus fournis par les utilisateurs (logos de clubs notamment) restent la
          propriété de leurs auteurs respectifs ; l'utilisateur est responsable des contenus qu'il
          fournit et garantit disposer des droits nécessaires.
        </p>

        <h2>6. Responsabilité</h2>
        <p>
          SportLink étant un projet de formation, l'application est fournie « en l'état », sans
          garantie de disponibilité ni d'exactitude des informations saisies par les utilisateurs.
          L'éditeur ne saurait être tenu responsable de l'organisation effective des matchs, du
          comportement des utilisateurs, ni des éventuels dommages liés à l'utilisation du service.
        </p>

        <h2>7. Données personnelles</h2>
        <p>
          Le traitement des données personnelles est décrit dans la
          <RouterLink to="/politique-confidentialite">Politique de confidentialité</RouterLink>.
          Conformément au RGPD, l'utilisateur dispose de droits d'accès, de rectification et
          d'effacement de ses données, exerçables depuis son profil ou en contactant l'éditeur.
        </p>

        <h2>8. Contact</h2>
        <p>
          Pour toute question relative au site ou à ces mentions légales :
          <a href="mailto:tiago.duval5@gmail.com">tiago.duval5@gmail.com</a>.
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
  padding: var(--espace-xl);
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
  .prose { padding: var(--espace-l); }
  .page-titre { font-size: 1.3rem; }
}
</style>
