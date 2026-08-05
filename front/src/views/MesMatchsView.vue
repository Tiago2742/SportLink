<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { chargerMesMatchs, chargerMesMatchsEquipes, chargerMesStats } from '@/services/api'
import type { MesStats, Match } from '@/services/api'
import BadgeStatut from '@/components/commun/BadgeStatut.vue'
import BadgeCompteur from '@/components/ui/BadgeCompteur.vue'
import IconeLigne from '@/components/ui/IconeLigne.vue'
import { MapPin, Trophy, Users } from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()

const matchs = ref<Match[]>([])
const matchsEquipes = ref<Match[]>([])
const mesStats = ref<MesStats | null>(null)
const chargement = ref(true)
const erreur = ref('')
const filtreStatut = ref('')
const ongletActif = ref<'individuels' | 'equipes'>('individuels')

onMounted(charger)

async function charger() {
  chargement.value = true
  erreur.value = ''
  const userId = auth.utilisateur?.id
  try {
    if (auth.utilisateur?.type === 'joueur') {
      const [individuels, equipes, stats] = await Promise.all([
        userId ? chargerMesMatchs(auth.token!) : Promise.resolve([]),
        userId ? chargerMesMatchsEquipes(auth.token!) : Promise.resolve([]),
        chargerMesStats(auth.token!),
      ])
      matchs.value = individuels
      matchsEquipes.value = equipes
      mesStats.value = stats
    } else {
      const [liste, stats] = await Promise.all([
        userId ? chargerMesMatchs(auth.token!) : Promise.resolve([]),
        chargerMesStats(auth.token!),
      ])
      matchs.value = liste
      mesStats.value = stats
    }
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

function nomsCamps(match: Match): string {
  return match.camps
    .flatMap((c) => c.equipe ? [c.equipe.nom] : [])
    .join(' vs ')
}

const sourceActive = computed(() =>
  auth.utilisateur?.type === 'joueur' && ongletActif.value === 'equipes'
    ? matchsEquipes.value
    : matchs.value,
)

const mesMatchs = computed(() => sourceActive.value)

const matchsFiltres = computed(() => {
  const liste = mesMatchs.value

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

      <!-- ── Stats ── -->
      <div v-if="mesStats" class="stats-grille" :class="{ 'stats-grille--deux': mesStats.equipes !== null }">
        <div class="stats-bloc">
          <p class="stats-bloc-titre">{{ mesStats.equipes !== null ? 'Matchs individuels' : 'Bilan' }}</p>
          <div class="stats-compteurs">
            <div class="stat-item">
              <span class="stat-val">{{ mesStats.individuels.joues }}</span>
              <span class="stat-lbl">Joués</span>
            </div>
            <div class="stat-item stat-item--victoire">
              <span class="stat-val">{{ mesStats.individuels.victoires }}</span>
              <span class="stat-lbl">Victoires</span>
            </div>
            <div class="stat-item stat-item--nul">
              <span class="stat-val">{{ mesStats.individuels.nuls }}</span>
              <span class="stat-lbl">Nuls</span>
            </div>
            <div class="stat-item stat-item--defaite">
              <span class="stat-val">{{ mesStats.individuels.defaites }}</span>
              <span class="stat-lbl">Défaites</span>
            </div>
            <div class="stat-item stat-item--ratio">
              <span class="stat-val">{{ mesStats.individuels.joues > 0 ? mesStats.individuels.ratio + ' %' : '—' }}</span>
              <span class="stat-lbl">Victoires</span>
            </div>
          </div>
        </div>

        <div v-if="mesStats.equipes !== null" class="stats-bloc">
          <p class="stats-bloc-titre">Matchs d'équipe</p>
          <div class="stats-compteurs">
            <div class="stat-item">
              <span class="stat-val">{{ mesStats.equipes.joues }}</span>
              <span class="stat-lbl">Joués</span>
            </div>
            <div class="stat-item stat-item--victoire">
              <span class="stat-val">{{ mesStats.equipes.victoires }}</span>
              <span class="stat-lbl">Victoires</span>
            </div>
            <div class="stat-item stat-item--nul">
              <span class="stat-val">{{ mesStats.equipes.nuls }}</span>
              <span class="stat-lbl">Nuls</span>
            </div>
            <div class="stat-item stat-item--defaite">
              <span class="stat-val">{{ mesStats.equipes.defaites }}</span>
              <span class="stat-lbl">Défaites</span>
            </div>
            <div class="stat-item stat-item--ratio">
              <span class="stat-val">{{ mesStats.equipes.joues > 0 ? mesStats.equipes.ratio + ' %' : '—' }}</span>
              <span class="stat-lbl">Victoires</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Onglets joueur ── -->
      <nav v-if="auth.utilisateur?.type === 'joueur'" class="onglets" aria-label="Basculer entre les types de matchs">
        <button
          class="onglet"
          :class="{ actif: ongletActif === 'individuels' }"
          @click="ongletActif = 'individuels'; filtreStatut = ''"
        >
          <Trophy :size="15" stroke-width="2.25" aria-hidden="true" />
          Mes matchs
          <span class="onglet-count">{{ matchs.length }}</span>
        </button>
        <button
          class="onglet"
          :class="{ actif: ongletActif === 'equipes' }"
          @click="ongletActif = 'equipes'; filtreStatut = ''"
        >
          <Users :size="15" stroke-width="2.25" aria-hidden="true" />
          Matchs d'équipe
          <span class="onglet-count">{{ matchsEquipes.length }}</span>
        </button>
      </nav>

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
                    <span v-if="ongletActif === 'equipes' && nomsCamps(match)" class="ligne-equipes">
                      {{ nomsCamps(match) }}
                    </span>
                    <span>{{ formaterDate(match.dateMatch) }}</span>
                    <IconeLigne v-if="match.lieu" :icone="MapPin" :taille="13" discret class="ligne-lieu">
                      {{ match.lieu }}
                    </IconeLigne>
                  </div>
                </div>
                <div class="ligne-droite">
                  <BadgeCompteur
                    v-if="match.createur?.id === auth.utilisateur?.id"
                    :nombre="match.demandesEnAttenteCount ?? 0"
                  />
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
                    <span v-if="ongletActif === 'equipes' && nomsCamps(match)" class="ligne-equipes">
                      {{ nomsCamps(match) }}
                    </span>
                    <span>{{ formaterDate(match.dateMatch) }}</span>
                    <IconeLigne v-if="match.lieu" :icone="MapPin" :taille="13" discret class="ligne-lieu">
                      {{ match.lieu }}
                    </IconeLigne>
                  </div>
                </div>
                <div class="ligne-droite">
                  <BadgeCompteur
                    v-if="match.createur?.id === auth.utilisateur?.id"
                    :nombre="match.demandesEnAttenteCount ?? 0"
                  />
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
                <BadgeCompteur
                  v-if="match.createur?.id === auth.utilisateur?.id"
                  :nombre="match.demandesEnAttenteCount ?? 0"
                />
                <BadgeStatut :statut="match.statut" />
                <span class="ligne-fleche" aria-hidden="true">›</span>
              </div>
            </div>
          </div>
        </section>

        <!-- État vide global -->
        <div v-if="mesMatchs.length === 0" class="etat-vide">
          <div class="vide-icone-cercle" aria-hidden="true">
            <component :is="ongletActif === 'equipes' ? Users : Trophy" :size="28" stroke-width="1.5" />
          </div>
          <template v-if="ongletActif === 'equipes'">
            <p class="vide-titre">Aucun match d'équipe</p>
            <p class="vide-desc">Rejoignez une équipe pour voir ses matchs ici.</p>
            <RouterLink to="/mes-equipes" class="btn btn-secondaire">Mes équipes</RouterLink>
          </template>
          <template v-else>
            <p class="vide-titre">Aucun match pour le moment</p>
            <p class="vide-desc">Participez à votre premier match ou créez-en un.</p>
            <RouterLink to="/rechercher" class="btn btn-secondaire">Trouver un match</RouterLink>
          </template>
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
   ONGLETS JOUEUR
══════════════════════════════════════ */
.onglets {
  display: flex;
  gap: 4px;
  background: #ffffff;
  border: 1.5px solid #dde8dd;
  border-radius: 10px;
  padding: 4px;
  animation: fadeUp 0.48s cubic-bezier(0.22, 1, 0.36, 1) 0.04s both;
}

.onglet {
  flex: 1;
  background: none;
  border: none;
  border-radius: 6px;
  padding: 0.6rem 1rem;
  font-size: 0.88rem;
  color: var(--couleur-texte-discret);
  cursor: pointer;
  font-weight: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  transition: background 0.18s ease, color 0.18s ease;
}

.onglet:hover {
  background: var(--couleur-primaire-tres-claire);
  color: var(--couleur-primaire);
}

.onglet.actif {
  background: var(--couleur-primaire);
  color: white;
  font-weight: 700;
}

.onglet-count {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 0.1rem 0.45rem;
  border-radius: var(--rayon-badge);
  background: var(--couleur-accent-fond);
  color: var(--couleur-accent-texte);
  line-height: 1.5;
}

.onglet.actif .onglet-count {
  background: rgba(255, 255, 255, 0.22);
  color: white;
}

.ligne-equipes {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--couleur-primaire);
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
   STATS
══════════════════════════════════════ */
.stats-grille {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--espace-m);
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.06s both;
}

.stats-grille--deux {
  grid-template-columns: 1fr 1fr;
}

.stats-bloc {
  background: white;
  border: 1.5px solid #dde8dd;
  border-radius: var(--rayon-carte);
  padding: var(--espace-l);
  position: relative;
  overflow: hidden;
}

.stats-bloc::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: var(--degrade-primaire);
}

.stats-bloc-titre {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--couleur-texte-discret);
  margin-bottom: var(--espace-m);
}

.stats-compteurs {
  display: flex;
  gap: var(--espace-l);
  flex-wrap: wrap;
  align-items: flex-end;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.15rem;
  min-width: 44px;
}

.stat-val {
  font-size: 1.7rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  line-height: 1;
  color: var(--couleur-titre);
}

.stat-lbl {
  font-size: 0.68rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--couleur-texte-discret);
}

.stat-item--victoire .stat-val { color: #2e7d32; }
.stat-item--nul      .stat-val { color: #795548; }
.stat-item--defaite  .stat-val { color: #c0392b; }

.stat-item--ratio {
  margin-left: auto;
  align-items: flex-end;
}

.stat-item--ratio .stat-val {
  font-size: 1.35rem;
  color: var(--couleur-primaire-foncee, #388e3c);
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
