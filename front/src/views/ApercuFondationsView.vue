<script setup lang="ts">
import AvatarEquipe from '@/components/equipes/AvatarEquipe.vue'
import BadgeStatut from '@/components/commun/BadgeStatut.vue'
import CarteMatch from '@/components/matchs/CarteMatch.vue'
import EnteteDegrade from '@/components/ui/EnteteDegrade.vue'
import IconeSection from '@/components/ui/IconeSection.vue'
import BadgeCompteur from '@/components/ui/BadgeCompteur.vue'
import { Calendar, Users, Zap } from 'lucide-vue-next'

const equipeDemo = { nom: 'Les Aigles', logo: null }
const matchDemo = {
  id: 1,
  sport: { id: 1, nom: 'Football', type: 'collectif' },
  dateMatch: new Date(Date.now() + 86400000 * 3).toISOString(),
  lieu: 'Stade municipal',
  statut: 'en_attente',
  createur: { id: 1, nom: 'Dupont', prenom: 'Jean', type: 'joueur' },
  nombreCamps: 1,
  camps: [],
}
</script>

<template>
  <div class="apercu conteneur">
    <EnteteDegrade
      titre="Fondations SportLink"
      sous-titre="Tokens, composants de base et icônes Lucide — les écrans hériteront de ce style."
    />

    <section class="carte bloc">
      <div class="bloc-entete">
        <IconeSection :icone="Zap" label="Typographie" />
        <h2>Typographie</h2>
      </div>
      <h1>Titre H1 — net et dynamique</h1>
      <h2>Titre H2 — section</h2>
      <h3>Titre H3 — sous-section</h3>
      <p>Corps de texte avec couleur discrète pour les métadonnées.</p>
    </section>

    <section class="carte bloc">
      <div class="bloc-entete">
        <IconeSection :icone="Users" />
        <h2>Boutons &amp; champs</h2>
      </div>
      <div class="rangee-boutons">
        <button type="button" class="btn btn-primaire">Primaire (dégradé)</button>
        <button type="button" class="btn btn-secondaire">Secondaire</button>
        <button type="button" class="btn btn-danger">Danger</button>
        <span class="btn btn-primaire">Primaire + badge <BadgeCompteur :nombre="3" /></span>
      </div>
      <div class="champ-groupe">
        <label for="demo-champ">Champ formulaire</label>
        <input id="demo-champ" class="champ" type="text" placeholder="Focus avec halo lime" />
      </div>
    </section>

    <section class="carte bloc">
      <div class="bloc-entete">
        <IconeSection :icone="Calendar" />
        <h2>Badges &amp; avatars</h2>
      </div>
      <div class="rangee-badges">
        <BadgeStatut statut="confirme" />
        <BadgeStatut statut="en_attente" />
        <BadgeStatut statut="refuse" />
        <BadgeStatut statut="termine" />
        <span class="badge badge-accent">Accent lime</span>
      </div>
      <div class="rangee-avatars">
        <AvatarEquipe :equipe="equipeDemo" taille="sm" />
        <AvatarEquipe :equipe="equipeDemo" taille="md" />
        <AvatarEquipe :equipe="equipeDemo" taille="lg" />
      </div>
    </section>

    <section class="grille-2">
      <CarteMatch :match="matchDemo" :afficher-bouton-rejoindre="true" />
      <div class="carte carte-interactive bloc-demo-carte">
        <h3>Carte interactive</h3>
        <p>Survol : translateY −3px + ombre marquée</p>
      </div>
    </section>
  </div>
</template>

<style scoped>
.apercu {
  padding: var(--espace-xl) 0 var(--espace-xxl);
}

.bloc {
  padding: var(--espace-l);
  margin-bottom: var(--espace-l);
}

.bloc-entete {
  display: flex;
  align-items: center;
  gap: var(--espace-s);
  margin-bottom: var(--espace-m);
}

.bloc-entete h2 {
  margin: 0;
}

.rangee-boutons {
  display: flex;
  flex-wrap: wrap;
  gap: var(--espace-s);
  margin-bottom: var(--espace-m);
  align-items: center;
}

.rangee-badges,
.rangee-avatars {
  display: flex;
  flex-wrap: wrap;
  gap: var(--espace-m);
  align-items: center;
  margin-bottom: var(--espace-m);
}

.bloc-demo-carte {
  padding: var(--espace-l);
  min-height: 180px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: var(--espace-s);
}
</style>
