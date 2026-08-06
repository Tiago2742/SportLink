<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { chargerMesMatchs } from '@/services/api'
import BadgeStatut from '@/components/commun/BadgeStatut.vue'
import { CalendarDays, Plus, Search, Users } from 'lucide-vue-next'

const auth   = useAuthStore()
const router = useRouter()

interface MatchResume {
  id: number
  dateMatch: string
  lieu: string | null
  statut: string
  sport: { nom: string } | null
}

const mesMatchs        = ref<MatchResume[]>([])
const chargementMatchs = ref(true)
const erreur           = ref('')

onMounted(charger)

async function charger() {
  chargementMatchs.value = true
  try {
    const miens = await chargerMesMatchs(auth.token!)
    const now   = new Date()
    mesMatchs.value = (miens as MatchResume[])
      .filter((m) => new Date(m.dateMatch) > now)
      .slice(0, 3)
  } catch {
    erreur.value = 'Impossible de charger les matchs.'
  } finally {
    chargementMatchs.value = false
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

const estClub = computed(() => auth.utilisateur?.type === 'club')

const prenomOuNom = computed(() => {
  const u = auth.utilisateur
  if (!u) return ''
  return u.type === 'club' ? (u.nom ?? '') : (u.prenom ?? u.nom ?? '')
})

const lienEquipes = computed(() =>
  estClub.value ? '/mes-equipes' : '/mes-equipes-joueur'
)
</script>

<template>
  <div class="page-accueil">

    <!-- ══ ACCROCHE ══ -->
    <section class="section-hero">
      <div class="conteneur">
        <div class="hero-eyebrow">
          <span class="hero-dot"></span>
          SportLink
        </div>
        <h1 class="hero-titre">
          Bonjour<template v-if="prenomOuNom">, {{ prenomOuNom }}</template>
        </h1>
        <p class="hero-sous-titre">
          Organisez et rejoignez des matchs près de chez vous entre équipes ou entre joueurs.
        </p>
      </div>
    </section>
    
    <!-- ══ PROCHAINS MATCHS ══ -->
    <section class="section-matchs">
      <div class="conteneur">

        <div class="section-entete">
          <span class="section-label">Agenda</span>
          <h2 class="section-titre">Vos prochains matchs</h2>
        </div>

        <div v-if="chargementMatchs" class="chargement">Chargement…</div>
        <div v-else-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>

        <template v-else-if="mesMatchs.length === 0">
          <div class="etat-vide">
            <p class="etat-vide-texte">Aucun match à venir pour le moment.</p>
            <RouterLink to="/rechercher" class="btn btn-primaire">
              <Search :size="15" aria-hidden="true" />
              Trouver un match
            </RouterLink>
          </div>
        </template>

        <template v-else>
          <div class="matchs-liste">
            <div
              v-for="match in mesMatchs"
              :key="match.id"
              class="match-ligne"
              role="link"
              tabindex="0"
              @click="router.push(`/matchs/${match.id}`)"
              @keyup.enter="router.push(`/matchs/${match.id}`)"
            >
              <div class="match-date">
                <span class="match-date-num">{{ jourMois(match.dateMatch).jour }}</span>
                <span class="match-date-mois">{{ jourMois(match.dateMatch).mois }}</span>
              </div>
              <div class="match-info">
                <strong class="match-sport">{{ match.sport?.nom ?? 'Sport' }}</strong>
                <span class="match-meta">
                  {{ jourMois(match.dateMatch).heure
                  }}<template v-if="match.lieu"> · {{ match.lieu }}</template>
                </span>
              </div>
              <BadgeStatut :statut="match.statut" />
              <span class="match-fleche" aria-hidden="true">→</span>
            </div>
          </div>

          <div class="matchs-footer">
            <RouterLink to="/mes-matchs" class="lien-voir-tous">
              Voir tous mes matchs <span class="lien-fleche">→</span>
            </RouterLink>
          </div>
        </template>

      </div>
    </section>

    <!-- ══ RACCOURCIS ══ -->
    <section class="section-raccourcis">
      <div class="conteneur">

        <div class="section-entete">
          <span class="section-label">Actions rapides</span>
          <h2 class="section-titre">Que souhaitez-vous faire ?</h2>
        </div>

        <div class="raccourcis-grille">

          <RouterLink to="/creer-match" class="raccourci-carte">
            <div class="raccourci-icone raccourci-icone--vert">
              <Plus :size="22" aria-hidden="true" />
            </div>
            <div class="raccourci-texte">
              <p class="raccourci-titre">Créer un match</p>
              <p class="raccourci-desc">
                {{ estClub
                  ? 'Organisez un match collectif pour votre équipe'
                  : 'Lancez un défi individuel contre un autre joueur' }}
              </p>
            </div>
            <span class="raccourci-fleche" aria-hidden="true">→</span>
          </RouterLink>

          <RouterLink to="/rechercher" class="raccourci-carte">
            <div class="raccourci-icone raccourci-icone--bleu">
              <Search :size="22" aria-hidden="true" />
            </div>
            <div class="raccourci-texte">
              <p class="raccourci-titre">Trouver un match</p>
              <p class="raccourci-desc">Parcourez les matchs disponibles près de chez vous</p>
            </div>
            <span class="raccourci-fleche" aria-hidden="true">→</span>
          </RouterLink>

          <RouterLink :to="lienEquipes" class="raccourci-carte">
            <div class="raccourci-icone raccourci-icone--violet">
              <Users :size="22" aria-hidden="true" />
            </div>
            <div class="raccourci-texte">
              <p class="raccourci-titre">Mes équipes</p>
              <p class="raccourci-desc">
                {{ estClub
                  ? 'Gérez vos équipes et leurs membres'
                  : 'Consultez vos équipes et vos invitations en attente' }}
              </p>
            </div>
            <span class="raccourci-fleche" aria-hidden="true">→</span>
          </RouterLink>

          <RouterLink to="/calendrier" class="raccourci-carte">
            <div class="raccourci-icone raccourci-icone--orange">
              <CalendarDays :size="22" aria-hidden="true" />
            </div>
            <div class="raccourci-texte">
              <p class="raccourci-titre">Calendrier</p>
              <p class="raccourci-desc">Visualisez tous vos matchs organisés dans le temps</p>
            </div>
            <span class="raccourci-fleche" aria-hidden="true">→</span>
          </RouterLink>

        </div>
      </div>
    </section>

  </div>
</template>

<style scoped>
/* ══════════════════════════════════════
   HERO / ACCROCHE
══════════════════════════════════════ */
.section-hero {
  background:
    radial-gradient(ellipse 55% 65% at 92% 115%, rgba(154, 230, 0, 0.09) 0%, transparent 55%),
    linear-gradient(148deg, #071a07 0%, #0d280d 40%, #1b5e20 78%, #256025 100%);
  padding: var(--espace-xl) 0 var(--espace-xxl);
  position: relative;
  overflow: hidden;
}

.section-hero::after {
  content: '';
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent 0%, rgba(154, 230, 0, 0.4) 40%, rgba(154, 230, 0, 0.4) 60%, transparent 100%);
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
  font-size: 2.8rem;
  font-weight: 900;
  letter-spacing: -0.04em;
  line-height: 1.1;
  color: white;
  margin-bottom: var(--espace-m);
  animation: fadeUp 0.6s cubic-bezier(0.22, 1, 0.36, 1) 0.07s both;
}

.hero-sous-titre {
  font-size: 1rem;
  line-height: 1.65;
  color: rgba(255, 255, 255, 0.62);
  animation: fadeUp 0.6s cubic-bezier(0.22, 1, 0.36, 1) 0.14s both;
}

/* ══════════════════════════════════════
   EN-TÊTES DE SECTION (commun)
══════════════════════════════════════ */
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
  font-size: 1.75rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  color: var(--couleur-titre);
}

/* ══════════════════════════════════════
   SECTION PROCHAINS MATCHS
══════════════════════════════════════ */
.section-matchs {
  background: #f5f9f5;
  padding: var(--espace-xxl) 0;
}

.etat-vide {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--espace-m);
  padding: var(--espace-xxl) var(--espace-xl);
  background: white;
  border-radius: 12px;
  border: 1.5px dashed var(--couleur-bordure);
  text-align: center;
}

