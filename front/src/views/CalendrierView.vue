<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { chargerMesMatchs, chargerMesMatchsEquipes, type Match } from '@/services/api'
// @ts-ignore
import VueCal from 'vue-cal'
// @ts-ignore
import fr from 'vue-cal/dist/i18n/fr.es.js'
import { CalendarRange, ListTodo } from 'lucide-vue-next'

const auth = useAuthStore()

const matchs = ref<Match[]>([])
const matchsEquipes = ref<Match[]>([])
const chargement = ref(true)
const erreur = ref('')

onMounted(async () => {
  try {
    if (auth.utilisateur?.type === 'joueur') {
      const [individuels, equipes] = await Promise.all([
        auth.token ? chargerMesMatchs(auth.token) : Promise.resolve([]),
        auth.token ? chargerMesMatchsEquipes(auth.token) : Promise.resolve([]),
      ])
      matchs.value = individuels
      matchsEquipes.value = equipes
    } else {
      matchs.value = auth.token ? await chargerMesMatchs(auth.token) : []
    }
  } catch {
    erreur.value = 'Impossible de charger vos matchs.'
  } finally {
    chargement.value = false
  }
})

function formatHeure(date: Date): string {
  if (!(date instanceof Date) || isNaN(date.getTime())) return ''
  return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

function estAujourdhui(date: Date): boolean {
  const auj = new Date()
  return date.getFullYear() === auj.getFullYear()
    && date.getMonth() === auj.getMonth()
    && date.getDate() === auj.getDate()
}

function nomEquipeChef(match: Match): string {
  const camp = match.camps.find((c) => c.equipe?.nom)
  return camp?.equipe?.nom ?? match.sport?.nom ?? 'Match'
}

function toEvent(match: Match, source: 'individuel' | 'equipe') {
  const start = new Date(match.dateMatch)
  const end = new Date(start.getTime() + 90 * 60 * 1000)
  return {
    start,
    end,
    title: source === 'equipe' ? nomEquipeChef(match) : (match.sport?.nom ?? 'Match'),
    class: `ev-${match.statut}`,
    matchId: match.id,
    statut: match.statut,
    lieu: match.lieu ?? null,
    source,
  }
}

const events = computed(() => [
  ...matchs.value.map((m) => toEvent(m, 'individuel')),
  ...matchsEquipes.value.map((m) => toEvent(m, 'equipe')),
])

const totalMatchs = computed(() => matchs.value.length + matchsEquipes.value.length)
</script>

<template>
  <div class="page-calendrier">
    <div class="conteneur page-corps">

      <!-- ── En-tête ── -->
      <div class="entete-page">
        <div>
          <span class="page-eyebrow">
            <CalendarRange :size="12" stroke-width="2.5" aria-hidden="true" />
            Planning mensuel
          </span>
          <h1 class="page-titre">Calendrier</h1>
          <p class="sous-titre">
            <template v-if="!chargement && !erreur">
              {{ totalMatchs }} match{{ totalMatchs !== 1 ? 's' : '' }} au total
            </template>
            <template v-else>Vue mensuelle de vos matchs</template>
          </p>
        </div>
        <RouterLink to="/mes-matchs" class="btn btn-secondaire vue-liste-btn">
          <ListTodo :size="16" stroke-width="2.25" aria-hidden="true" />
          Vue liste
        </RouterLink>
      </div>

      <!-- ── Chargement / erreur ── -->
      <div v-if="chargement" class="chargement">Chargement...</div>
      <div v-else-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>

      <template v-else>

        <!-- ── Calendrier ── -->
        <div class="calendrier-wrapper">
          <VueCal
            :locale="fr"
            default-view="month"
            :disable-views="['years', 'year', 'week', 'day']"
            :events="events"
            :time="false"
            hide-view-selector
            class="sportlink-cal"
          >
            <!--
              #cell-content reçoit { events } = les événements filtrés pour CE jour par vue-cal.
              On bypasse le système de clic de vue-cal et on utilise RouterLink directement.
            -->
            <template #cell-content="{ events: joursMatchs }">
              <!-- Numéro du jour injecté manuellement (le slot remplace le rendu par défaut de vue-cal) -->
              <div v-if="joursMatchs.length > 0" class="cal-cell-entete">
                <span
                  class="cal-date-num"
                  :class="{ 'cal-date-num--today': estAujourdhui(joursMatchs[0].start) }"
                >{{ joursMatchs[0].start.getDate() }}</span>
              </div>
              <RouterLink
                v-for="ev in joursMatchs"
                :key="ev.matchId"
                :to="`/matchs/${ev.matchId}`"
                class="cal-chip"
                :class="`chip-${ev.statut}`"
                :title="`${ev.title}${ev.lieu ? ' · ' + ev.lieu : ''}`"
              >
                <span v-if="ev.source === 'equipe'" class="chip-equipe-marker">Éq.</span>
                <span class="chip-sport">{{ ev.title }}</span>
                <span class="chip-heure">{{ formatHeure(ev.start) }}</span>
                <span v-if="ev.lieu" class="chip-lieu">{{ ev.lieu }}</span>
              </RouterLink>
            </template>
          </VueCal>
        </div>

        <!-- ── Légende ── -->
        <div class="legende" aria-label="Légende des statuts">
          <span class="legende-label">Statuts</span>
          <div class="legende-items">
            <div class="legende-item">
              <span class="legende-pastille pastille-attente" aria-hidden="true"></span>
              En attente
            </div>
            <div class="legende-item">
              <span class="legende-pastille pastille-confirme" aria-hidden="true"></span>
              Confirmé
            </div>
            <div class="legende-item">
              <span class="legende-pastille pastille-termine" aria-hidden="true"></span>
              Terminé
            </div>
            <div class="legende-item">
              <span class="legende-pastille pastille-annule" aria-hidden="true"></span>
              Annulé
            </div>
          </div>
        </div>

        <!-- ── État vide ── -->
        <div v-if="matchs.length === 0" class="etat-vide">
          <div class="vide-icone-cercle" aria-hidden="true">
            <CalendarRange :size="28" stroke-width="1.5" />
          </div>
          <p class="vide-titre">Aucun match prévu</p>
          <p class="vide-desc">Vos matchs apparaîtront ici une fois que vous en aurez créé ou rejoint.</p>
          <div class="vide-actions">
            <RouterLink to="/rechercher" class="btn btn-secondaire">Trouver un match</RouterLink>
            <RouterLink to="/creer-match" class="btn btn-primaire">Créer un match</RouterLink>
          </div>
        </div>

      </template>
    </div>
  </div>
</template>

<style scoped>
/* ══════════════════════════════════════
   FOND PAGE
══════════════════════════════════════ */
.page-calendrier {
  background: #f5f9f5;
  min-height: 100vh;
  position: relative;
  overflow: hidden;
}

.page-calendrier::before {
  content: '';
  position: absolute;
  top: -80px;
  right: -80px;
  width: 380px;
  height: 380px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(154, 230, 0, 0.09) 0%, transparent 60%);
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
  gap: 0.35rem;
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

.vue-liste-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
}

