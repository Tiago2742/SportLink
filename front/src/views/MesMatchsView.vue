<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { chargerMesMatchs } from '@/services/api'
import BadgeStatut from '@/components/commun/BadgeStatut.vue'
import IconeLigne from '@/components/ui/IconeLigne.vue'
import { MapPin, Trophy } from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()

const matchs = ref<any[]>([])
const chargement = ref(true)
const erreur = ref('')
const filtreStatut = ref('')

onMounted(charger)

async function charger() {
  chargement.value = true
  erreur.value = ''
  const userId = auth.utilisateur?.id
  try {
    matchs.value = userId ? await chargerMesMatchs(auth.token!) : []
  } catch {
    erreur.value = 'Impossible de charger vos matchs.'
  } finally {
    chargement.value = false
  }
}

function estPasse(dateMatch: string) {
  return new Date(dateMatch) < new Date()
}

function estTermine(match: { statut: string; dateMatch: string }) {
  return match.statut === 'termine' || estPasse(match.dateMatch)
}

const mesMatchs = computed(() => matchs.value)

const matchsFiltres = computed(() => {
  let liste = mesMatchs.value

  if (filtreStatut.value === 'passe') {
    return liste.filter((m) => estPasse(m.dateMatch))
  }
  if (filtreStatut.value === 'avenir') {
    return liste.filter((m) => !estPasse(m.dateMatch))
  }
  if (filtreStatut.value === 'termine') {
    return liste.filter((m) => estTermine(m))
  }
  if (filtreStatut.value) {
    return liste.filter((m) => m.statut === filtreStatut.value)
  }

  return liste
})

const matchsAvenir = computed(() =>
  matchsFiltres.value.filter((m) => !estPasse(m.dateMatch)),
)

const matchsPasses = computed(() =>
  matchsFiltres.value.filter((m) => estPasse(m.dateMatch)),
)

const afficherSections = computed(() => filtreStatut.value === '')

