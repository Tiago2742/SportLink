<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { chargerMatchs, chargerMesMatchs, ajouterCamp } from '@/services/api'
import { utilisateurEstInscrit } from '@/composables/useMatchCamps'
import { useSports } from '@/composables/useSports'
import CarteMatch from '@/components/matchs/CarteMatch.vue'
import BadgeStatut from '@/components/commun/BadgeStatut.vue'

const auth = useAuthStore()
const router = useRouter()
const { listeSports, niveauxPour, chargerCatalogue } = useSports()

const matchsDisponibles = ref<any[]>([])
const mesMatchs = ref<any[]>([])
const chargementMatchs = ref(true)
const erreur = ref('')

const recherche          = ref('')
const filtreSport        = ref<number | ''>('')
const filtreNiveau       = ref<number | ''>('')
const filtreLocalisation = ref('')

onMounted(() => {
  chargerCatalogue()
  charger()
})

async function charger() {
  chargementMatchs.value = true
  const userId = auth.utilisateur?.id
  try {
    const [disponibles, miens] = await Promise.all([
      chargerMatchs(auth.token!, { statut: 'disponible' }),
      userId ? chargerMesMatchs(auth.token!) : Promise.resolve([]),
    ])
    matchsDisponibles.value = disponibles.slice(0, 3)
    mesMatchs.value = miens
  } catch {
    erreur.value = 'Impossible de charger les matchs.'
  } finally {
    chargementMatchs.value = false
  }
}

async function rechercherMatchs() {
  router.push({
    path: '/rechercher',
    query: {
      q:            recherche.value || undefined,
      sportId:      filtreSport.value !== '' ? filtreSport.value : undefined,
      niveauId:     filtreNiveau.value !== '' ? filtreNiveau.value : undefined,
      localisation: filtreLocalisation.value || undefined,
    },
  })
}

async function rejoindreMatch(matchId: number) {
  if (!auth.utilisateur) return
  const m = matchsDisponibles.value.find((x) => x.id === matchId)
  if (m && utilisateurEstInscrit(m, auth.utilisateur.id)) return
  try {
    if (m?.sport?.type === 'individuel') {
      await ajouterCamp(auth.token!, matchId, { joueurId: auth.utilisateur.id })
      await charger()
    } else {
      router.push(`/matchs/${matchId}`)
    }
  } catch (e: any) {
    alert(e.message || 'Impossible de rejoindre ce match.')
  }
}

function jourMois(dateStr: string) {
  const d = new Date(dateStr)
  return {
    jour:  d.getDate(),
    mois:  d.toLocaleDateString('fr-FR', { month: 'short' }),
    heure: d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }),
  }
}
</script>

