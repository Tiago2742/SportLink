<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { chargerProfilPublic } from '@/services/api'
import IconeSection from '@/components/ui/IconeSection.vue'
import { ArrowLeft, Star, Trophy } from 'lucide-vue-next'

const auth   = useAuthStore()
const route  = useRoute()
const router = useRouter()

const profilId    = Number(route.params.id)
const profil      = ref<any>(null)
const chargement  = ref(true)
const erreur      = ref('')

onMounted(async () => {
  if (auth.utilisateur?.id === profilId) {
    router.replace('/profil')
    return
  }
  try {
    profil.value = await chargerProfilPublic(auth.token!, profilId)
  } catch (e: any) {
    erreur.value = e?.statut === 404
      ? 'Ce profil est introuvable.'
      : 'Impossible de charger ce profil.'
  } finally {
    chargement.value = false
  }
})

const estClub = computed(() => profil.value?.type === 'club')

const nomComplet = computed(() => {
  const p = profil.value
  if (!p) return ''
  if (p.type === 'club') return p.nom ?? ''
  return [p.prenom, p.nom].filter(Boolean).join(' ')
})

const typeLabel = computed(() =>
  profil.value?.type === 'club' ? 'Club / Association' : 'Joueur',
)

const initiales = computed(() => {
  const p = profil.value
  if (!p) return '?'
  if (p.type === 'club') return (p.nom?.[0] ?? '?').toUpperCase()
  return ((p.prenom?.[0] ?? '') + (p.nom?.[0] ?? '')).toUpperCase() || '?'
})
</script>