/* ══════════════════════════════════════
   WRAPPER CALENDRIER
══════════════════════════════════════ */
.calendrier-wrapper {
  background: white;
  border: 1.5px solid #dde8dd;
  border-radius: var(--rayon-carte);
  overflow: hidden;
  box-shadow: var(--ombre-carte);
  position: relative;
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.08s both;
}

.calendrier-wrapper::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: var(--degrade-primaire);
  z-index: 10;
  pointer-events: none;
}

/* ══════════════════════════════════════
   VUE-CAL OVERRIDES
══════════════════════════════════════ */
:deep(.sportlink-cal) {
  font-family: inherit;
  border: none;
  border-radius: 0;
  background: transparent;
}

/* — Header titre + nav — */
:deep(.sportlink-cal .vuecal__header) {
  background: white;
  padding-top: 3px;
}

:deep(.sportlink-cal .vuecal__title-bar) {
  background: transparent;
  padding: 0.6rem 1rem 0.45rem;
  gap: 0.65rem;
}

:deep(.sportlink-cal .vuecal__title) {
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--couleur-titre);
  letter-spacing: -0.02em;
  text-transform: capitalize;
  background: transparent;
  border: none;
  cursor: default;
}

:deep(.sportlink-cal .vuecal__title:hover) {
  background: transparent;
}

