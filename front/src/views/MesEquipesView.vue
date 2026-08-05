<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import AvatarEquipe from '@/components/equipes/AvatarEquipe.vue'
import { chargerEquipes, type EquipeResume } from '@/services/api'
import IconeLigne from '@/components/ui/IconeLigne.vue'
import { MapPin, Plus } from 'lucide-vue-next'

const auth = useAuthStore()

const equipes = ref<EquipeResume[]>([])
const chargement = ref(true)
const erreur = ref('')

onMounted(charger)

async function charger() {
  chargement.value = true
  erreur.value = ''
  try {
    equipes.value = await chargerEquipes(auth.token!, {
      clubId: auth.utilisateur!.id,
    })
  } catch {
    erreur.value = 'Impossible de charger vos équipes.'
  } finally {
    chargement.value = false
  }
}

// ── Stats rapides calculées depuis les données chargées ──
const nbEquipes = computed(() => equipes.value.length)

const nbSports = computed(() =>
  new Set(equipes.value.map((e) => e.sport.id).filter(Boolean)).size,
)

const nbJoueurs = computed<number | null>(() => {
  if (!equipes.value.length) return null
  return equipes.value.reduce((sum, e) => sum + e.membresConfirmesCount, 0)
})
</script>

<template>
  <div class="page-mes-equipes">
    <div class="conteneur page-corps">

      <!-- ── En-tête ── -->
      <div class="entete-page">
        <div>
          <span class="page-eyebrow">Club</span>
          <h1 class="page-titre">Mes équipes</h1>
          <p class="sous-titre">Gérez les équipes de votre club par sport</p>
        </div>
      </div>

      <div v-if="chargement" class="chargement">Chargement...</div>
      <div v-else-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>

      <!-- ── État vide ── -->
      <div v-else-if="equipes.length === 0" class="etat-vide">
        <div class="vide-icone">
          <div class="vide-avatar vide-avatar--1"></div>
          <div class="vide-avatar vide-avatar--2"></div>
          <div class="vide-avatar vide-avatar--3"></div>
        </div>
        <p class="vide-titre">Aucune équipe pour le moment</p>
        <p class="vide-desc">
          Créez votre première équipe pour inviter des joueurs et organiser des matchs collectifs.
        </p>
        <RouterLink to="/equipes/creer" class="btn btn-primaire">
          Créer ma première équipe
        </RouterLink>
      </div>

      <!-- ── Vue avec équipes ── -->
      <template v-else>

        <!-- Barre de stats rapides -->
        <div class="barre-stats">
          <div class="stat-bloc">
            <span class="stat-nb">{{ nbEquipes }}</span>
            <span class="stat-label">équipe{{ nbEquipes > 1 ? 's' : '' }}</span>
          </div>
          <div class="stat-sep"></div>
          <div class="stat-bloc">
            <span class="stat-nb">{{ nbSports }}</span>
            <span class="stat-label">sport{{ nbSports > 1 ? 's' : '' }}</span>
          </div>
          <div class="stat-sep"></div>
          <div class="stat-bloc">
            <span class="stat-nb">{{ nbJoueurs !== null ? nbJoueurs : '—' }}</span>
            <span class="stat-label">joueurs confirmés</span>
          </div>
        </div>

        <!-- Grille équipes + carte fantôme -->
        <div class="grille-equipes">
          <article v-for="equipe in equipes" :key="equipe.id" class="carte-equipe">
            <div class="carte-equipe-haut">
              <AvatarEquipe :equipe="equipe" taille="md" />
              <div class="carte-equipe-corps">
                <h2 class="carte-equipe-titre">{{ equipe.nom }}</h2>
                <p class="carte-equipe-meta">
                  {{ equipe.sport?.nom ?? 'Sport' }}
                  <template v-if="equipe.niveau"> · {{ equipe.niveau.libelle }}</template>
                </p>
                <p v-if="equipe.membresConfirmesCount !== undefined" class="carte-equipe-membres">
                  {{ equipe.membresConfirmesCount }}
                  joueur{{ equipe.membresConfirmesCount !== 1 ? 's' : '' }}
                </p>
                <IconeLigne
                  v-if="equipe.localisation"
                  :icone="MapPin"
                  :taille="13"
                  discret
                  class="carte-equipe-lieu"
                >
                  {{ equipe.localisation }}
                </IconeLigne>
              </div>
            </div>
            <div class="carte-equipe-pied">
              <RouterLink :to="`/equipes/${equipe.id}`" class="btn btn-secondaire btn-pleine-largeur">
                Voir l'équipe
              </RouterLink>
            </div>
          </article>

          <!-- Carte fantôme : créer une nouvelle équipe -->
          <RouterLink to="/equipes/creer" class="carte-creer" aria-label="Créer une équipe">
            <div class="carte-creer-cercle">
              <Plus :size="22" stroke-width="2" />
            </div>
            <p class="carte-creer-titre">Créer une équipe</p>
            <p class="carte-creer-desc">Ajoutez un sport à votre club</p>
          </RouterLink>
        </div>

      </template>

    </div>
  </div>
</template>

<style scoped>
/* ══════════════════════════════════════
   FOND PLEIN-LARGEUR
══════════════════════════════════════ */
.page-mes-equipes {
  background: #f5f9f5;
  min-height: 100vh;
  position: relative;
  overflow: hidden;
}

.page-mes-equipes::before {
  content: '';
  position: absolute;
  top: -80px; right: -80px;
  width: 380px; height: 380px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(154, 230, 0, 0.09) 0%, transparent 60%);
  pointer-events: none;
}

.page-mes-equipes::after {
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
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--espace-m);
  margin-bottom: var(--espace-l);
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
   BARRE DE STATS
