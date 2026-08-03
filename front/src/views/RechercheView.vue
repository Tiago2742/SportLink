<script setup lang="ts">
import { ref, onMounted, watch, computed, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { chargerMatchs, demanderRejoindreMatch } from '@/services/api'
import CarteMatch from '@/components/matchs/CarteMatch.vue'
import { utilisateurEstInscrit } from '@/composables/useMatchCamps'
import SelecteurFiltresSport from '@/components/form/SelecteurFiltresSport.vue'
import { Search } from 'lucide-vue-next'
import L from 'leaflet'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const matchs = ref<any[]>([])
const chargement = ref(true)
const erreur = ref('')

const mapConteneur = ref<HTMLElement | null>(null)
let carteRecherche: L.Map | null = null
let grpMarqueurs: L.LayerGroup | null = null

const filtres = ref({
  sportId:  route.query.sportId  ? Number(route.query.sportId)  : '' as number | '',
  niveauId: route.query.niveauId ? Number(route.query.niveauId) : '' as number | '',
  lieu: (route.query.localisation as string) || '',
})

const recherche = ref((route.query.q as string) || '')
const triParDate = ref('asc')

const pageCourante = ref(1)
const parPage = 6
const demandesEnvoyees = ref<number[]>([])

const sportsDeclares = computed(() =>
  (auth.utilisateur?.niveaux ?? []).map((un: any) => un.sport).filter(Boolean),
)

const chipsRapides = computed(() => sportsDeclares.value.slice(0, 5))

onMounted(charger)

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
    const params: Record<string, string> = { statut: 'disponible' }
    if (filtres.value.sportId  !== '') params.sportId  = String(filtres.value.sportId)
    if (filtres.value.niveauId !== '') params.niveauId = String(filtres.value.niveauId)
    if (filtres.value.lieu)            params.lieu     = filtres.value.lieu
    matchs.value = await chargerMatchs(auth.token!, params)
    pageCourante.value = 1
  } catch {
    erreur.value = 'Impossible de charger les matchs.'
  } finally {
    chargement.value = false
  }
}

function appliquerFiltreRapide(sportId: number) {
  filtres.value.sportId = filtres.value.sportId === sportId ? '' : sportId
}

function filtreRapideAujourdhui() {
  const auj = new Date().toISOString().split('T')[0]
  filtres.value.lieu = filtres.value.lieu === auj ? '' : auj
}

