<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import BadgeCompteur from '@/components/ui/BadgeCompteur.vue'
import OngletMesEquipes from '@/components/equipes/joueur/OngletMesEquipes.vue'
import OngletInvitations from '@/components/equipes/joueur/OngletInvitations.vue'
import OngletDemandes from '@/components/equipes/joueur/OngletDemandes.vue'
import OngletTrouverEquipe from '@/components/equipes/joueur/OngletTrouverEquipe.vue'
import { useEspaceEquipesJoueur } from '@/composables/useEspaceEquipesJoueur'
import type { EspaceEquipesJoueur } from '@/services/api'

type OngletId = 'mes-equipes' | 'invitations' | 'demandes' | 'trouver'

const route = useRoute()
const router = useRouter()
const { nbInvitations, adhesionsFusionnees, chargerEspace } = useEspaceEquipesJoueur()

const chargement = ref(true)
const erreur = ref('')
const espace = ref<EspaceEquipesJoueur | null>(null)

const onglets: { id: OngletId; label: string; badge?: boolean }[] = [
  { id: 'mes-equipes', label: 'Mes équipes' },
  { id: 'invitations', label: 'Invitations', badge: true },
  { id: 'demandes', label: 'Demandes' },
  { id: 'trouver', label: 'Trouver une équipe' },
]

const ongletActif = computed<OngletId>(() => {
  const q = route.query.onglet as string
  if (onglets.some((o) => o.id === q)) return q as OngletId
  return 'mes-equipes'
})

function changerOnglet(id: OngletId) {
  router.replace({ query: { ...route.query, onglet: id } })
}

onMounted(async () => {
  await charger()
})

watch(
  () => route.query.onglet,
  () => {
    if (!route.query.onglet) {
      router.replace({ query: { onglet: 'mes-equipes' } })
    }
  },
  { immediate: true },
)

async function charger() {
  chargement.value = true
  erreur.value = ''
  try {
    espace.value = await chargerEspace()
  } catch {
    erreur.value = 'Impossible de charger votre espace équipe.'
  } finally {
    chargement.value = false
  }
}
</script>

<template>
  <div class="page-espace-joueur conteneur">
    <div class="entete-page">
      <h1>Mes équipes</h1>
      <p class="sous-titre">Vos adhésions, invitations et demandes</p>
    </div>

    <nav class="onglets" aria-label="Espace équipe">
      <button
        v-for="onglet in onglets"
        :key="onglet.id"
        type="button"
        class="onglet-btn"
        :class="{ actif: ongletActif === onglet.id }"
        @click="changerOnglet(onglet.id)"
      >
        {{ onglet.label }}
        <BadgeCompteur
          v-if="onglet.badge"
          :nombre="nbInvitations"
        />
      </button>
    </nav>

    <div v-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>

    <OngletMesEquipes
      v-show="ongletActif === 'mes-equipes'"
      :mes-equipes="espace?.mesEquipes ?? []"
      :chargement="chargement"
    />
    <OngletInvitations
      v-show="ongletActif === 'invitations'"
      :invitations="espace?.invitations ?? []"
      :chargement="chargement"
      @actualiser="charger"
    />
    <OngletDemandes
      v-show="ongletActif === 'demandes'"
      :demandes="espace?.demandes ?? []"
      :chargement="chargement"
      @actualiser="charger"
    />
    <OngletTrouverEquipe
      v-show="ongletActif === 'trouver'"
      :adhesions="adhesionsFusionnees"
      @actualiser="charger"
    />
  </div>
</template>

<style scoped>
.page-espace-joueur {
  padding-top: var(--espace-xl);
  padding-bottom: var(--espace-xxl);
}

.entete-page {
  margin-bottom: var(--espace-l);
}

.entete-page h1 {
  font-size: 1.5rem;
  margin-bottom: 0.25rem;
}

.sous-titre {
  color: var(--couleur-texte-discret);
  font-size: 0.9rem;
}

.onglets {
  display: flex;
  flex-wrap: wrap;
  gap: var(--espace-xs);
  margin-bottom: var(--espace-l);
  border-bottom: 1px solid var(--couleur-bordure);
  padding-bottom: 0;
}

.onglet-btn {
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  padding: 0.65rem 1rem;
  margin-bottom: -1px;
  font-size: 0.9rem;
  color: var(--couleur-texte-discret);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
}

.onglet-btn:hover {
  color: var(--couleur-primaire);
}

.onglet-btn.actif {
  color: var(--couleur-primaire);
  border-bottom-color: var(--couleur-primaire);
  font-weight: 600;
}
</style>
