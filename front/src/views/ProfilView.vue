<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { chargerMesMatchs, chargerEquipes, chargerProfil } from '@/services/api'
import IconeSection from '@/components/ui/IconeSection.vue'
import { Calendar, KeyRound, Lock, Pencil, User, Users } from 'lucide-vue-next'
const auth = useAuthStore()
const router = useRouter()

const profil = ref<any>(auth.utilisateur)
const mesEquipes = ref<any[]>([])
const mesMatchs = ref<any[]>([])
const chargement = ref(true)

const modeEdition = ref(false)
const formEdition = ref({ nom: '', prenom: '', localisation: '' })
const erreurEdition = ref('')

onMounted(async () => {
  const userId = auth.utilisateur?.id
  if (!userId) {
    chargement.value = false
    return
  }
  try {
    const [matchs, equipes] = await Promise.all([
      chargerMesMatchs(auth.token!),
      chargerEquipes(auth.token!, { clubId: userId }),
    ])
    mesMatchs.value = matchs
    mesEquipes.value = equipes
  } finally {
    chargement.value = false
  }
})

function ouvrirEdition() {
  formEdition.value = {
    nom: profil.value?.nom || '',
    prenom: profil.value?.prenom || '',
    localisation: profil.value?.localisation || '',
  }
  modeEdition.value = true
}

function annulerEdition() {
  modeEdition.value = false
  erreurEdition.value = ''
}

function formaterDate(dateStr: string) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}

const matchsAvenir = computed(() =>
  mesMatchs.value.filter((m) => new Date(m.dateMatch) >= new Date()),
)

const matchsPasses = computed(() =>
  mesMatchs.value.filter((m) => new Date(m.dateMatch) < new Date()),
)

const estClub = computed(() => profil.value?.type === 'club')

// ── Computed visuels (aucune logique métier) ──
const initiales = computed(() => {
  const p = profil.value
  if (!p) return '?'
  if (p.type === 'club') return (p.nom?.[0] ?? '?').toUpperCase()
  return ((p.prenom?.[0] ?? '') + (p.nom?.[0] ?? '')).toUpperCase() || '?'
})

const nomComplet = computed(() =>
  profil.value?.type === 'club'
    ? (profil.value?.nom ?? '')
    : [profil.value?.prenom, profil.value?.nom].filter(Boolean).join(' '),
)

const typeLabel = computed(() =>
  profil.value?.type === 'club' ? 'Club / Association' : 'Joueur',
)
</script>

