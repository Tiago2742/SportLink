<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { chargerMatchs } from '@/services/api'
import BadgeStatut from '@/components/commun/BadgeStatut.vue'

const auth = useAuthStore()
const router = useRouter()

const matchs = ref<any[]>([])
const chargement = ref(true)
const erreur = ref('')
const filtreStatut = ref('')

onMounted(charger)

async function charger() {
  chargement.value = true
  const userId = auth.utilisateur?.id
  try {
    matchs.value = await chargerMatchs(auth.token!, userId ? { createurId: userId } : {})
  } catch {
    erreur.value = 'Impossible de charger vos matchs.'
  } finally {
    chargement.value = false
  }
}

const mesMatchs = computed(() => matchs.value)

const matchsFiltres = computed(() => {
  if (!filtreStatut.value) return mesMatchs.value
  return mesMatchs.value.filter((m) => m.statut === filtreStatut.value)
})

const matchsAvenir = computed(() => {
  const maintenant = new Date()
  return matchsFiltres.value.filter((m) => new Date(m.dateMatch) >= maintenant)
})

const matchsPasses = computed(() => {
  const maintenant = new Date()
  return matchsFiltres.value.filter((m) => new Date(m.dateMatch) < maintenant)
})

function formaterDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<template>
  <div class="page-mes-matchs conteneur">
    <div class="page-entete">
      <div>
        <h1>Mes matchs</h1>
        <p class="sous-titre">Gérez vos matchs créés</p>
      </div>
      <RouterLink to="/creer-match" class="btn btn-primaire">+ Créer un match</RouterLink>
    </div>

    <!-- Filtres rapides -->
    <div class="filtres-statut">
      <button
        class="chip"
        :class="{ actif: filtreStatut === '' }"
        @click="filtreStatut = ''"
      >
        Tous ({{ mesMatchs.length }})
      </button>
      <button
        class="chip"
        :class="{ actif: filtreStatut === 'ouvert' }"
        @click="filtreStatut = 'ouvert'"
      >
        Ouverts
      </button>
      <button
        class="chip"
        :class="{ actif: filtreStatut === 'terminé' }"
        @click="filtreStatut = 'terminé'"
      >
        Terminés
      </button>
    </div>

    <div v-if="chargement" class="chargement">Chargement...</div>
    <div v-else-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>

    <template v-else>
      <!-- Matchs à venir -->
      <section v-if="matchsAvenir.length > 0">
        <h2 class="section-titre">À venir ({{ matchsAvenir.length }})</h2>
        <div class="carte liste-matchs">
          <div
            v-for="match in matchsAvenir"
            :key="match.id"
            class="ligne-match"
            @click="router.push(`/matchs/${match.id}`)"
          >
            <div class="ligne-gauche">
              <div class="ligne-sport-icone">🏟️</div>
              <div class="ligne-info">
                <strong>
                  {{ match.sport.charAt(0).toUpperCase() + match.sport.slice(1) }}
                </strong>
                <span>{{ formaterDate(match.dateMatch) }}</span>
                <span v-if="match.lieu" class="ligne-lieu">📍 {{ match.lieu }}</span>
              </div>
            </div>
            <div class="ligne-droite">
              <BadgeStatut :statut="match.statut" />
              <span class="ligne-fleche">›</span>
            </div>
          </div>
        </div>
      </section>

      <!-- Matchs passés -->
      <section v-if="matchsPasses.length > 0" style="margin-top: var(--espace-xl)">
        <h2 class="section-titre">Passés ({{ matchsPasses.length }})</h2>
        <div class="carte liste-matchs liste-passee">
          <div
            v-for="match in matchsPasses"
            :key="match.id"
            class="ligne-match"
            @click="router.push(`/matchs/${match.id}`)"
          >
            <div class="ligne-gauche">
              <div class="ligne-sport-icone passé">🏟️</div>
              <div class="ligne-info">
                <strong>
                  {{ match.sport.charAt(0).toUpperCase() + match.sport.slice(1) }}
                </strong>
                <span>{{ formaterDate(match.dateMatch) }}</span>
              </div>
            </div>
            <div class="ligne-droite">
              <BadgeStatut statut="terminé" />
              <span class="ligne-fleche">›</span>
            </div>
          </div>
        </div>
      </section>

      <!-- Vide -->
      <div v-if="mesMatchs.length === 0" class="vide">
        <p>Vous n'avez pas encore créé de match.</p>
        <RouterLink to="/creer-match" class="btn btn-primaire" style="margin-top: var(--espace-m)">
          Créer mon premier match
        </RouterLink>
      </div>
    </template>
  </div>
</template>

<style scoped>
.page-mes-matchs {
  padding-top: var(--espace-xl);
  padding-bottom: var(--espace-xxl);
}

.page-entete {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--espace-xl);
}

.page-entete h1 {
  font-size: 1.6rem;
  font-weight: 700;
}

.sous-titre {
  color: var(--couleur-texte-discret);
  font-size: 0.9rem;
}

.filtres-statut {
  display: flex;
  gap: var(--espace-s);
  margin-bottom: var(--espace-l);
  flex-wrap: wrap;
}

.chip {
  padding: 0.35rem 1rem;
  border: 1.5px solid var(--couleur-bordure);
  border-radius: var(--rayon-badge);
  background: white;
  cursor: pointer;
  font-size: 0.85rem;
  transition: all 0.2s;
}

.chip.actif {
  border-color: var(--couleur-primaire);
  color: var(--couleur-primaire);
  background: var(--couleur-primaire-tres-claire);
}

.section-titre {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: var(--espace-s);
  color: var(--couleur-texte-discret);
  text-transform: uppercase;
  font-size: 0.82rem;
  letter-spacing: 0.5px;
}

.liste-matchs {
  overflow: hidden;
}

.liste-passee {
  opacity: 0.75;
}

.ligne-match {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--espace-m) var(--espace-l);
  border-bottom: 1px solid var(--couleur-bordure);
  cursor: pointer;
  transition: background 0.15s;
}

.ligne-match:last-child {
  border-bottom: none;
}

.ligne-match:hover {
  background: var(--couleur-fond);
}

.ligne-gauche {
  display: flex;
  align-items: center;
  gap: var(--espace-m);
}

.ligne-sport-icone {
  width: 40px;
  height: 40px;
  background: var(--couleur-primaire-tres-claire);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.ligne-sport-icone.passé {
  background: #f5f5f5;
}

.ligne-info {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.ligne-info strong {
  font-size: 0.95rem;
}

.ligne-info span {
  font-size: 0.82rem;
  color: var(--couleur-texte-discret);
}

.ligne-lieu {
  font-size: 0.8rem;
}

.ligne-droite {
  display: flex;
  align-items: center;
  gap: var(--espace-m);
}

.ligne-fleche {
  color: var(--couleur-texte-discret);
  font-size: 1.2rem;
}

.vide {
  text-align: center;
  padding: var(--espace-xxl);
  color: var(--couleur-texte-discret);
  background: white;
  border-radius: var(--rayon-carte);
  border: 1px dashed var(--couleur-bordure);
  display: flex;
  flex-direction: column;
  align-items: center;
}
</style>
