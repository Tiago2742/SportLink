<script setup lang="ts">
import { computed } from 'vue'
import BadgeStatut from '@/components/commun/BadgeStatut.vue'

const props = defineProps<{
  match: {
    id: number
    sport: string
    dateMatch: string
    lieu?: string
    niveauRequis?: string
    statut: string
    createur?: { nom: string; prenom: string }
    participations?: any[]
  }
  afficherBoutonRejoindre?: boolean
}>()

const emit = defineEmits<{
  rejoindre: [matchId: number]
}>()

const titreMatch = computed(() => {
  const sport = props.match.sport
    ? props.match.sport.charAt(0).toUpperCase() + props.match.sport.slice(1)
    : 'Sport'
  return `Match de ${sport}`
})

const dateFormatee = computed(() => {
  if (!props.match.dateMatch) return ''
  return new Date(props.match.dateMatch).toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
})

const nombreParticipants = computed(() => props.match.participations?.length ?? 0)
</script>

<template>
  <div class="carte-match carte">
    <div class="carte-corps">
      <h3 class="carte-titre">{{ titreMatch }}</h3>

      <ul class="carte-infos">
        <li>
          <span class="icone">📅</span>
          <span>{{ dateFormatee }}</span>
        </li>
        <li v-if="match.lieu">
          <span class="icone">📍</span>
          <span>{{ match.lieu }}</span>
        </li>
        <li v-if="match.createur">
          <span class="icone">👤</span>
          <span>{{ match.createur.prenom }} {{ match.createur.nom }}</span>
        </li>
      </ul>

      <div class="carte-tags" v-if="match.niveauRequis || match.statut">
        <BadgeStatut :statut="match.statut" />
        <span v-if="match.niveauRequis" class="tag-niveau">{{ match.niveauRequis }}</span>
        <span v-if="match.participations !== undefined" class="tag-joueurs">
          👥 {{ nombreParticipants }} joueur{{ nombreParticipants > 1 ? 's' : '' }}
        </span>
      </div>
    </div>

    <div class="carte-actions">
      <button
        v-if="afficherBoutonRejoindre && match.statut === 'ouvert'"
        class="btn btn-primaire"
        @click="emit('rejoindre', match.id)"
      >
        Rejoindre match
      </button>
      <RouterLink :to="`/matchs/${match.id}`" class="btn btn-secondaire">
        Voir détails
      </RouterLink>
    </div>
  </div>
</template>

<style scoped>
.carte-match {
  display: flex;
  flex-direction: column;
  gap: var(--espace-m);
  padding: var(--espace-l);
  transition: box-shadow 0.2s;
}

.carte-match:hover {
  box-shadow: var(--ombre-carte-survol);
}

.carte-corps {
  flex: 1;
}

.carte-titre {
  font-size: 1rem;
  font-weight: 600;
  color: var(--couleur-texte);
  margin-bottom: var(--espace-s);
}

.carte-infos {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  margin-bottom: var(--espace-s);
}

.carte-infos li {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.88rem;
  color: var(--couleur-texte-discret);
}

.icone {
  font-size: 0.85rem;
  flex-shrink: 0;
}

.carte-tags {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--espace-xs);
  margin-top: var(--espace-s);
}

.tag-niveau {
  background: var(--couleur-info-fond);
  color: var(--couleur-info);
  padding: 0.2rem 0.6rem;
  border-radius: var(--rayon-badge);
  font-size: 0.78rem;
  font-weight: 600;
}

.tag-joueurs {
  font-size: 0.82rem;
  color: var(--couleur-texte-discret);
  margin-left: auto;
}

.carte-actions {
  display: flex;
  gap: var(--espace-s);
  flex-wrap: wrap;
}

.carte-actions .btn {
  flex: 1;
  min-width: 0;
}
</style>
