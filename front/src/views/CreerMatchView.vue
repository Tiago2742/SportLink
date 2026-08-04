<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { creerMatch, chargerEquipes } from '@/services/api'
import { CircleCheck, Users, Zap } from 'lucide-vue-next'
import ChampLieuAutoComplete from '@/components/form/ChampLieuAutoComplete.vue'
import AppSelect from '@/components/form/AppSelect.vue'

const auth = useAuthStore()
const router = useRouter()

// Filtre par type : joueur → sports individuels, club → sports collectifs
const sportsDeclares = computed(() =>
  (auth.utilisateur?.niveaux ?? [])
    .map((un: any) => un.sport)
    .filter(Boolean)
    .filter((s: any) =>
      auth.utilisateur?.type === 'joueur' ? s.type === 'individuel' : s.type === 'collectif',
    ),
)

const form = ref({
  sportId:     '' as number | '',
  equipeId:    '' as number | '',
  dateMatch:   '',
  lieu:        '',
  latitude:    null as number | null,
  longitude:   null as number | null,
  description: '',
})

const equipes = ref<{ id: number; nom: string }[]>([])
const chargementEquipes = ref(false)

const sportSelectionne = computed(() =>
  sportsDeclares.value.find((s) => s.id === form.value.sportId),
)

const besoinEquipe = computed(
  () => sportSelectionne.value?.type === 'collectif' && auth.utilisateur?.type === 'club',
)

// Min pour datetime-local : heure locale courante (sans secondes)
const dateMinimale = computed(() => {
  const now = new Date()
  now.setSeconds(0, 0)
  return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16)
})

async function chargerEquipesDuClub() {
  form.value.equipeId = ''
  if (!besoinEquipe.value || form.value.sportId === '') {
    equipes.value = []
    return
  }
  chargementEquipes.value = true
  try {
    equipes.value = await chargerEquipes(auth.token!, {
      sportId: form.value.sportId,
      clubId: auth.utilisateur.id,
    })
    // Présélectionner la première équipe disponible
    if (equipes.value.length > 0) {
      form.value.equipeId = equipes.value[0].id
    }
  } catch {
    equipes.value = []
  } finally {
    chargementEquipes.value = false
  }
}

function onSportChange() {
  chargerEquipesDuClub()
}

const erreur = ref('')
const chargement = ref(false)

async function soumettre() {
  erreur.value = ''
  if (!form.value.sportId || !form.value.dateMatch || !form.value.lieu) {
    erreur.value = 'Veuillez remplir tous les champs obligatoires.'
    return
  }
  if (sportSelectionne.value?.type === 'collectif' && auth.utilisateur?.type !== 'club') {
    erreur.value = 'Seul un compte club peut créer un match de sport collectif.'
    return
  }
  if (besoinEquipe.value && form.value.equipeId === '') {
    erreur.value = "Sélectionnez l'équipe qui participera à ce match."
    return
  }
  // Convertir l'heure locale en UTC avant envoi (évite les décalages de timezone)
  const dateUTC = new Date(form.value.dateMatch).toISOString().slice(0, -5) + 'Z'

  chargement.value = true
  try {
    const match = await creerMatch(auth.token!, {
      sportId:     form.value.sportId,
      dateMatch:   dateUTC,
      lieu:        form.value.lieu,
      latitude:    form.value.latitude,
      longitude:   form.value.longitude,
      description: form.value.description || undefined,
      equipeId:    form.value.equipeId !== '' ? form.value.equipeId : undefined,
    })
    router.push(`/matchs/${match.id}`)
  } catch (e: any) {
    if (e.statut === 400) {
      erreur.value = 'Données invalides. Vérifiez les champs.'
    } else if (e.statut === 422) {
      erreur.value = (e as Error).message
    } else {
      erreur.value = 'Impossible de créer le match. Réessayez.'
    }
  } finally {
    chargement.value = false
  }
}

function annuler() {
  router.push('/')
}
</script>