const matchsFiltres = computed(() => {
  let liste = matchs.value
  if (auth.utilisateur?.type === 'joueur') {
    liste = liste.filter((m) => m.sport?.type !== 'collectif')
  }
  if (recherche.value) {
    const q = recherche.value.toLowerCase()
    liste = liste.filter(
      (m) =>
        m.sport?.nom?.toLowerCase().includes(q) ||
        m.lieu?.toLowerCase().includes(q) ||
        m.niveauRequis?.libelle?.toLowerCase().includes(q),
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

const matchsGeocodes = computed(() =>
  matchsTries.value.filter((m: any) => m.latitude != null && m.longitude != null),
)

const aCarteActive = computed(() => matchsGeocodes.value.length > 0)

const totalPages = computed(() => Math.ceil(matchsTries.value.length / parPage))

const matchsPage = computed(() =>
  matchsTries.value.slice((pageCourante.value - 1) * parPage, pageCourante.value * parPage),
)

function initCarteRecherche() {
  if (!mapConteneur.value || carteRecherche) return
  carteRecherche = L.map(mapConteneur.value, { scrollWheelZoom: false })
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
  }).addTo(carteRecherche)
  grpMarqueurs = L.layerGroup().addTo(carteRecherche)
  mettreAJourMarqueurs(matchsGeocodes.value)
}

function mettreAJourMarqueurs(geocodes: any[]) {
  if (!carteRecherche || !grpMarqueurs) return
  grpMarqueurs.clearLayers()
  if (geocodes.length === 0) return
  geocodes.forEach((m: any) => {
    L.marker([m.latitude, m.longitude])
      .addTo(grpMarqueurs!)
      .bindPopup(
        `<strong>${m.sport?.nom ?? 'Match'}</strong><br>${m.lieu ?? ''}<br>` +
        `<a href="/matchs/${m.id}" style="color:#4caf50;font-weight:600;font-size:0.85em">Voir le match →</a>`,
      )
  })
  const bounds = L.latLngBounds(geocodes.map((m: any) => [m.latitude, m.longitude] as L.LatLngTuple))
  carteRecherche.fitBounds(bounds, { padding: [30, 30] })
}

watch(matchsGeocodes, (geocodes) => {
  if (geocodes.length === 0) return
  if (!carteRecherche) {
    initCarteRecherche()
  } else {
    mettreAJourMarqueurs(geocodes)
  }
}, { flush: 'post' })

onUnmounted(() => {
  carteRecherche?.remove()
  carteRecherche = null
  grpMarqueurs = null
})

async function rejoindreMatch(matchId: number) {
  if (!auth.utilisateur) return
  const m = matchs.value.find((x) => x.id === matchId)
  if (m && utilisateurEstInscrit(m, auth.utilisateur.id)) return
  if (demandesEnvoyees.value.includes(matchId)) return
  try {
    if (m?.sport?.type === 'individuel') {
      await demanderRejoindreMatch(auth.token!, matchId)
      demandesEnvoyees.value = [...demandesEnvoyees.value, matchId]
    } else {
      router.push(`/matchs/${matchId}`)
    }
  } catch (e: any) {
    alert(e.message || 'Impossible d\'envoyer la demande.')
  }
}
</script>

<template>
  <div class="page-recherche">
    <div class="conteneur page-corps">

      <!-- ── En-tête ── -->
      <div class="page-entete">
        <span class="page-eyebrow">Explorer</span>
        <h1 class="page-titre">Trouver un match</h1>
        <p class="sous-titre">Parcourez les matchs disponibles et rejoignez une partie</p>
      </div>

      <div class="recherche-layout">
      <!-- Sidebar filtres -->
      <aside class="sidebar-filtres carte">
        <h3 class="sidebar-titre">Filtres</h3>
        <p class="sidebar-sous-titre">Filtrer par sport, niveau, localisation</p>

        <div class="filtre-section">
          <label class="filtre-label">Sport &amp; Niveau</label>
          <SelecteurFiltresSport
            :sport="filtres.sportId"
            :niveau="filtres.niveauId"
            :sports="sportsDeclares"
            @update:sport="(v) => { filtres.sportId = v; filtres.niveauId = '' }"
            @update:niveau="(v) => filtres.niveauId = v"
          />
        </div>

        <div class="filtre-section">
          <label class="filtre-label">Localisation</label>
          <input v-model="filtres.lieu" type="text" class="champ" placeholder="Ville ou terrain" />
        </div>

        <button
          class="btn btn-secondaire btn-pleine-largeur"
          @click="filtres = { sportId: '', niveauId: '', lieu: '' }"
        >
          Réinitialiser
        </button>

      </aside>

      <!-- Contenu principal -->
      <main class="recherche-contenu">
        <!-- Barre de recherche + chips -->
        <div class="recherche-barre">
          <div class="recherche-input-wrapper">
            <span class="recherche-icone" aria-hidden="true"><Search :size="18" /></span>
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
            :class="{ actif: filtres.sportId === sport.id }"
            @click="appliquerFiltreRapide(sport.id)"
          >
            {{ sport.nom }}
          </button>
        </div>

        <!-- En-tête résultats -->
        <div class="resultats-entete">
          <h2 class="section-titre">
            Matchs disponibles
            <span class="resultats-count">({{ matchsFiltres.length }} résultats)</span>
          </h2>
          <select v-model="triParDate" class="champ champ-tri">
            <option value="asc">Trier par date (plus ancien)</option>
            <option value="desc">Trier par date (plus récent)</option>
          </select>
        </div>

        <!-- Carte des matchs géolocalisés -->
        <section v-if="aCarteActive" class="section-carte-matchs">
          <div ref="mapConteneur" class="carte-leaflet"></div>
        </section>

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
            :demande-envoyee="demandesEnvoyees.includes(match.id)"
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
  </div>
</template>

<style scoped>
/* ══════════════════════════════════════
   FOND PLEIN-LARGEUR
══════════════════════════════════════ */
.page-recherche {
  background: #f5f9f5;
  min-height: 100vh;
  position: relative;
  overflow: hidden;
}

.page-recherche::before {
  content: '';
  position: absolute;
  top: -80px; right: -80px;
  width: 380px; height: 380px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(154, 230, 0, 0.09) 0%, transparent 60%);
  pointer-events: none;
}

.page-recherche::after {
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
  display: flex;
  flex-direction: column;
  gap: var(--espace-l);
}

/* ══════════════════════════════════════
   EN-TÊTE
══════════════════════════════════════ */
.page-entete {
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
   CARTE (override scoped)
══════════════════════════════════════ */
.carte {
  border: 1.5px solid #dde8dd;
  box-shadow: none;
  position: relative;
  overflow: hidden;
}

.carte::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: var(--degrade-primaire);
  z-index: 1;
}

/* ══════════════════════════════════════
   ANIMATIONS
══════════════════════════════════════ */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(14px); }
  to   { opacity: 1; transform: translateY(0); }
}

.sidebar-filtres {
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.08s both;
}

.recherche-contenu {
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.14s both;
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
  display: flex;
  color: var(--couleur-texte-discret);
  pointer-events: none;
}

.recherche-input {
  padding-left: 2.2rem;
}

.recherche-input:focus {
  border-color: var(--couleur-primaire);
  box-shadow: 0 0 0 3px rgba(154, 230, 0, 0.28), 0 0 0 1px var(--couleur-primaire);
}

.sidebar-filtres .champ:focus {
  border-color: var(--couleur-primaire);
  box-shadow: 0 0 0 3px rgba(154, 230, 0, 0.28), 0 0 0 1px var(--couleur-primaire);
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

.chip:hover {
  border-color: var(--couleur-primaire);
  color: var(--couleur-primaire);
  background: var(--couleur-primaire-tres-claire);
}

.chip.actif {
  border-color: var(--couleur-primaire);
  color: var(--couleur-primaire);
  background: var(--couleur-accent-fond);
  font-weight: 600;
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
  background: var(--couleur-primaire);
  border-color: var(--couleur-primaire);
  color: white;
}

.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.section-carte-matchs {
  margin-bottom: var(--espace-l);
}

.carte-leaflet {
  height: 320px;
  border-radius: var(--rayon-carte);
  overflow: hidden;
  border: 1.5px solid #dde8dd;
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