<template>
  <div class="page-profil">
    <div class="conteneur page-corps">

      <!-- Retour -->
      <button class="btn-retour" @click="router.back()">
        <ArrowLeft :size="16" aria-hidden="true" />
        Retour
      </button>

      <!-- En-tête -->
      <div class="page-entete">
        <span class="page-eyebrow">Profil public</span>
        <h1 class="page-titre">{{ nomComplet || '…' }}</h1>
      </div>

      <div v-if="chargement" class="chargement">Chargement...</div>

      <div v-else-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>

      <template v-else-if="profil">

        <!-- ── Carte identité ── -->
        <section class="carte profil-principale">
          <div class="profil-banner">

            <div class="profil-avatar-zone">
              <template v-if="estClub && profil.logo">
                <img :src="profil.logo" :alt="nomComplet" class="profil-logo" />
              </template>
              <div v-else class="profil-avatar-grand" aria-hidden="true">{{ initiales }}</div>
            </div>

            <div class="profil-identite">
              <span class="tag-type">{{ typeLabel }}</span>
              <div class="profil-info-grille">
                <div class="champ-affichage" :class="{ 'champ-affichage-pleine': estClub }">
                  <label>{{ estClub ? 'Nom du club' : 'Nom' }}</label>
                  <span>{{ profil.nom || '—' }}</span>
                </div>
                <div v-if="!estClub" class="champ-affichage">
                  <label>Prénom</label>
                  <span>{{ profil.prenom || '—' }}</span>
                </div>
                <div class="champ-affichage">
                  <label>Localisation</label>
                  <span>{{ profil.localisation || '—' }}</span>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- ── Carte sports ── -->
        <section class="carte section-sports">
          <div class="section-titre-icone">
            <IconeSection :icone="Trophy" label="Sports" />
            <h2>Sports pratiqués</h2>
            <span v-if="profil.niveaux?.length" class="section-badge">{{ profil.niveaux.length }}</span>
          </div>

          <div v-if="profil.niveaux?.length" class="sports-liste">
            <div v-for="un in profil.niveaux" :key="un.id" class="sport-ligne">
              <div class="sport-ligne-info">
                <span class="sport-ligne-nom">{{ un.sport?.nom }}</span>
                <span class="sport-ligne-niveau">{{ un.niveau?.libelle }}</span>
              </div>
            </div>
          </div>
          <p v-else class="section-desc">Aucun sport déclaré.</p>
        </section>

        <!-- ── Carte réputation ── -->
        <section class="carte section-reputation">
          <div class="section-titre-icone">
            <IconeSection :icone="Star" label="Réputation" />
            <h2>Réputation</h2>
            <span v-if="profil.reputation?.total" class="section-badge">
              {{ profil.reputation.total }} avis
            </span>
          </div>

          <template v-if="profil.reputation">
            <div class="reputation-grille">
              <div class="reputation-critere">
                <span class="reputation-label">Ponctualité</span>
                <div class="reputation-score">
                  <span class="reputation-note">{{ profil.reputation.ponctualite.toFixed(1) }}</span>
                  <div class="etoiles" aria-hidden="true">
                    <span
                      v-for="i in 5" :key="i"
                      class="etoile"
                      :class="{ 'etoile--active': i <= Math.round(profil.reputation.ponctualite) }"
                    >★</span>
                  </div>
                </div>
              </div>
              <div class="reputation-critere">
                <span class="reputation-label">Fair-play</span>
                <div class="reputation-score">
                  <span class="reputation-note">{{ profil.reputation.fairPlay.toFixed(1) }}</span>
                  <div class="etoiles" aria-hidden="true">
                    <span
                      v-for="i in 5" :key="i"
                      class="etoile"
                      :class="{ 'etoile--active': i <= Math.round(profil.reputation.fairPlay) }"
                    >★</span>
                  </div>
                </div>
              </div>
              <div class="reputation-critere">
                <span class="reputation-label">Niveau conforme</span>
                <div class="reputation-score">
                  <span class="reputation-note">{{ profil.reputation.niveauConforme.toFixed(1) }}</span>
                  <div class="etoiles" aria-hidden="true">
                    <span
                      v-for="i in 5" :key="i"
                      class="etoile"
                      :class="{ 'etoile--active': i <= Math.round(profil.reputation.niveauConforme) }"
                    >★</span>
                  </div>
                </div>
              </div>
            </div>
            <p class="reputation-total">
              Basé sur {{ profil.reputation.total }} évaluation{{ profil.reputation.total > 1 ? 's' : '' }}
            </p>
          </template>

          <template v-else>
            <p class="section-desc">Aucune évaluation reçue pour le moment.</p>
          </template>
        </section>

      </template>
    </div>
  </div>
</template>

<style scoped>
.page-profil {
  background: #f5f9f5;
  min-height: 100vh;
  position: relative;
  overflow: hidden;
}

.page-profil::before {
  content: '';
  position: absolute;
  top: -80px; right: -80px;
  width: 380px; height: 380px;
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
  gap: var(--espace-m);
}

.btn-retour {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 0.85rem;
  color: var(--couleur-texte-discret);
  padding: 0;
  transition: color 0.15s;
  margin-bottom: var(--espace-xs);
}

.btn-retour:hover {
  color: var(--couleur-primaire);
}

.page-entete {
  margin-bottom: var(--espace-s);
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
}

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
}

/* ── Carte identité ── */
.profil-principale {
  padding: var(--espace-xl);
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.06s both;
}

.profil-banner {
  display: flex;
  align-items: flex-start;
  gap: var(--espace-l);
  flex-wrap: wrap;
}

.profil-avatar-zone {
  flex-shrink: 0;
}