══════════════════════════════════════ */
.barre-stats {
  display: flex;
  align-items: center;
  gap: var(--espace-l);
  padding: var(--espace-m) var(--espace-l);
  background: #ffffff;
  border: 1.5px solid #dde8dd;
  border-radius: var(--rayon-carte);
  margin-bottom: var(--espace-xl);
  position: relative;
  overflow: hidden;
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.05s both;
}

.barre-stats::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: var(--degrade-primaire);
}

.stat-bloc {
  display: flex;
  align-items: baseline;
  gap: 0.4rem;
}

.stat-nb {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--couleur-titre);
  letter-spacing: -0.03em;
  line-height: 1;
}

.stat-label {
  font-size: 0.82rem;
  color: var(--couleur-texte-discret);
  font-weight: 500;
}

.stat-sep {
  width: 1px;
  height: 26px;
  background: var(--couleur-bordure);
  flex-shrink: 0;
}

/* ══════════════════════════════════════
   ÉTAT VIDE
══════════════════════════════════════ */
.etat-vide {
  text-align: center;
  padding: var(--espace-xxl) var(--espace-xl);
  background: #ffffff;
  border: 1.5px solid #dde8dd;
  border-radius: var(--rayon-carte);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--espace-m);
  position: relative;
  overflow: hidden;
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.1s both;
}

.etat-vide::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: var(--degrade-primaire);
}

.vide-icone {
  display: flex;
  align-items: center;
  margin-bottom: var(--espace-xs);
}

.vide-avatar {
  width: 46px; height: 46px;
  border-radius: 50%;
  border: 3px solid white;
  flex-shrink: 0;
}

.vide-avatar + .vide-avatar { margin-left: -14px; }
.vide-avatar--1 { background: linear-gradient(135deg, #e8f5e9, #c8e6c9); }
.vide-avatar--2 { background: linear-gradient(135deg, #a5d6a7, #66bb6a); }
.vide-avatar--3 { background: linear-gradient(135deg, #4caf50, #9ae600 140%); }

.vide-titre {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--couleur-titre);
}

.vide-desc {
  font-size: 0.88rem;
  color: var(--couleur-texte-discret);
  max-width: 380px;
  line-height: 1.5;
  margin-bottom: 0.2rem;
}

/* ══════════════════════════════════════
   GRILLE D'ÉQUIPES
══════════════════════════════════════ */
.grille-equipes {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: var(--espace-m);
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.12s both;
}

/* ── Carte équipe ── */
.carte-equipe {
  background: #ffffff;
  border: 1.5px solid #dde8dd;
  border-radius: var(--rayon-carte);
  padding: var(--espace-l);
  display: flex;
  flex-direction: column;
  gap: var(--espace-m);
  position: relative;
  overflow: hidden;
  transition:
    border-color 0.22s cubic-bezier(0.22, 1, 0.36, 1),
    transform 0.22s cubic-bezier(0.22, 1, 0.36, 1);
}

.carte-equipe::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: var(--degrade-primaire);
}

.carte-equipe:hover {
  border-color: var(--couleur-primaire-claire);
  transform: translateY(-3px);
}

.carte-equipe-haut {
  display: flex;
  align-items: flex-start;
  gap: var(--espace-m);
  flex: 1;
}

.carte-equipe-corps {
  flex: 1;
  min-width: 0;
}

.carte-equipe-titre {
  font-size: 1.02rem;
  font-weight: 700;
  letter-spacing: -0.01em;
  color: var(--couleur-titre);
  margin-bottom: 0.2rem;
}

.carte-equipe-meta {
  font-size: 0.84rem;
  color: var(--couleur-texte-discret);
  margin-bottom: 0.15rem;
}

.carte-equipe-membres {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--couleur-primaire);
  margin-bottom: 0.1rem;
}

.carte-equipe-lieu {
  font-size: 0.82rem;
  margin-top: 0.2rem;
}

.carte-equipe-pied {
  border-top: 1px solid var(--couleur-bordure);
  padding-top: var(--espace-m);
  margin-top: auto;
}

/* ── Carte fantôme "Créer" ── */
.carte-creer {
  border: 2px dashed #c0d4c0;
  border-radius: var(--rayon-carte);
  padding: var(--espace-l);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: var(--espace-m);
  text-decoration: none;
  background: transparent;
  min-height: 160px;
  text-align: center;
  cursor: pointer;
  transition:
    border-color 0.22s cubic-bezier(0.22, 1, 0.36, 1),
    background 0.22s cubic-bezier(0.22, 1, 0.36, 1);
}

.carte-creer:hover {
  border-color: var(--couleur-primaire);
  background: rgba(46, 125, 50, 0.04);
}

.carte-creer-cercle {
  width: 44px; height: 44px;
  border-radius: 50%;
  border: 2px dashed #c0d4c0;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--couleur-texte-discret);
  transition:
    border-color 0.22s cubic-bezier(0.22, 1, 0.36, 1),
    color 0.22s cubic-bezier(0.22, 1, 0.36, 1),
    background 0.22s cubic-bezier(0.22, 1, 0.36, 1);
}

.carte-creer:hover .carte-creer-cercle {
  border-color: var(--couleur-primaire);
  color: var(--couleur-primaire);
  background: var(--couleur-primaire-tres-claire);
}

.carte-creer-titre {
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--couleur-texte-discret);
  transition: color 0.22s ease;
}

.carte-creer:hover .carte-creer-titre {
  color: var(--couleur-primaire);
}

.carte-creer-desc {
  font-size: 0.8rem;
  color: var(--couleur-texte-discret);
  line-height: 1.4;
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
@media (max-width: 600px) {
  .barre-stats {
    gap: var(--espace-m);
    padding: var(--espace-m);
  }

  .stat-nb {
    font-size: 1.2rem;
  }
}
</style>