<template>
  <div class="page-profil">
    <div class="conteneur page-corps">

      <!-- ── En-tête de page ── -->
      <div class="page-entete">
        <div>
          <span class="page-eyebrow">Mon profil</span>
          <h1 class="page-titre">{{ nomComplet || 'Mon profil' }}</h1>
          <p class="sous-titre">Gérez vos informations personnelles et vos activités sportives</p>
        </div>
      </div>

      <div v-if="chargement" class="chargement">Chargement...</div>

      <template v-else>

        <!-- ── Carte profil principale ── -->
        <section class="carte profil-principale">

          <!-- MODE LECTURE -->
          <template v-if="!modeEdition">
            <div class="profil-banner">
              <div class="profil-avatar-grand" aria-hidden="true">{{ initiales }}</div>

              <div class="profil-identite">
                <span class="tag-type">{{ typeLabel }}</span>
                <div class="profil-info-grille">
                  <div class="champ-affichage" :class="{ 'champ-affichage-pleine': estClub }">
                    <label>{{ estClub ? 'Nom du club' : 'Nom' }}</label>
                    <span>{{ profil?.nom || '—' }}</span>
                  </div>
                  <div v-if="!estClub" class="champ-affichage">
                    <label>Prénom</label>
                    <span>{{ profil?.prenom || '—' }}</span>
                  </div>
                  <div class="champ-affichage">
                    <label>Localisation</label>
                    <span>{{ profil?.localisation || '—' }}</span>
                  </div>
                  <div class="champ-affichage">
                    <label>Membre depuis</label>
                    <span>{{ profil?.dateInscription ? formaterDate(profil.dateInscription) : '—' }}</span>
                  </div>
                </div>
              </div>

              <button class="btn btn-secondaire btn-modifier" @click="ouvrirEdition">
                <Pencil :size="15" aria-hidden="true" />
                Modifier
              </button>
            </div>
          </template>

          <!-- MODE ÉDITION -->
          <template v-else>
            <div class="profil-form-entete">
              <div class="profil-avatar-grand" aria-hidden="true">{{ initiales }}</div>
              <div>
                <h2 class="profil-form-titre">Modifier le profil</h2>
                <p class="profil-form-sous-titre">Mettez à jour vos informations</p>
              </div>
            </div>

            <div v-if="erreurEdition" class="alerte alerte-erreur">{{ erreurEdition }}</div>

            <div class="grille-2">
              <div class="champ-groupe" :class="{ 'champ-groupe-pleine': estClub }">
                <label>{{ estClub ? 'Nom du club' : 'Nom' }}</label>
                <input v-model="formEdition.nom" type="text" class="champ" />
              </div>
              <div v-if="!estClub" class="champ-groupe">
                <label>Prénom</label>
                <input v-model="formEdition.prenom" type="text" class="champ" />
              </div>
            </div>

            <div class="champ-groupe">
              <label>Localisation</label>
              <input
                v-model="formEdition.localisation"
                type="text"
                class="champ"
                placeholder="Paris, Lyon..."
              />
            </div>

            <div class="profil-form-actions">
              <button class="btn btn-primaire" @click="annulerEdition">Enregistrer</button>
              <button class="btn btn-secondaire" @click="annulerEdition">Annuler</button>
            </div>
          </template>

        </section>

        <!-- ── Grid secondaire (sécurité + équipes) ── -->
        <div class="profil-grille-sec">

          <section class="carte section-sec">
            <div class="section-titre-icone">
              <IconeSection :icone="Lock" label="Sécurité" />
              <h2>Sécurité</h2>
            </div>
            <p class="section-desc">Modifiez votre mot de passe pour sécuriser votre compte.</p>
            <button class="btn btn-primaire" disabled>
              <KeyRound :size="16" aria-hidden="true" />
              Changer mot de passe
            </button>
            <p class="bientot">Fonctionnalité à venir</p>
          </section>

          <section v-if="profil?.type === 'club'" class="carte section-sec">
            <div class="section-titre-icone">
              <IconeSection :icone="Users" label="Mes équipes" />
              <h2>Mes équipes</h2>
              <span v-if="mesEquipes.length > 0" class="section-badge">{{ mesEquipes.length }}</span>
            </div>
            <p class="section-desc">
              {{
                mesEquipes.length === 0
                  ? 'Aucune équipe créée pour le moment.'
                  : `${mesEquipes.length} équipe${mesEquipes.length > 1 ? 's' : ''} enregistrée${mesEquipes.length > 1 ? 's' : ''}.`
              }}
            </p>
            <RouterLink to="/mes-equipes" class="btn btn-primaire">
              Gérer mes équipes
            </RouterLink>
          </section>

        </div>

        <!-- ── Carte matchs ── -->
        <section class="carte section-matchs">
          <div class="section-titre-icone">
            <IconeSection :icone="Calendar" label="Mes matchs" />
            <h2>Mes matchs</h2>
            <span v-if="mesMatchs.length > 0" class="section-badge">{{ mesMatchs.length }}</span>
          </div>

          <div v-if="mesMatchs.length === 0" class="vide-section">
            Aucun match créé pour le moment.
          </div>

          <div v-else class="liste-matchs-profil">
            <div
              v-for="match in [...matchsAvenir, ...matchsPasses].slice(0, 6)"
              :key="match.id"
              class="item-match"
              @click="router.push(`/matchs/${match.id}`)"
            >
              <div class="match-info">
                <strong>{{ match.sport?.nom ?? 'Sport' }}</strong>
                <span>{{ formaterDate(match.dateMatch) }}</span>
              </div>
              <span
                class="statut-match"
                :class="new Date(match.dateMatch) >= new Date() ? 'avenir' : 'passe'"
              >
                {{ new Date(match.dateMatch) >= new Date() ? 'À venir' : 'Terminé' }}
              </span>
            </div>
          </div>
        </section>

      </template>
    </div>
  </div>