.profil-avatar-grand {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: var(--degrade-primaire);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.profil-logo {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  object-fit: cover;
  display: block;
  border: 2px solid var(--couleur-bordure);
}

.profil-identite {
  flex: 1;
  min-width: 0;
}

.tag-type {
  display: inline-flex;
  align-items: center;
  background: var(--couleur-accent-fond);
  color: var(--couleur-accent-texte);
  padding: 0.18rem 0.65rem;
  border-radius: var(--rayon-badge);
  font-size: 0.67rem;
  font-weight: 700;
  letter-spacing: 0.07em;
  text-transform: uppercase;
  margin-bottom: 0.75rem;
}

.profil-info-grille {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
  gap: var(--espace-m) var(--espace-xl);
}

.champ-affichage {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.champ-affichage label {
  font-size: 0.71rem;
  color: var(--couleur-texte-discret);
  text-transform: uppercase;
  font-weight: 600;
  letter-spacing: 0.06em;
}

.champ-affichage span {
  font-size: 0.92rem;
  color: var(--couleur-texte);
  font-weight: 500;
}

/* ── Sports ── */
.section-sports {
  padding: var(--espace-l);
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.10s both;
}

.sports-liste {
  display: flex;
  flex-direction: column;
}

.sport-ligne {
  border-bottom: 1px solid var(--couleur-bordure);
}

.sport-ligne:last-child {
  border-bottom: none;
}

.sport-ligne-info {
  display: flex;
  align-items: center;
  gap: var(--espace-s);
  padding: 0.65rem 0;
  flex-wrap: wrap;
}

.sport-ligne-nom {
  font-size: 0.92rem;
  font-weight: 600;
  color: var(--couleur-titre);
  min-width: 110px;
}

.sport-ligne-niveau {
  font-size: 0.82rem;
  color: var(--couleur-texte-discret);
  background: var(--couleur-primaire-tres-claire);
  padding: 0.2rem 0.55rem;
  border-radius: var(--rayon-badge);
}

/* ── Réputation ── */
.section-reputation {
  padding: var(--espace-l);
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.12s both;
}

.section-titre-icone {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  margin-bottom: var(--espace-l);
  padding-bottom: var(--espace-m);
  border-bottom: 1px solid var(--couleur-bordure);
}

.section-titre-icone h2 {
  font-size: 1rem;
  font-weight: 700;
  letter-spacing: -0.01em;
  color: var(--couleur-titre);
  flex: 1;
}

.section-badge {
  font-size: 0.73rem;
  font-weight: 600;
  color: var(--couleur-accent-texte);
  background: var(--couleur-accent-fond);
  padding: 0.2rem 0.6rem;
  border-radius: var(--rayon-badge);
}

.section-desc {
  font-size: 0.88rem;
  color: var(--couleur-texte-discret);
  line-height: 1.5;
}

.reputation-grille {
  display: flex;
  flex-direction: column;
  margin-bottom: var(--espace-m);
}

.reputation-critere {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.7rem 0;
  border-bottom: 1px solid var(--couleur-bordure);
}

.reputation-critere:last-child {
  border-bottom: none;
}

.reputation-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--couleur-titre);
}

.reputation-score {
  display: flex;
  align-items: center;
  gap: var(--espace-s);
}

.reputation-note {
  font-size: 1rem;
  font-weight: 700;
  color: var(--couleur-titre);
  min-width: 2.2rem;
  text-align: right;
}

.etoiles {
  display: flex;
  gap: 2px;
}

.etoile {
  font-size: 1.1rem;
  color: #d0d0d0;
  line-height: 1;
  transition: color 0.1s;
}

.etoile--active {
  color: #f5a623;
}

.reputation-total {
  font-size: 0.8rem;
  color: var(--couleur-texte-discret);
  text-align: right;
  font-style: italic;
}

.chargement {
  color: var(--couleur-texte-discret);
  font-size: 0.88rem;
  text-align: center;
  padding: var(--espace-xl);
}

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(14px); }
  to   { opacity: 1; transform: translateY(0); }
}

@media (max-width: 640px) {
  .profil-principale { padding: var(--espace-l); }
  .profil-banner { gap: var(--espace-m); }
  .profil-avatar-grand { width: 56px; height: 56px; font-size: 1.25rem; }
  .profil-info-grille { grid-template-columns: 1fr 1fr; }
}
</style>