<template>
  <!-- Fond plein-largeur teinté -->
  <div class="page-creer">
    <div class="conteneur creer-corps">
      <div class="creer-layout">

        <!-- Sidebar éditoriale -->
        <aside class="sidebar-info">
          <span class="sidebar-eyebrow">Nouveau match</span>
          <h2 class="sidebar-titre">Créer un match</h2>
          <p class="sidebar-desc">
            Remplissez les informations pour créer un match. Les champs marqués
            <span class="obligatoire">*</span> sont obligatoires.
          </p>
          <ul class="sidebar-infos">
            <li>
              <span class="info-icone" aria-hidden="true">
                <CircleCheck :size="15" stroke-width="2.25" />
              </span>
              Le match sera visible par tous les utilisateurs
            </li>
            <li>
              <span class="info-icone" aria-hidden="true">
                <Users :size="15" stroke-width="2.25" />
              </span>
              Vous serez automatiquement participant
            </li>
            <li>
              <span class="info-icone" aria-hidden="true">
                <Zap :size="15" stroke-width="2.25" />
              </span>
              Création instantanée après validation
            </li>
          </ul>
        </aside>

        <!-- Formulaire -->
        <div class="formulaire-section">
          <div class="formulaire-entete">
            <span class="form-eyebrow">Informations du match</span>
            <h1 class="formulaire-titre">Créer un nouveau match</h1>
            <p class="formulaire-sous-titre">
              Complétez le formulaire pour organiser votre match sportif
            </p>
          </div>

          <div class="formulaire-carte">
            <div v-if="sportsDeclares.length === 0" class="alerte alerte-info">
              Déclarez des sports dans votre
              <router-link to="/profil">profil</router-link>
              pour créer un match.
            </div>

            <template v-else>
            <div v-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>

            <form @submit.prevent="soumettre">
              <div class="grille-champs">
                <div class="champ-groupe">
                  <label for="sport">Sport <span class="obligatoire">*</span></label>
                  <AppSelect
                    v-model="form.sportId"
                    :options="(sportsDeclares as any[]).map((s) => ({ value: s.id, label: s.nom }))"
                    :searchable="true"
                    :clearable="false"
                    placeholder="Choisir un sport"
                    @change="onSportChange"
                  />
                </div>

                <div class="champ-groupe">
                  <label for="dateMatch">Date et heure <span class="obligatoire">*</span></label>
                  <input
                    id="dateMatch"
                    v-model="form.dateMatch"
                    type="datetime-local"
                    class="champ"
                    :min="dateMinimale"
                    required
                  />
                </div>
              </div>

              <div v-if="besoinEquipe" class="champ-groupe">
                <label for="equipe">Votre équipe <span class="obligatoire">*</span></label>
                <AppSelect
                  v-model="form.equipeId"
                  :options="equipes.map(e => ({ value: e.id, label: e.nom }))"
                  :searchable="true"
                  :placeholder="chargementEquipes ? 'Chargement…' : 'Choisir une équipe'"
                  :disabled="chargementEquipes || equipes.length === 0"
                  :loading="chargementEquipes"
                />
                <p
                  v-if="!chargementEquipes && equipes.length === 0 && form.sportId !== ''"
                  class="avertissement-equipe"
                >
                  Aucune équipe de {{ sportSelectionne?.nom }} trouvée.
                  <router-link to="/equipes/creer">Créer une équipe →</router-link>
                </p>
              </div>

              <div class="champ-groupe">
                <label for="lieu">Lieu <span class="obligatoire">*</span></label>
                <ChampLieuAutoComplete
                  v-model:lieu="form.lieu"
                  v-model:latitude="form.latitude"
                  v-model:longitude="form.longitude"
                  :required="true"
                />
              </div>

              <div class="champ-groupe">
                <label for="description">Description <span class="label-optionnel">(optionnel)</span></label>
                <textarea
                  id="description"
                  v-model="form.description"
                  class="champ textarea"
                  placeholder="Ajoutez des détails sur votre match..."
                  rows="3"
                ></textarea>
              </div>

              <div class="formulaire-actions">
                <button type="submit" class="btn btn-primaire btn-action" :disabled="chargement">
                  {{ chargement ? 'Création en cours…' : 'Créer le match' }}
                </button>
                <button type="button" class="btn btn-secondaire btn-action" @click="annuler">
                  Annuler
                </button>
              </div>
            </form>
            </template>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
/* ══════════════════════════════════════
   FOND PLEIN-LARGEUR
══════════════════════════════════════ */
.page-creer {
  background: #f5f9f5;
  min-height: 100vh;
  position: relative;
  overflow-x: clip;
}

/* Orbe décoratif haut-droit */
.page-creer::before {
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

/* Orbe décoratif bas-gauche */
.page-creer::after {
  content: '';
  position: absolute;
  bottom: -100px;
  left: -100px;
  width: 320px;
  height: 320px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(46, 125, 50, 0.07) 0%, transparent 60%);
  pointer-events: none;
}

.creer-corps {
  padding-top: var(--espace-xl);
  padding-bottom: var(--espace-xxl);
  position: relative;
  z-index: 1;
}