const listeUnique = computed(() => (afficherSections.value ? [] : matchsFiltres.value))

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
  <div class="page-mes-matchs">
    <div class="conteneur page-corps">

      <!-- ── En-tête ── -->
      <div class="entete-page">
        <div>
          <span class="page-eyebrow">{{ auth.utilisateur?.type === 'club' ? 'Club' : 'Joueur' }}</span>
          <h1 class="page-titre">Mes matchs</h1>
          <p class="sous-titre">Matchs que vous organisez ou auxquels vous participez</p>
        </div>
        <RouterLink to="/creer-match" class="btn btn-primaire">+ Créer un match</RouterLink>
      </div>

      <!-- ── Filtres (pill container) ── -->
      <nav class="filtres-statut" aria-label="Filtrer les matchs">
        <button class="chip" :class="{ actif: filtreStatut === '' }" @click="filtreStatut = ''">
          Tous ({{ mesMatchs.length }})
        </button>
        <button class="chip" :class="{ actif: filtreStatut === 'avenir' }" @click="filtreStatut = 'avenir'">
          À venir
        </button>
        <button class="chip" :class="{ actif: filtreStatut === 'passe' }" @click="filtreStatut = 'passe'">
          Passés
        </button>
        <button
          class="chip"
          :class="{ actif: filtreStatut === 'en_attente' }"
          @click="filtreStatut = 'en_attente'"
        >
          En attente
        </button>
        <button
          class="chip"
          :class="{ actif: filtreStatut === 'confirme' }"
          @click="filtreStatut = 'confirme'"
        >
          Confirmés
        </button>
        <button
          class="chip"
          :class="{ actif: filtreStatut === 'termine' }"
          @click="filtreStatut = 'termine'"
        >
          Terminés
        </button>
      </nav>

      <div v-if="chargement" class="chargement">Chargement...</div>
      <div v-else-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>

      <template v-else>

        <template v-if="afficherSections">

          <!-- À venir -->
          <section v-if="matchsAvenir.length > 0" class="section-matchs">
            <p class="section-eyebrow">À venir · {{ matchsAvenir.length }}</p>
            <div class="carte liste-matchs">
              <div
                v-for="match in matchsAvenir"
                :key="match.id"
                class="ligne-match"
                @click="router.push(`/matchs/${match.id}`)"
              >
                <div class="ligne-gauche">
                  <div class="ligne-sport-icone" aria-hidden="true">
                    <Trophy :size="18" stroke-width="2" />
                  </div>
                  <div class="ligne-info">
                    <strong>{{ match.sport?.nom ?? 'Sport' }}</strong>
                    <span>{{ formaterDate(match.dateMatch) }}</span>
                    <IconeLigne v-if="match.lieu" :icone="MapPin" :taille="13" discret class="ligne-lieu">
                      {{ match.lieu }}
                    </IconeLigne>
                  </div>
                </div>
                <div class="ligne-droite">
                  <BadgeStatut :statut="match.statut" />
                  <span class="ligne-fleche" aria-hidden="true">›</span>
                </div>
              </div>
            </div>
          </section>

          <!-- Passés -->
          <section v-if="matchsPasses.length > 0" class="section-matchs section-passe">
            <p class="section-eyebrow section-eyebrow-dim">Passés · {{ matchsPasses.length }}</p>
            <div class="carte liste-matchs liste-passee">
              <div
                v-for="match in matchsPasses"
                :key="match.id"
                class="ligne-match"
                @click="router.push(`/matchs/${match.id}`)"
              >
                <div class="ligne-gauche">
                  <div class="ligne-sport-icone icone-passe" aria-hidden="true">
                    <Trophy :size="18" stroke-width="2" />
                  </div>
                  <div class="ligne-info">
                    <strong>{{ match.sport?.nom ?? 'Sport' }}</strong>
                    <span>{{ formaterDate(match.dateMatch) }}</span>
                    <IconeLigne v-if="match.lieu" :icone="MapPin" :taille="13" discret class="ligne-lieu">
                      {{ match.lieu }}
                    </IconeLigne>
                  </div>
                </div>
                <div class="ligne-droite">
                  <BadgeStatut :statut="match.statut" />
                  <span class="ligne-fleche" aria-hidden="true">›</span>
                </div>
              </div>
            </div>
          </section>

        </template>

        <!-- Filtre actif — liste unique -->
        <section v-else-if="listeUnique.length > 0" class="section-matchs">
          <p class="section-eyebrow">Résultats · {{ listeUnique.length }}</p>
          <div
            class="carte liste-matchs"
            :class="{ 'liste-passee': filtreStatut === 'passe' || filtreStatut === 'termine' }"
          >
            <div
              v-for="match in listeUnique"
              :key="match.id"
              class="ligne-match"
              @click="router.push(`/matchs/${match.id}`)"
            >
              <div class="ligne-gauche">
                <div
                  class="ligne-sport-icone"
                  :class="{ 'icone-passe': estPasse(match.dateMatch) }"
                  aria-hidden="true"
                >
                  <Trophy :size="18" stroke-width="2" />
                </div>
                <div class="ligne-info">
                  <strong>{{ match.sport?.nom ?? 'Sport' }}</strong>
                  <span>{{ formaterDate(match.dateMatch) }}</span>
                  <IconeLigne v-if="match.lieu" :icone="MapPin" :taille="13" discret class="ligne-lieu">
                    {{ match.lieu }}
                  </IconeLigne>
                </div>
              </div>
              <div class="ligne-droite">
                <BadgeStatut :statut="match.statut" />
                <span class="ligne-fleche" aria-hidden="true">›</span>
              </div>
            </div>
          </div>
        </section>

        <!-- État vide global -->
        <div v-if="mesMatchs.length === 0" class="etat-vide">
          <div class="vide-icone-cercle" aria-hidden="true">
            <Trophy :size="28" stroke-width="1.5" />
          </div>
          <p class="vide-titre">Aucun match pour le moment</p>
          <p class="vide-desc">Participez à votre premier match ou créez-en un.</p>
          <RouterLink to="/rechercher" class="btn btn-secondaire">Trouver un match</RouterLink>
        </div>

        <!-- Filtre sans résultat -->
        <div v-else-if="matchsFiltres.length === 0" class="etat-vide">
          <p class="vide-titre">Aucun match pour ce filtre</p>
          <button type="button" class="btn btn-secondaire" @click="filtreStatut = ''">
            Voir tous mes matchs
          </button>
        </div>

      </template>
    </div>
  </div>