<template>
  <div class="page-accueil">

    <!-- ══ HERO ══ -->
    <section class="section-hero">
      <div class="conteneur hero-grille">

        <!-- Texte éditorial -->
        <div class="hero-texte">
          <div class="hero-eyebrow">
            <span class="hero-dot"></span>
            Matchs disponibles
          </div>
          <h1 class="hero-titre">Trouvez votre<br>prochain match</h1>
          <p class="hero-sous-titre">Mettez-vous en relation avec des équipes et joueurs amateurs près de chez vous</p>
        </div>

        <!-- Panel de recherche -->
        <div class="hero-panel">
          <p class="panel-label">Recherche rapide</p>
          <input
            v-model="recherche"
            type="text"
            class="champ panel-input"
            placeholder="Football, Basketball..."
            @keyup.enter="rechercherMatchs"
          />
          <div class="panel-filtres">
            <select v-model="filtreSport" class="champ" @change="filtreNiveau = ''">
              <option value="">Tous les sports</option>
              <option v-for="sport in listeSports" :key="sport.id" :value="sport.id">{{ sport.nom }}</option>
            </select>
            <select v-model="filtreNiveau" class="champ" :disabled="filtreSport === ''">
              <option value="">Tous niveaux</option>
              <option v-for="n in niveauxPour(filtreSport as number)" :key="n.id" :value="n.id">{{ n.libelle }}</option>
            </select>
            <input
              v-model="filtreLocalisation"
              type="text"
              class="champ"
              placeholder="Ville ou terrain"
            />
          </div>
          <button class="btn-panel-recherche" @click="rechercherMatchs">Rechercher</button>
        </div>

      </div>
    </section>

    <!-- ══ MATCHS DISPONIBLES ══ -->
    <section class="section-disponibles">
      <div class="conteneur">

        <div class="section-entete">
          <span class="section-label">Disponibles maintenant</span>
          <h2 class="section-titre">Matchs disponibles</h2>
        </div>

        <div v-if="chargementMatchs" class="chargement">Chargement des matchs...</div>
        <div v-else-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>
        <div v-else-if="matchsDisponibles.length === 0" class="etat-vide">
          Aucun match disponible pour le moment.
        </div>

        <div v-else class="grille-bento">
          <CarteMatch
            v-for="match in matchsDisponibles"
            :key="match.id"
            :match="match"
            :afficher-bouton-rejoindre="true"
            @rejoindre="rejoindreMatch"
          />
        </div>

        <div class="voir-plus" v-if="matchsDisponibles.length > 0">
          <RouterLink to="/rechercher" class="btn-voir-tous">
            Voir tous les matchs
            <span class="btn-fleche">→</span>
          </RouterLink>
        </div>

      </div>
    </section>

    <!-- ══ AGENDA (dark) ══ -->
    <section class="section-agenda" v-if="auth.estConnecte">
      <div class="conteneur">

        <div class="section-entete">
          <span class="section-label">Mon agenda</span>
          <h2 class="section-titre section-titre--inverse">Mes prochains matchs</h2>
        </div>

        <div v-if="mesMatchs.length === 0" class="etat-vide etat-vide--inverse">
          Vous n'avez pas encore de matchs.
          <RouterLink to="/creer-match">Créer un match</RouterLink>
        </div>

        <div v-else class="agenda-liste">
          <div
            v-for="match in mesMatchs"
            :key="match.id"
            class="agenda-ligne"
            @click="router.push(`/matchs/${match.id}`)"
          >
            <div class="agenda-gauche">
              <div class="agenda-date">
                <span class="agenda-date-num">{{ jourMois(match.dateMatch).jour }}</span>
                <span class="agenda-date-mois">{{ jourMois(match.dateMatch).mois }}</span>
              </div>
              <div class="agenda-info">
                <strong class="agenda-sport">{{ match.sport?.nom ?? 'Sport' }}</strong>
                <span class="agenda-meta">
                  {{ jourMois(match.dateMatch).heure }}<template v-if="match.lieu"> · {{ match.lieu }}</template>
                </span>
              </div>
            </div>
            <BadgeStatut :statut="match.statut" />
          </div>
        </div>

        <div class="agenda-cta">
          <RouterLink to="/mes-matchs" class="btn-agenda-sec">Voir tous mes matchs</RouterLink>
          <RouterLink to="/creer-match" class="btn-agenda-pri">+ Créer un match</RouterLink>
        </div>

      </div>
    </section>

  </div>
</template>

<style scoped>
/* ══════════════════════════════════════
   HERO
══════════════════════════════════════ */
.section-hero {
  background:
    radial-gradient(ellipse 55% 65% at 92% 115%, rgba(154, 230, 0, 0.09) 0%, transparent 55%),
    linear-gradient(148deg, #071a07 0%, #0d280d 40%, #1b5e20 78%, #256025 100%);
  padding: var(--espace-xl) 0 var(--espace-xxl);
  position: relative;
  overflow: hidden;
}

/* Séparateur lime bas du hero */
.section-hero::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent 0%, rgba(154, 230, 0, 0.4) 40%, rgba(154, 230, 0, 0.4) 60%, transparent 100%);
}

.hero-grille {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--espace-xxl);
  align-items: center;
  min-height: 260px;
}

/* — Texte — */
.hero-texte {
  color: white;
}

.hero-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: rgba(154, 230, 0, 0.1);
  border: 1px solid rgba(154, 230, 0, 0.22);
  color: var(--couleur-accent);
  padding: 0.28rem 0.85rem;
  border-radius: var(--rayon-badge);
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-bottom: var(--espace-m);
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.hero-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--couleur-accent);
  flex-shrink: 0;
  animation: dotPulse 2.2s ease-in-out infinite;
}

.hero-titre {
  font-size: 3.5rem;
  font-weight: 900;
  letter-spacing: -0.04em;
  line-height: 1.04;
  color: white;
  margin-bottom: var(--espace-m);
  animation: fadeUp 0.6s cubic-bezier(0.22, 1, 0.36, 1) 0.07s both;
}

.hero-sous-titre {
  font-size: 1rem;
  line-height: 1.65;
  color: rgba(255, 255, 255, 0.62);
  max-width: 38ch;
  animation: fadeUp 0.6s cubic-bezier(0.22, 1, 0.36, 1) 0.14s both;
}

/* — Panel recherche — */
.hero-panel {
  background: #ffffff;
  border-radius: 12px;
  padding: var(--espace-l) var(--espace-xl);
  box-shadow:
    0 0 0 1px rgba(0, 0, 0, 0.06),
    0 20px 60px rgba(0, 0, 0, 0.28),
    0 6px 18px rgba(0, 0, 0, 0.12);
  animation: fadeUp 0.7s cubic-bezier(0.22, 1, 0.36, 1) 0.2s both;
}

