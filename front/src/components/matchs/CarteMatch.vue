<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import BadgeStatut from '@/components/commun/BadgeStatut.vue'
import {
  libellePlacesMatch,
  utilisateurEstInscrit,
  type CampResume,
} from '@/composables/useMatchCamps'
import { nomAffichage } from '@/utils/nomAffichage'

const props = defineProps<{
  match: {
    id: number
    sport?: { id: number; nom: string; type: string }
    dateMatch: string
    lieu?: string
    niveauRequis?: { id: number; libelle: string; ordre: number }
    statut: string
    createur?: { id: number; nom: string; prenom?: string | null; type?: string }
    nombreCamps?: number
    camps?: CampResume[]
  }
  afficherBoutonRejoindre?: boolean
}>()

const auth = useAuthStore()

const emit = defineEmits<{
  rejoindre: [matchId: number]
}>()

const titreMatch = computed(() => `Match de ${props.match.sport?.nom ?? 'Sport'}`)

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

const nombreInscrits = computed(
  () => props.match.nombreCamps ?? props.match.camps?.length ?? 0,
)

const placesRestantes = computed(() => Math.max(0, 2 - nombreInscrits.value))

const libellePlaces = computed(() => libellePlacesMatch(nombreInscrits.value))

const dejaInscrit = computed(() =>
  utilisateurEstInscrit(props.match, auth.utilisateur?.id),
)

const peutRejoindreIndividuel = computed(
  () =>
    props.afficherBoutonRejoindre &&
    !dejaInscrit.value &&
    props.match.sport?.type === 'individuel' &&
    props.match.statut !== 'termine' &&
    props.match.statut !== 'annule' &&
    placesRestantes.value > 0,
)

const lienEquipeCollectif = computed(
  () =>
    props.afficherBoutonRejoindre &&
    !dejaInscrit.value &&
    props.match.sport?.type === 'collectif' &&
    auth.utilisateur?.type === 'club' &&
    placesRestantes.value > 0,
)
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
          <span>{{ nomAffichage(match.createur) }}</span>
        </li>
      </ul>

      <div class="carte-tags" v-if="match.niveauRequis || match.statut">
        <BadgeStatut :statut="match.statut" />
        <span v-if="match.niveauRequis" class="tag-niveau">{{ match.niveauRequis.libelle }}</span>
        <span class="tag-joueurs">👥 {{ libellePlaces }}</span>
        <span v-if="dejaInscrit" class="tag-inscrit">Vous participez</span>
      </div>
    </div>

    <div class="carte-actions">
      <button
        v-if="peutRejoindreIndividuel"
        class="btn btn-primaire"
        @click="emit('rejoindre', match.id)"
      >
        Rejoindre le match
      </button>
      <RouterLink
        v-else-if="lienEquipeCollectif"
        :to="`/matchs/${match.id}`"
        class="btn btn-primaire"
      >
        Inscrire une équipe
      </RouterLink>
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
}

.tag-inscrit {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--couleur-primaire);
  background: var(--couleur-primaire-tres-claire);
  padding: 0.2rem 0.55rem;
  border-radius: var(--rayon-badge);
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
