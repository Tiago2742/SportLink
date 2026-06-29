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
  <div class="page-espace-joueur">
    <div class="conteneur page-corps">

      <div class="entete-page">
        <span class="page-eyebrow">Joueur</span>
        <h1 class="page-titre">Mes équipes</h1>
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
        @actualiser="charger"
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
  </div>
</template>

<style scoped>
/* ══════════════════════════════════════
   FOND PLEIN-LARGEUR
══════════════════════════════════════ */
.page-espace-joueur {
  background: #f5f9f5;
  min-height: 100vh;
  position: relative;
  overflow: hidden;
}

.page-espace-joueur::before {
  content: '';
  position: absolute;
  top: -80px; right: -80px;
  width: 380px; height: 380px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(154, 230, 0, 0.09) 0%, transparent 60%);
  pointer-events: none;
}

.page-espace-joueur::after {
  content: '';
  position: absolute;
  bottom: -100px; left: -100px;
  width: 300px; height: 300px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(46, 125, 50, 0.07) 0%, transparent 60%);
  pointer-events: none;
}

.page-corps {
  padding-top: var(--espace-xl);
  padding-bottom: var(--espace-xxl);
  position: relative;
  z-index: 1;
}

/* ══════════════════════════════════════
   EN-TÊTE
══════════════════════════════════════ */
.entete-page {
  margin-bottom: var(--espace-xl);
  animation: fadeUp 0.45s cubic-bezier(0.22, 1, 0.36, 1) both;
}

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
  margin-bottom: 0.15rem;
}

.sous-titre {
  color: var(--couleur-texte-discret);
  font-size: 0.88rem;
}

/* ══════════════════════════════════════
   ONGLETS
══════════════════════════════════════ */
.onglets {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-bottom: var(--espace-l);
  background: #ffffff;
  border: 1.5px solid #dde8dd;
  border-radius: 10px;
  padding: 4px;
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.08s both;
}

.onglet-btn {
  background: none;
  border: none;
  border-radius: 6px;
  padding: 0.55rem 1rem;
  font-size: 0.88rem;
  color: var(--couleur-texte-discret);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-weight: 500;
  transition: background 0.18s ease, color 0.18s ease;
}

.onglet-btn:hover {
  background: var(--couleur-primaire-tres-claire);
  color: var(--couleur-primaire);
}

.onglet-btn.actif {
  background: var(--couleur-accent-fond);
  color: var(--couleur-primaire);
  font-weight: 700;
}

/* ══════════════════════════════════════
   ANIMATIONS
══════════════════════════════════════ */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(14px); }
  to   { opacity: 1; transform: translateY(0); }
}
</style>