:deep(.sportlink-cal .vuecal__arrow) {
  color: var(--couleur-primaire);
  background: var(--couleur-primaire-tres-claire);
  border-radius: 8px;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(46, 125, 50, 0.22);
  transition:
    background 0.18s ease,
    color 0.18s ease,
    transform 0.18s cubic-bezier(0.22, 1, 0.36, 1),
    box-shadow 0.18s ease;
}

:deep(.sportlink-cal .vuecal__arrow:hover) {
  background: var(--couleur-primaire);
  color: white;
  transform: scale(1.07);
  box-shadow: var(--ombre-bouton);
  border-color: transparent;
}

/* — Jours de la semaine — */
:deep(.sportlink-cal .vuecal__weekdays-headings) {
  background: #f5f9f5;
  border-top: 1px solid var(--couleur-bordure);
  border-bottom: 1px solid var(--couleur-bordure);
  padding: 0;
}

:deep(.sportlink-cal .vuecal__weekdays-headings .vuecal__heading) {
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--couleur-texte-discret);
  padding: 0.5rem 0;
  text-align: center;
  height: auto;
  line-height: 1;
}

/* — Grille cellules — */
:deep(.sportlink-cal .vuecal__bg) {
  background: white;
}

:deep(.sportlink-cal .vuecal__cell) {
  border-color: var(--couleur-bordure);
  background: white;
  min-height: 90px;
  transition: background 0.15s;
  vertical-align: top;
}

:deep(.sportlink-cal .vuecal__cell:hover) {
  background: #fafff5;
}

:deep(.sportlink-cal .vuecal__cell--out-of-scope) {
  background: #fafafa;
}

:deep(.sportlink-cal .vuecal__cell--out-of-scope .vuecal__cell-date) {
  opacity: 0.3;
}

:deep(.sportlink-cal .vuecal__cell--today) {
  background: var(--couleur-primaire-tres-claire);
}

:deep(.sportlink-cal .vuecal__cell-date) {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--couleur-texte-discret);
  padding: 0.3rem 0.5rem 0.2rem;
  text-align: right;
  line-height: 1;
}

:deep(.sportlink-cal .vuecal__cell--today .vuecal__cell-date) {
  color: white;
  background: var(--couleur-primaire);
  border-radius: 50%;
  width: 22px;
  height: 22px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  margin: 0.25rem 0.4rem 0.25rem auto;
  font-weight: 800;
  font-size: 0.75rem;
}

:deep(.sportlink-cal .vuecal__cell--has-events .vuecal__cell-date) {
  color: var(--couleur-titre);
  font-weight: 700;
}

/* Zone contenu de cellule — contient nos chips */
:deep(.sportlink-cal .vuecal__cell-content) {
  display: flex;
  flex-direction: column;
  gap: 3px;
  padding: 0 3px 4px;
  align-items: stretch;
}

/* Masque le numéro du jour natif de vue-cal sur les cellules avec événements
   (remplacé par cal-date-num injecté dans le slot) */
:deep(.sportlink-cal .vuecal__cell--has-events .vuecal__cell-date) {
  display: none;
}

/* Masquer les éventuels badges count résiduels */
:deep(.sportlink-cal .vuecal__cell-events-count) {
  display: none;
}