</template>

<style scoped>
/* ══════════════════════════════════════
   FOND PLEIN-LARGEUR
══════════════════════════════════════ */
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

.page-profil::after {
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
  gap: var(--espace-m);
}

/* ══════════════════════════════════════
   EN-TÊTE DE PAGE
══════════════════════════════════════ */
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
  margin-bottom: 0.15rem;
}

.sous-titre {
  color: var(--couleur-texte-discret);
  font-size: 0.88rem;
}

/* ══════════════════════════════════════
   CARTE (override global, scoped)
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
}

/* ══════════════════════════════════════
   CARTE PROFIL PRINCIPALE
══════════════════════════════════════ */
.profil-principale {
  padding: var(--espace-xl);
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.06s both;
}

/* ── View mode — banner ── */
.profil-banner {
  display: flex;
  align-items: flex-start;
  gap: var(--espace-l);
  flex-wrap: wrap;
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
  flex-shrink: 0;
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

.btn-modifier {
  flex-shrink: 0;
  align-self: flex-start;
}

/* ── Edit mode ── */
.profil-form-entete {
  display: flex;
  align-items: center;
  gap: var(--espace-m);
  margin-bottom: var(--espace-l);
  padding-bottom: var(--espace-m);
  border-bottom: 1px solid var(--couleur-bordure);
}

.profil-form-titre {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--couleur-titre);
  margin-bottom: 0.1rem;
}

.profil-form-sous-titre {
  font-size: 0.82rem;
  color: var(--couleur-texte-discret);
}

.profil-principale .champ:focus {
  border-color: var(--couleur-primaire);
  box-shadow: 0 0 0 3px rgba(154, 230, 0, 0.28), 0 0 0 1px var(--couleur-primaire);
}

.profil-form-actions {
  display: flex;
  gap: var(--espace-s);
  margin-top: var(--espace-l);
  padding-top: var(--espace-m);
  border-top: 1px solid var(--couleur-bordure);
}

/* ══════════════════════════════════════
   GRID SECONDAIRE
══════════════════════════════════════ */
.profil-grille-sec {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: var(--espace-m);
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.12s both;
}

.section-sec {
  padding: var(--espace-l);
}

/* ── En-têtes de section ── */
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
  margin-bottom: var(--espace-m);
}

.bientot {
  font-size: 0.78rem;
  color: var(--couleur-texte-discret);
  text-align: center;
  margin-top: var(--espace-s);
}

button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ══════════════════════════════════════
   SECTION MATCHS
══════════════════════════════════════ */
.section-matchs {
  padding: var(--espace-l);
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.18s both;
}

.vide-section {
  color: var(--couleur-texte-discret);
  font-size: 0.88rem;
  text-align: center;
  padding: var(--espace-l);
}

.liste-matchs-profil {
  display: flex;
  flex-direction: column;
}

.item-match {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.7rem var(--espace-m);
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.15s ease;
  border-bottom: 1px solid var(--couleur-bordure);
}

.item-match:last-child {
  border-bottom: none;
}

.item-match:hover {
  background: var(--couleur-primaire-tres-claire);
}

.match-info {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.match-info strong {
  font-size: 0.9rem;
  color: var(--couleur-titre);
  font-weight: 600;
}

.match-info span {
  font-size: 0.8rem;
  color: var(--couleur-texte-discret);
}

.statut-match {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 0.2rem 0.6rem;
  border-radius: var(--rayon-badge);
  white-space: nowrap;
}

.statut-match.avenir {
  background: var(--couleur-accent-fond);
  color: var(--couleur-accent-texte);
}

.statut-match.passe {
  background: var(--couleur-fond);
  color: var(--couleur-texte-discret);
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
  .profil-principale {
    padding: var(--espace-l);
  }

  .profil-banner {
    gap: var(--espace-m);
  }

  .profil-avatar-grand {
    width: 56px;
    height: 56px;
    font-size: 1.25rem;
  }

  .profil-info-grille {
    grid-template-columns: 1fr 1fr;
  }

  .btn-modifier {
    width: 100%;
    justify-content: center;
  }
}
</style>