.panel-label {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.07em;
  text-transform: uppercase;
  color: var(--couleur-texte-discret);
  margin-bottom: var(--espace-m);
}

.panel-input {
  font-size: 1rem;
  padding: 0.75rem 1rem;
  margin-bottom: var(--espace-s);
  border-radius: 8px;
}

.panel-filtres {
  display: flex;
  flex-direction: column;
  gap: var(--espace-s);
  margin-bottom: var(--espace-m);
}

.panel-filtres .champ {
  border-radius: 8px;
}

.btn-panel-recherche {
  width: 100%;
  padding: 0.78rem;
  background: var(--degrade-primaire);
  color: white;
  font-size: 0.9rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  box-shadow: var(--ombre-bouton);
  transition:
    transform 0.22s cubic-bezier(0.22, 1, 0.36, 1),
    box-shadow 0.22s cubic-bezier(0.22, 1, 0.36, 1);
}

.btn-panel-recherche:hover {
  transform: translateY(-2px);
  box-shadow: var(--ombre-bouton-survol);
}

.btn-panel-recherche:active {
  transform: scale(0.97);
  box-shadow: var(--ombre-bouton);
}

/* ══════════════════════════════════════
   SECTION MATCHS DISPONIBLES
══════════════════════════════════════ */
.section-disponibles {
  background: #ffffff;
  padding: var(--espace-xxl) 0;
}

.section-entete {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  margin-bottom: var(--espace-xl);
}

.section-label {
  display: inline-block;
  align-self: flex-start;
  background: var(--couleur-accent-fond);
  color: var(--couleur-accent-texte);
  padding: 0.2rem 0.7rem;
  border-radius: var(--rayon-badge);
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.section-titre {
  font-size: 2rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  line-height: 1.1;
  color: var(--couleur-titre);
}

.section-titre--inverse {
  color: rgba(255, 255, 255, 0.95);
}

/* — Bento grid — */
.grille-bento {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--espace-m);
}

/* Première carte : pleine largeur (featured) */
.grille-bento > *:first-child {
  grid-column: 1 / -1;
}

/* ─ Override CarteMatch dans la grille bento ─ */
.grille-bento :deep(.carte-match) {
  border-radius: 8px;
  box-shadow: none;
  border: 1.5px solid #dde8dd;
  transition:
    transform 0.28s cubic-bezier(0.22, 1, 0.36, 1),
    border-color 0.2s ease,
    box-shadow 0.28s cubic-bezier(0.22, 1, 0.36, 1);
}

.grille-bento :deep(.carte-match:hover) {
  transform: translateY(-5px);
  border-color: var(--couleur-primaire-claire);
  box-shadow: 0 16px 48px rgba(27, 94, 32, 0.10), 0 4px 12px rgba(0, 0, 0, 0.04);
}

/* Carte featured : fond dégradé très doux + titre plus grand */
.grille-bento :deep(.carte-match:first-child) {
  background: linear-gradient(130deg, var(--couleur-primaire-tres-claire) 0%, #ffffff 60%);
  border-color: rgba(46, 125, 50, 0.18);
  padding: var(--espace-xl);
}

.grille-bento :deep(.carte-match:first-child .carte-titre) {
  font-size: 1.45rem;
  letter-spacing: -0.025em;
}

/* Scroll-reveal pour les cartes */
@supports (animation-timeline: view()) {
  .grille-bento > * {
    animation: fadeUp 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
    animation-timeline: view();
    animation-range: entry 0% cover 30%;
  }
}

/* — Voir plus — */
.voir-plus {
  display: flex;
  justify-content: center;
  margin-top: var(--espace-xl);
}

.btn-voir-tous {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.72rem 1.75rem;
  border: 1.5px solid #dde8dd;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--couleur-texte);
  background: white;
  text-decoration: none;
  transition:
    border-color 0.2s ease,
    color 0.2s ease,
    background 0.2s ease,
    transform 0.22s cubic-bezier(0.22, 1, 0.36, 1);
}

.btn-voir-tous:hover {
  border-color: var(--couleur-primaire);
  color: var(--couleur-primaire);
  background: var(--couleur-primaire-tres-claire);
  transform: translateX(3px);
}

.btn-fleche {
  transition: transform 0.22s cubic-bezier(0.22, 1, 0.36, 1);
}

.btn-voir-tous:hover .btn-fleche {
  transform: translateX(4px);
}

/* ══════════════════════════════════════
   SECTION AGENDA (dark)
══════════════════════════════════════ */
.section-agenda {
  background:
    radial-gradient(ellipse 55% 70% at 12% 50%, rgba(46, 125, 50, 0.07) 0%, transparent 55%),
    linear-gradient(158deg, #071a07 0%, #0d280d 55%, #071a07 100%);
  padding: var(--espace-xxl) 0;
  border-top: 1px solid rgba(154, 230, 0, 0.12);
}

/* Scroll-reveal pour la section agenda */
@supports (animation-timeline: view()) {
  .section-agenda {
    animation: fadeUp 0.7s cubic-bezier(0.22, 1, 0.36, 1) both;
    animation-timeline: view();
    animation-range: entry 0% cover 25%;
  }
}

.agenda-liste {
  border: 1px solid rgba(255, 255, 255, 0.07);
  border-radius: 10px;
  overflow: hidden;
  margin-bottom: var(--espace-l);
}

.agenda-ligne {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--espace-m) var(--espace-l);
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  cursor: pointer;
  transition: background 0.18s ease;
}

.agenda-ligne:last-child {
  border-bottom: none;
}

.agenda-ligne:hover {
  background: rgba(255, 255, 255, 0.04);
}

.agenda-gauche {
  display: flex;
  align-items: center;
  gap: var(--espace-m);
}

.agenda-date {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 44px;
  padding: 0.3rem 0.5rem;
  background: rgba(154, 230, 0, 0.1);
  border-radius: 6px;
  flex-shrink: 0;
  line-height: 1.15;
}

.agenda-date-num {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--couleur-accent);
}