/* ── Date injectée dans le slot cell-content ── */
.cal-cell-entete {
  display: flex;
  justify-content: flex-end;
  padding: 0.25rem 0.4rem 0.1rem;
}

.cal-date-num {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--couleur-titre);
  line-height: 1;
}

.cal-date-num--today {
  width: 22px;
  height: 22px;
  background: var(--couleur-primaire);
  color: white;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 0.75rem;
}

/* ══════════════════════════════════════
   CHIPS D'ÉVÉNEMENTS (slot #cell-content)
   RouterLink direct → navigation fiable
══════════════════════════════════════ */
.cal-chip {
  display: flex;
  flex-direction: column;
  padding: 5px 7px 4px;
  border-radius: 5px;
  text-decoration: none;
  cursor: pointer;
  gap: 1px;
  transition:
    transform 0.13s cubic-bezier(0.22, 1, 0.36, 1),
    opacity 0.13s ease,
    box-shadow 0.13s ease;
  overflow: hidden;
}

.cal-chip:hover {
  transform: translateY(-1px);
  opacity: 0.9;
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.14);
}

.cal-chip:active {
  transform: scale(0.98);
}

/* Couleurs solides par statut */
.chip-en_attente { background: #f57c00; }
.chip-confirme   { background: #2e7d32; }
.chip-termine    { background: #757575; opacity: 0.82; }
.chip-annule     { background: #c62828; opacity: 0.75; }

/* Texte blanc sur fond coloré */
.chip-sport {
  font-size: 0.78rem;
  font-weight: 700;
  line-height: 1.2;
  color: white;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.chip-heure {
  font-size: 0.67rem;
  font-weight: 500;
  line-height: 1.2;
  color: rgba(255, 255, 255, 0.82);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.chip-lieu {
  font-size: 0.63rem;
  line-height: 1.2;
  color: rgba(255, 255, 255, 0.68);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.chip-equipe-marker {
  font-size: 0.58rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.78);
  background: rgba(255, 255, 255, 0.18);
  border-radius: 2px;
  padding: 0 3px;
  align-self: flex-start;
  line-height: 1.4;
}

.chip-annule .chip-sport {
  text-decoration: line-through;
}

/* ══════════════════════════════════════
   LÉGENDE
══════════════════════════════════════ */
.legende {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--espace-m);
  padding: 0.8rem var(--espace-l);
  background: white;
  border: 1.5px solid #dde8dd;
  border-radius: var(--rayon-carte);
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.14s both;
}

.legende-label {
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--couleur-texte-discret);
  letter-spacing: 0.04em;
  text-transform: uppercase;
  margin-right: 0.15rem;
  flex-shrink: 0;
}

.legende-items {
  display: flex;
  flex-wrap: wrap;
  gap: var(--espace-m);
}

.legende-item {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.83rem;
  color: var(--couleur-texte);
  font-weight: 500;
}

.legende-pastille {
  width: 10px;
  height: 10px;
  border-radius: 3px;
  flex-shrink: 0;
}

.pastille-attente  { background: #f57c00; }
.pastille-confirme { background: #2e7d32; }
.pastille-termine  { background: #757575; }
.pastille-annule   { background: #c62828; }

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
  max-width: 300px;
}

.vide-actions {
  display: flex;
  gap: var(--espace-m);
  flex-wrap: wrap;
  justify-content: center;
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
@media (max-width: 768px) {
  :deep(.sportlink-cal .vuecal__cell) {
    min-height: 68px;
  }

  .chip-heure,
  .chip-lieu {
    display: none;
  }

  .chip-sport {
    font-size: 0.7rem;
  }

  .cal-chip {
    padding: 4px 5px 3px;
  }
}

@media (max-width: 640px) {
  .entete-page {
    flex-direction: column;
    gap: var(--espace-s);
  }

  :deep(.sportlink-cal .vuecal__cell) {
    min-height: 52px;
  }

  .legende-items {
    gap: 0.65rem;
  }
}
</style>
