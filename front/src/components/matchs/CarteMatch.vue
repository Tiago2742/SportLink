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
import IconeLigne from '@/components/ui/IconeLigne.vue'
import { Calendar, MapPin, User, Users } from 'lucide-vue-next'

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
    monStatutDemande?: string | null
  }
  afficherBoutonRejoindre?: boolean
  demandeEnvoyee?: boolean
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

// Demande active = demande en_attente connue côté API OU envoyée dans cette session
const aDemandeActive = computed(
  () => props.demandeEnvoyee || props.match.monStatutDemande === 'en_attente',
)

const peutRejoindreIndividuel = computed(
  () =>
    props.afficherBoutonRejoindre &&
    !dejaInscrit.value &&
    !aDemandeActive.value &&
    props.match.sport?.type === 'individuel' &&
    props.match.statut !== 'termine' &&
    props.match.statut !== 'annule' &&
    placesRestantes.value > 0,
)

const lienEquipeCollectif = computed(
  () =>
    props.afficherBoutonRejoindre &&
    !dejaInscrit.value &&
    !aDemandeActive.value &&
    props.match.sport?.type === 'collectif' &&
    auth.utilisateur?.type === 'club' &&
    placesRestantes.value > 0,
)
</script>

<template>
  <div class="carte-match carte carte-interactive" :class="{ 'carte-match--inscrit': dejaInscrit }">
    <div class="carte-corps">
      <span v-if="match.sport?.type" class="chip-type-sport">
        {{ match.sport.type === 'collectif' ? 'Collectif' : 'Individuel' }}
      </span>
      <h3 class="carte-titre">{{ titreMatch }}</h3>

      <ul class="carte-infos">
        <li>
          <IconeLigne :icone="Calendar" discret>{{ dateFormatee }}</IconeLigne>
        </li>
        <li v-if="match.lieu">
          <IconeLigne :icone="MapPin" discret>{{ match.lieu }}</IconeLigne>
        </li>
        <li v-if="match.createur">
          <IconeLigne :icone="User" discret>{{ nomAffichage(match.createur) }}</IconeLigne>
        </li>
      </ul>

      <div class="carte-tags" v-if="match.niveauRequis || match.statut">
        <BadgeStatut :statut="match.statut" />
        <span v-if="match.niveauRequis" class="tag-niveau">{{ match.niveauRequis.libelle }}</span>
        <span class="tag-joueurs">
          <IconeLigne :icone="Users" :taille="14" discret>{{ libellePlaces }}</IconeLigne>
        </span>
        <span v-if="dejaInscrit" class="tag-inscrit">Vous participez</span>
        <span v-else-if="aDemandeActive" class="tag-demande">Demande envoyée</span>
      </div>
    </div>

    <div class="carte-actions">
      <button
        v-if="peutRejoindreIndividuel"
        class="btn btn-primaire"
        @click="emit('rejoindre', match.id)"
      >
        Demander à rejoindre
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
  position: relative;
  overflow: hidden;
}

/* Filet gradient en haut de chaque carte */
.carte-match::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: var(--degrade-primaire);
}

/* État "je participe" : bordure gauche verte */
.carte-match--inscrit {
  border-left-width: 3px;
  border-left-color: var(--couleur-primaire);
}

.carte-corps {
  flex: 1;
}

.chip-type-sport {
  display: inline-flex;
  align-items: center;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--couleur-accent-texte);
  background: var(--couleur-accent-fond);
  padding: 0.18rem 0.55rem;
  border-radius: var(--rayon-badge);
  margin-bottom: var(--espace-xs);
}

.carte-titre {
  font-size: 1.1rem;
  font-weight: 700;
  letter-spacing: -0.015em;
  color: var(--couleur-titre);
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

.tag-demande {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--couleur-attente, #b45309);
  background: var(--couleur-attente-fond, #fff8e1);
  padding: 0.2rem 0.55rem;
  border-radius: var(--rayon-badge);
  margin-left: auto;
}

.carte-actions {
  display: flex;
  gap: var(--espace-s);
  flex-wrap: wrap;
  border-top: 1px solid var(--couleur-bordure);
  padding-top: var(--espace-s);
}

.carte-actions .btn {
  flex: 1;
  min-width: 0;
}
</style>
