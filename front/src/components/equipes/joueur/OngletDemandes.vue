<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import AvatarEquipe from '@/components/equipes/AvatarEquipe.vue'
import { retirerMembre } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import {
  classeBadgeStatutMembre,
  libelleStatutMembre,
} from '@/utils/equipeAffichage'
import { nomAffichage } from '@/utils/nomAffichage'

defineProps<{
  demandes: any[]
  chargement: boolean
}>()

const emit = defineEmits<{
  actualiser: []
}>()

const auth = useAuthStore()
const erreur = ref('')
const annulationEnCours = ref<number | null>(null)

async function annuler(demande: any) {
  annulationEnCours.value = demande.id
  erreur.value = ''
  try {
    await retirerMembre(auth.token!, demande.equipe.id, demande.id)
    emit('actualiser')
  } catch (e: any) {
    erreur.value = e.message || 'Annulation impossible.'
  } finally {
    annulationEnCours.value = null
  }
}
</script>

<template>
  <div class="onglet-demandes">
    <div v-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>
    <div v-if="chargement" class="chargement">Chargement...</div>
    <div v-else-if="!demandes.length" class="carte vide-centre">
      <p>Vous n'avez envoyé aucune demande d'adhésion.</p>
    </div>
    <div v-else class="liste-demandes">
      <article v-for="demande in demandes" :key="demande.id" class="carte carte-demande">
        <div class="demande-haut">
          <AvatarEquipe :equipe="demande.equipe" taille="md" />
          <div class="demande-corps">
            <h2>{{ demande.equipe?.nom }}</h2>
            <p class="demande-meta">
              {{ demande.equipe?.sport?.nom }}
              <template v-if="demande.equipe?.niveau"> · {{ demande.equipe.niveau.libelle }}</template>
            </p>
            <p class="demande-club">{{ nomAffichage(demande.equipe?.club) }}</p>
            <span
              class="demande-statut"
              :class="classeBadgeStatutMembre(demande.statut)"
            >
              {{ libelleStatutMembre(demande.statut) }}
            </span>
          </div>
        </div>
        <div class="demande-actions">
          <button
            v-if="demande.statut === 'en_attente'"
            class="btn btn-secondaire"
            :disabled="annulationEnCours === demande.id"
            @click="annuler(demande)"
          >
            Annuler la demande
          </button>
          <RouterLink
            :to="`/equipes/${demande.equipe?.id}`"
            class="btn btn-secondaire"
          >
            Voir l'équipe
          </RouterLink>
        </div>
      </article>
    </div>
  </div>
</template>

<style scoped>
.liste-demandes {
  display: flex;
  flex-direction: column;
  gap: var(--espace-m);
}

.carte-demande {
  padding: var(--espace-l);
}

.demande-haut {
  display: flex;
  gap: var(--espace-m);
  margin-bottom: var(--espace-m);
}

.demande-corps h2 {
  font-size: 1.1rem;
  margin-bottom: 0.2rem;
}

.demande-meta,
.demande-club {
  font-size: 0.88rem;
  color: var(--couleur-texte-discret);
}

.demande-statut {
  display: inline-block;
  margin-top: 0.35rem;
  font-size: 0.72rem;
  padding: 0.15rem 0.45rem;
  border-radius: var(--rayon-badge);
}

.badge-attente {
  background: var(--couleur-attente-fond, #fff8e1);
  color: var(--couleur-attente, #f57c00);
}

.badge-confirme {
  background: var(--couleur-confirme-fond);
  color: var(--couleur-confirme);
}

.badge-refuse {
  background: var(--couleur-refuse-fond);
  color: var(--couleur-refuse);
}

.demande-actions {
  display: flex;
  gap: var(--espace-s);
  flex-wrap: wrap;
}

.vide-centre {
  text-align: center;
  padding: var(--espace-xl);
}
</style>
