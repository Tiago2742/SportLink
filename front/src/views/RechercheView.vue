<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { chargerMatchs, participer } from '@/services/api'
import { useSports } from '@/composables/useSports'
import CarteMatch from '@/components/matchs/CarteMatch.vue'
import SelecteurFiltresSport from '@/components/form/SelecteurFiltresSport.vue'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const matchs = ref<any[]>([])
const chargement = ref(true)
const erreur = ref('')

const filtres = ref({
  sport: (route.query.sport as string) || '',
  niveauRequis: (route.query.niveau as string) || '',
  statut: '',
  lieu: (route.query.localisation as string) || '',
})

const recherche = ref((route.query.q as string) || '')
const triParDate = ref('asc')

const pageCourante = ref(1)
const parPage = 6

const { listeSports, chargerCatalogue } = useSports()
const chipsRapides = computed(() => listeSports.value.slice(0, 5))

onMounted(() => {
  chargerCatalogue()
  charger()
})

let debounceTimeout: ReturnType<typeof setTimeout> | null = null
watch(
  filtres,
  () => {
    if (debounceTimeout) clearTimeout(debounceTimeout)
    debounceTimeout = setTimeout(charger, 350)
  },
  { deep: true },
)

async function charger() {
  chargement.value = true
  erreur.value = ''
  try {
    const params: Record<string, string> = {}
    if (filtres.value.sport) params.sport = filtres.value.sport
    if (filtres.value.niveauRequis) params.niveauRequis = filtres.value.niveauRequis
    if (filtres.value.statut) params.statut = filtres.value.statut
    if (filtres.value.lieu) params.lieu = filtres.value.lieu
    matchs.value = await chargerMatchs(auth.token!, params)
    pageCourante.value = 1
  } catch {
    erreur.value = 'Impossible de charger les matchs.'
  } finally {
    chargement.value = false
  }
}

function appliquerFiltreRapide(sport: string) {
  filtres.value.sport = filtres.value.sport === sport ? '' : sport
}

function filtreRapideAujourdhui() {
  const auj = new Date().toISOString().split('T')[0]
  filtres.value.lieu = filtres.value.lieu === auj ? '' : auj
}

const matchsFiltres = computed(() => {
  let liste = matchs.value
  if (recherche.value) {
    const q = recherche.value.toLowerCase()
    liste = liste.filter(
      (m) =>
        m.sport?.toLowerCase().includes(q) ||
        m.lieu?.toLowerCase().includes(q) ||
        m.niveauRequis?.toLowerCase().includes(q),
    )
  }
  return liste
})

const matchsTries = computed(() => {
  return [...matchsFiltres.value].sort((a, b) => {
    const diff = new Date(a.dateMatch).getTime() - new Date(b.dateMatch).getTime()
    return triParDate.value === 'asc' ? diff : -diff
  })
})

const totalPages = computed(() => Math.ceil(matchsTries.value.length / parPage))

const matchsPage = computed(() =>
  matchsTries.value.slice((pageCourante.value - 1) * parPage, pageCourante.value * parPage),
)

async function rejoindreMatch(matchId: number) {
  try {
    await participer(auth.token!, matchId)
    await charger()
  } catch (e: any) {
    if (e.statut === 422) alert('Vous participez déjà à ce match.')
  }
}
</script>

<template>
  <div class="page-recherche conteneur">
    <div class="recherche-layout">
      <!-- Sidebar filtres -->
      <aside class="sidebar-filtres carte">
        <h3 class="sidebar-titre">Filtres</h3>
        <p class="sidebar-sous-titre">Filtrer par sport, niveau, localisation</p>

        <div class="filtre-section">
          <label class="filtre-label">Sport &amp; Niveau</label>
          <SelecteurFiltresSport
            :sport="filtres.sport"
            :niveau="filtres.niveauRequis"
            @update:sport="(v) => { filtres.sport = v; filtres.niveauRequis = '' }"
            @update:niveau="(v) => filtres.niveauRequis = v"
          />
        </div>

        <div class="filtre-section">
          <label class="filtre-label">Statut</label>
          <select v-model="filtres.statut" class="champ">
            <option value="">Tous</option>
            <option value="ouvert">Ouvert</option>
            <option value="complet">Complet</option>
            <option value="terminé">Terminé</option>
          </select>
        </div>

        <div class="filtre-section">
          <label class="filtre-label">Localisation</label>
          <input v-model="filtres.lieu" type="text" class="champ" placeholder="Ville ou terrain" />
        </div>

        <button
          class="btn btn-secondaire btn-pleine-largeur"
          @click="filtres = { sport: '', niveauRequis: '', statut: '', lieu: '' }"
        >
          Réinitialiser
        </button>

        <!-- Recommandations sport populaire -->
        <div class="sidebar-recommandations" v-if="matchs.length > 0">
          <h4>Recommandations</h4>
          <div class="reco-item" @click="filtres.sport = 'football'">
            <span class="reco-nom">Match populaire</span>
            <span class="reco-detail">Football — ce weekend</span>
          </div>
          <div class="reco-item" @click="filtres.lieu = 'Paris'">
            <span class="reco-nom">Près de vous</span>
            <span class="reco-detail">Tennis — aujourd'hui 18h</span>
          </div>
        </div>
      </aside>

      <!-- Contenu principal -->
      <main class="recherche-contenu">
        <!-- Barre de recherche + chips -->
        <div class="recherche-barre">
          <div class="recherche-input-wrapper">
            <span class="recherche-icone">🔍</span>
            <input
              v-model="recherche"
              type="text"
              class="champ recherche-input"
              placeholder="Rechercher un match..."
            />
          </div>
        </div>

        <div class="chips-rapides">
          <button
            v-for="sport in chipsRapides"
            :key="sport"
            class="chip"
            :class="{ actif: filtres.sport === sport }"
            @click="appliquerFiltreRapide(sport)"
          >
            {{ sport }}
          </button>
        </div>

        <!-- En-tête résultats -->
        <div class="resultats-entete">
          <h2 class="section-titre">
            Matchs disponibles
            <span class="resultats-count">({{ matchsFiltres.length }} résultats)</span>
          </h2>
          <select v-model="triParDate" class="champ champ-tri">
            <option value="asc">Trier par date ↑</option>
            <option value="desc">Trier par date ↓</option>
          </select>
        </div>

        <!-- États -->
        <div v-if="chargement" class="chargement">Chargement des matchs...</div>
        <div v-else-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>
        <div v-else-if="matchsPage.length === 0" class="vide">
          Aucun match ne correspond à vos critères.
        </div>

        <!-- Liste des matchs -->
        <div v-else class="liste-resultats">
          <CarteMatch
            v-for="match in matchsPage"
            :key="match.id"
            :match="match"
            :afficher-bouton-rejoindre="true"
            @rejoindre="rejoindreMatch"
          />
        </div>

        <!-- Pagination -->
        <div class="pagination" v-if="totalPages > 1">
          <button
            class="page-btn"
            :disabled="pageCourante === 1"
            @click="pageCourante--"
          >
            ‹
          </button>
          <button
            v-for="p in totalPages"
            :key="p"
            class="page-btn"
            :class="{ actif: p === pageCourante }"
            @click="pageCourante = p"
          >
            {{ p }}
          </button>
          <button
            class="page-btn"
            :disabled="pageCourante === totalPages"
            @click="pageCourante++"
          >
            ›
          </button>
        </div>
      </main>
    </div>
  </div>