/* ══════════════════════════════════════
   LAYOUT 2 COLONNES
══════════════════════════════════════ */
.creer-layout {
  display: grid;
  grid-template-columns: 252px 1fr;
  gap: var(--espace-xl);
  align-items: start;
}

/* ══════════════════════════════════════
   SIDEBAR ÉDITORIALE
══════════════════════════════════════ */
.sidebar-info {
  background: #ffffff;
  border: 1.5px solid #dde8dd;
  border-radius: var(--rayon-carte);
  padding: var(--espace-l);
  position: sticky;
  top: 80px;
  overflow: hidden;
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.05s both;
}

.sidebar-info::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: var(--degrade-primaire);
}

.sidebar-eyebrow {
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

.sidebar-titre {
  font-size: 1.15rem;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: var(--couleur-titre);
  margin-bottom: 0.2rem;
}

.sidebar-desc {
  font-size: 0.83rem;
  color: var(--couleur-texte-discret);
  line-height: 1.5;
  margin-bottom: var(--espace-l);
  padding-bottom: var(--espace-m);
  border-bottom: 1px solid var(--couleur-bordure);
}

.sidebar-infos {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.8rem;
}

.sidebar-infos li {
  display: flex;
  align-items: flex-start;
  gap: 0.55rem;
  font-size: 0.83rem;
  color: var(--couleur-texte);
  line-height: 1.4;
}

.info-icone {
  display: inline-flex;
  align-items: center;
  flex-shrink: 0;
  color: var(--couleur-primaire);
  margin-top: 1px;
}

/* ══════════════════════════════════════
   EN-TÊTE FORMULAIRE
══════════════════════════════════════ */
.formulaire-section {
  animation: fadeUp 0.55s cubic-bezier(0.22, 1, 0.36, 1) 0.15s both;
}

.formulaire-entete {
  margin-bottom: var(--espace-l);
}

.form-eyebrow {
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

.formulaire-titre {
  font-size: 1.55rem;
  font-weight: 800;
  letter-spacing: -0.025em;
  color: var(--couleur-titre);
  margin-bottom: 0.2rem;
}

.formulaire-sous-titre {
  font-size: 0.88rem;
  color: var(--couleur-texte-discret);
}

/* ══════════════════════════════════════
   CARTE FORMULAIRE
══════════════════════════════════════ */
.formulaire-carte {
  background: #ffffff;
  border: 1.5px solid #dde8dd;
  border-radius: var(--rayon-carte);
  padding: var(--espace-xl) var(--espace-xl) var(--espace-l);
  position: relative;
}

.formulaire-carte::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: var(--degrade-primaire);
}

.formulaire-carte .champ-groupe label {
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--couleur-titre);
  letter-spacing: 0.01em;
}

.formulaire-carte .champ:focus {
  border-color: var(--couleur-primaire);
  box-shadow: 0 0 0 3px rgba(154, 230, 0, 0.28), 0 0 0 1px var(--couleur-primaire);
}

.grille-champs {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--espace-m);
}

.label-optionnel {
  font-weight: 400;
  color: var(--couleur-texte-discret);
  font-size: 0.78rem;
  margin-left: 0.25rem;
}

.textarea {
  resize: vertical;
  min-height: 72px;
  font-family: inherit;
}

.formulaire-actions {
  display: flex;
  gap: var(--espace-m);
  margin-top: var(--espace-l);
  padding-top: var(--espace-m);
  border-top: 1px solid var(--couleur-bordure);
}

.btn-action {
  flex: 1;
  padding: 0.72rem var(--espace-m);
  font-size: 0.95rem;
}

button:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.avertissement-equipe {
  margin-top: 0.45rem;
  font-size: 0.82rem;
  color: var(--couleur-texte-discret);
}

.avertissement-equipe a {
  color: var(--couleur-primaire-fonce, #2e7d32);
  font-weight: 600;
  text-decoration: underline;
  margin-left: 0.3rem;
}

.alerte-info {
  background: #eff6ff;
  border: 1.5px solid #bfdbfe;
  color: #1e40af;
  border-radius: 8px;
  padding: 0.85rem 1rem;
  font-size: 0.88rem;
  margin-bottom: var(--espace-m);
}

.alerte-info a {
  color: #1d4ed8;
  font-weight: 600;
  text-decoration: underline;
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
@media (max-width: 900px) {
  .creer-layout {
    grid-template-columns: 1fr;
  }
  .sidebar-info {
    position: static;
  }
  .formulaire-titre {
    font-size: 1.35rem;
  }
}

@media (max-width: 560px) {
  .grille-champs {
    grid-template-columns: 1fr;
  }
  .formulaire-actions {
    flex-direction: column;
  }
}
</style>