.etat-vide-texte {
  color: var(--couleur-texte-discret);
  font-size: 0.95rem;
}

.matchs-liste {
  background: white;
  border-radius: 12px;
  border: 1.5px solid #dde8dd;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(27, 94, 32, 0.06);
  margin-bottom: var(--espace-m);
}

.match-ligne {
  display: flex;
  align-items: center;
  gap: var(--espace-m);
  padding: var(--espace-m) var(--espace-l);
  border-bottom: 1px solid #f0f4f0;
  cursor: pointer;
  transition: background 0.18s ease;
}

.match-ligne:last-child {
  border-bottom: none;
}

.match-ligne:hover {
  background: #f5f9f5;
}

.match-date {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 44px;
  padding: 0.35rem 0.5rem;
  background: var(--couleur-accent-fond);
  border-radius: 8px;
  flex-shrink: 0;
  line-height: 1.2;
}

.match-date-num {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--couleur-accent-texte);
}

.match-date-mois {
  font-size: 0.6rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--couleur-accent-texte);
}

.match-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  min-width: 0;
}

.match-sport {
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--couleur-titre);
}

.match-meta {
  font-size: 0.8rem;
  color: var(--couleur-texte-discret);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.match-fleche {
  font-size: 1rem;
  color: #c8d8c8;
  flex-shrink: 0;
  transition: transform 0.18s ease, color 0.18s ease;
}

.match-ligne:hover .match-fleche {
  transform: translateX(4px);
  color: var(--couleur-primaire);
}

.matchs-footer {
  display: flex;
  justify-content: flex-end;
}

.lien-voir-tous {
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--couleur-primaire-foncee, #388e3c);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.lien-fleche {
  transition: transform 0.18s ease;
  display: inline-block;
}

.lien-voir-tous:hover .lien-fleche {
  transform: translateX(4px);
}

/* ══════════════════════════════════════
   SECTION RACCOURCIS
══════════════════════════════════════ */
.section-raccourcis {
  background: white;
  padding: var(--espace-xxl) 0;
  border-top: 1px solid #eef2ee;
}

.raccourcis-grille {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--espace-m);
}