</template>

<style scoped>
.page-recherche {
  padding-top: var(--espace-xl);
  padding-bottom: var(--espace-xxl);
}

.recherche-layout {
  display: grid;
  grid-template-columns: 260px 1fr;
  gap: var(--espace-xl);
  align-items: start;
}

.sidebar-filtres {
  padding: var(--espace-l);
  position: sticky;
  top: 80px;
}

.sidebar-titre {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 0.2rem;
}

.sidebar-sous-titre {
  font-size: 0.8rem;
  color: var(--couleur-texte-discret);
  margin-bottom: var(--espace-l);
}

.filtre-section {
  margin-bottom: var(--espace-m);
}

.filtre-label {
  display: block;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--couleur-texte-discret);
  margin-bottom: 0.3rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.sidebar-recommandations {
  margin-top: var(--espace-l);
  border-top: 1px solid var(--couleur-bordure);
  padding-top: var(--espace-m);
}

.sidebar-recommandations h4 {
  font-size: 0.85rem;
  font-weight: 700;
  margin-bottom: var(--espace-s);
  color: var(--couleur-texte-discret);
  text-transform: uppercase;
}

.reco-item {
  padding: var(--espace-s);
  border-radius: var(--rayon-bouton);
  cursor: pointer;
  transition: background 0.15s;
  margin-bottom: var(--espace-xs);
}

.reco-item:hover {
  background: var(--couleur-fond);
}

.reco-nom {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
}

.reco-detail {
  display: block;
  font-size: 0.78rem;
  color: var(--couleur-texte-discret);
}

.recherche-contenu {
  min-width: 0;
}

.recherche-barre {
  margin-bottom: var(--espace-m);
}

.recherche-input-wrapper {
  position: relative;
}

.recherche-icone {
  position: absolute;
  left: 0.8rem;
  top: 50%;
  transform: translateY(-50%);
  font-size: 0.9rem;
}

.recherche-input {
  padding-left: 2.2rem;
}

.chips-rapides {
  display: flex;
  gap: var(--espace-s);
  flex-wrap: wrap;
  margin-bottom: var(--espace-l);
}

.chip {
  padding: 0.35rem 0.9rem;
  border: 1.5px solid var(--couleur-bordure);
  border-radius: var(--rayon-badge);
  background: white;
  cursor: pointer;
  font-size: 0.85rem;
  color: var(--couleur-texte);
  transition: all 0.2s;
}

.chip:hover,
.chip.actif {
  border-color: var(--couleur-primaire);
  color: var(--couleur-primaire);
  background: var(--couleur-primaire-tres-claire);
}

.resultats-entete {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--espace-l);
}

.section-titre {
  font-size: 1.2rem;
  font-weight: 700;
}

.resultats-count {
  font-size: 1rem;
  font-weight: 400;
  color: var(--couleur-texte-discret);
}

.champ-tri {
  width: auto;
  min-width: 160px;
}

.liste-resultats {
  display: flex;
  flex-direction: column;
  gap: var(--espace-m);
}

.vide {
  text-align: center;
  padding: var(--espace-xl);
  color: var(--couleur-texte-discret);
  background: white;
  border-radius: var(--rayon-carte);
  border: 1px dashed var(--couleur-bordure);
}

.pagination {
  display: flex;
  justify-content: center;
  gap: var(--espace-xs);
  margin-top: var(--espace-xl);
}

.page-btn {
  width: 36px;
  height: 36px;
  border: 1.5px solid var(--couleur-bordure);
  border-radius: 4px;
  background: white;
  cursor: pointer;
  font-size: 0.9rem;
  transition: all 0.2s;
}

.page-btn:hover:not(:disabled) {
  border-color: var(--couleur-primaire);
  color: var(--couleur-primaire);
}

.page-btn.actif {
  background: var(--couleur-bouton);
  border-color: var(--couleur-bouton);
  color: white;
}

.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

@media (max-width: 900px) {
  .recherche-layout {
    grid-template-columns: 1fr;
  }

  .sidebar-filtres {
    position: static;
  }
}
</style>