.agenda-date-mois {
  font-size: 0.6rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--couleur-accent);
}

.agenda-info {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.agenda-sport {
  font-size: 0.95rem;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.92);
}

.agenda-meta {
  font-size: 0.8rem;
  color: rgba(255, 255, 255, 0.38);
}

.agenda-cta {
  display: flex;
  gap: var(--espace-m);
  justify-content: flex-end;
}

.btn-agenda-sec {
  display: inline-flex;
  align-items: center;
  padding: 0.65rem 1.25rem;
  border: 1.5px solid rgba(255, 255, 255, 0.18);
  border-radius: 8px;
  font-size: 0.88rem;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.72);
  background: transparent;
  text-decoration: none;
  transition: all 0.22s ease;
}

.btn-agenda-sec:hover {
  border-color: rgba(255, 255, 255, 0.38);
  color: white;
  background: rgba(255, 255, 255, 0.05);
}

.btn-agenda-pri {
  display: inline-flex;
  align-items: center;
  padding: 0.65rem 1.25rem;
  background: var(--degrade-primaire);
  border-radius: 8px;
  font-size: 0.88rem;
  font-weight: 700;
  color: white;
  text-decoration: none;
  box-shadow: var(--ombre-bouton);
  transition:
    transform 0.22s cubic-bezier(0.22, 1, 0.36, 1),
    box-shadow 0.22s cubic-bezier(0.22, 1, 0.36, 1);
}

.btn-agenda-pri:hover {
  transform: translateY(-2px);
  box-shadow: var(--ombre-bouton-survol);
}

/* ══════════════════════════════════════
   ÉTATS
══════════════════════════════════════ */
.etat-vide {
  text-align: center;
  padding: var(--espace-xl);
  color: var(--couleur-texte-discret);
  background: white;
  border-radius: 10px;
  border: 1px dashed var(--couleur-bordure);
}

.etat-vide a {
  color: var(--couleur-primaire);
  text-decoration: none;
  font-weight: 500;
  margin-left: 0.4rem;
}

.etat-vide--inverse {
  background: rgba(255, 255, 255, 0.03);
  border-color: rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.45);
}

/* ══════════════════════════════════════
   ANIMATIONS
══════════════════════════════════════ */
@keyframes fadeUp {
  from {
    opacity: 0;
    transform: translateY(18px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes dotPulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: 0.35; transform: scale(0.7); }
}

/* ══════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════ */
@media (max-width: 960px) {
  .hero-grille {
    grid-template-columns: 1fr;
    min-height: unset;
    gap: var(--espace-xl);
  }

  .hero-titre {
    font-size: 2.6rem;
  }

  .hero-sous-titre {
    max-width: unset;
  }

  .grille-bento {
    grid-template-columns: 1fr;
  }

  /* featured card: retire le fond dégradé sur mobile (moins pertinent) */
  .grille-bento :deep(.carte-match:first-child) {
    padding: var(--espace-l);
  }

  .grille-bento :deep(.carte-match:first-child .carte-titre) {
    font-size: 1.15rem;
  }

  .agenda-cta {
    flex-direction: column;
    align-items: stretch;
  }

  .btn-agenda-sec,
  .btn-agenda-pri {
    justify-content: center;
  }
}

@media (max-width: 600px) {
  .hero-titre {
    font-size: 2rem;
  }

  .section-titre {
    font-size: 1.6rem;
  }

  .hero-panel {
    padding: var(--espace-l);
  }
}
</style>