</template>

<style scoped>
/* ══════════════════════════════════════
   FOND PLEIN-LARGEUR
══════════════════════════════════════ */
.page-mes-matchs {
  background: #f5f9f5;
  min-height: 100vh;
  position: relative;
  overflow: hidden;
}

.page-mes-matchs::before {
  content: '';
  position: absolute;
  top: -80px; right: -80px;
  width: 380px; height: 380px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(154, 230, 0, 0.09) 0%, transparent 60%);
  pointer-events: none;
}

.page-mes-matchs::after {
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
.entete-page {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--espace-m);
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
   FILTRES (pill container)
══════════════════════════════════════ */
.filtres-statut {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  background: #ffffff;
  border: 1.5px solid #dde8dd;
  border-radius: 10px;
  padding: 4px;
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.08s both;
}

.chip {
  background: none;
  border: none;
  border-radius: 6px;
  padding: 0.5rem 0.95rem;
  font-size: 0.85rem;
  color: var(--couleur-texte-discret);
  cursor: pointer;
  font-weight: 500;
  transition: background 0.18s ease, color 0.18s ease;
  white-space: nowrap;
}

.chip:hover {
  background: var(--couleur-primaire-tres-claire);
  color: var(--couleur-primaire);
}

.chip.actif {
  background: var(--couleur-accent-fond);
  color: var(--couleur-primaire);
  font-weight: 700;
}

/* ══════════════════════════════════════
   SECTIONS
══════════════════════════════════════ */
.section-matchs {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.12s both;
}

.section-passe {
  opacity: 0.88;
}

.section-eyebrow {
  font-size: 0.73rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--couleur-texte-discret);
}

.section-eyebrow-dim {
  opacity: 0.7;
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

.liste-matchs {
  padding: 0;
}

.liste-passee {
  opacity: 0.88;
}

/* ══════════════════════════════════════
   LIGNES MATCH
══════════════════════════════════════ */
.ligne-match {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--espace-m) var(--espace-l);
  border-bottom: 1px solid var(--couleur-bordure);
  cursor: pointer;
  transition: background 0.15s;
}

.ligne-match:first-child {
  margin-top: 3px;
}

.ligne-match:last-child {
  border-bottom: none;
}

.ligne-match:hover {
  background: var(--couleur-primaire-tres-claire);
}

.ligne-gauche {
  display: flex;
  align-items: center;
  gap: var(--espace-m);
}

.ligne-sport-icone {
  width: 38px;
  height: 38px;
  background: var(--couleur-primaire-tres-claire);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: var(--couleur-primaire);
}

.ligne-sport-icone.icone-passe {
  background: #f0f0f0;
  color: var(--couleur-texte-discret);
}

.ligne-info {
  display: flex;
  flex-direction: column;
  gap: 0.12rem;
}

.ligne-info strong {
  font-size: 0.95rem;
  color: var(--couleur-titre);
  font-weight: 600;
}

.ligne-info span {
  font-size: 0.82rem;
  color: var(--couleur-texte-discret);
}

.ligne-lieu {
  font-size: 0.78rem;
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

/* ══════════════════════════════════════
   ÉTAT VIDE
══════════════════════════════════════ */
.etat-vide {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--espace-m);
  text-align: center;
  padding: var(--espace-xxl) var(--espace-xl);
  background: white;
  border: 2px dashed #c0d4c0;
  border-radius: var(--rayon-carte);
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.1s both;
}

.vide-icone-cercle {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--couleur-primaire-tres-claire);
  color: var(--couleur-primaire);
  display: flex;
  align-items: center;
  justify-content: center;
}

.vide-titre {
  font-size: 1rem;
  font-weight: 700;
  color: var(--couleur-titre);
}

.vide-desc {
  font-size: 0.88rem;
  color: var(--couleur-texte-discret);
  max-width: 280px;
}

/* ══════════════════════════════════════
   ANIMATIONS
══════════════════════════════════════ */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(14px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ══════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════ */
@media (max-width: 640px) {
  .entete-page {
    flex-direction: column;
    gap: var(--espace-s);
  }

  .ligne-match {
    padding: var(--espace-m);
  }
}
</style>