.raccourci-carte {
  display: flex;
  align-items: center;
  gap: var(--espace-m);
  padding: var(--espace-l);
  background: white;
  border: 1.5px solid #dde8dd;
  border-radius: 12px;
  text-decoration: none;
  transition:
    transform 0.22s cubic-bezier(0.22, 1, 0.36, 1),
    border-color 0.2s ease,
    box-shadow 0.22s cubic-bezier(0.22, 1, 0.36, 1);
}

.raccourci-carte:hover {
  transform: translateY(-3px);
  border-color: var(--couleur-primaire-claire);
  box-shadow: 0 8px 32px rgba(27, 94, 32, 0.09);
}

.raccourci-icone {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: white;
}

.raccourci-icone--vert   { background: var(--degrade-primaire); }
.raccourci-icone--bleu   { background: linear-gradient(135deg, #1565c0, #1976d2); }
.raccourci-icone--violet { background: linear-gradient(135deg, #6a1b9a, #8e24aa); }
.raccourci-icone--orange { background: linear-gradient(135deg, #e65100, #f4511e); }

.raccourci-texte {
  flex: 1;
  min-width: 0;
}

.raccourci-titre {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--couleur-titre);
  margin-bottom: 0.2rem;
}

.raccourci-desc {
  font-size: 0.8rem;
  color: var(--couleur-texte-discret);
  line-height: 1.4;
}

.raccourci-fleche {
  font-size: 1.1rem;
  color: #c8d8c8;
  flex-shrink: 0;
  transition: transform 0.18s ease, color 0.18s ease;
}

.raccourci-carte:hover .raccourci-fleche {
  transform: translateX(4px);
  color: var(--couleur-primaire);
}

/* ══════════════════════════════════════
   ANIMATIONS
══════════════════════════════════════ */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(18px); }
  to   { opacity: 1; transform: translateY(0); }
}

@keyframes dotPulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: 0.35; transform: scale(0.7); }
}

/* ══════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════ */
@media (max-width: 768px) {
  .hero-titre          { font-size: 2.1rem; }
  .raccourcis-grille   { grid-template-columns: 1fr; }
}

@media (max-width: 480px) {
  .hero-titre          { font-size: 1.75rem; }
  .hero-sous-titre     { font-size: 0.92rem; }
  .match-ligne         { padding: var(--espace-m); gap: var(--espace-s); }
  .raccourci-carte     { padding: var(--espace-m); }
}
</style>
