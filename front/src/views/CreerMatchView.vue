<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { creerMatch, chargerEquipes } from '@/services/api'
import { useSports } from '@/composables/useSports'
import type { NiveauRef } from '@/services/api'
import { CircleCheck, Users, Zap } from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()
const { listeSports, niveauxPour, chargerCatalogue } = useSports()

onMounted(chargerCatalogue)

const form = ref({
  sportId:       '' as number | '',
  equipeId:      '' as number | '',
  dateMatch:     '',
  lieu:          '',
  niveauRequisId: '' as number | '',
  description:   '',
})

const equipes = ref<{ id: number; nom: string }[]>([])
const chargementEquipes = ref(false)

const niveauxDisponibles = computed<NiveauRef[]>(() =>
  form.value.sportId !== '' ? niveauxPour(form.value.sportId as number) : [],
)

const sportSelectionne = computed(() =>
  listeSports.value.find((s) => s.id === form.value.sportId),
)

const besoinEquipe = computed(
  () => sportSelectionne.value?.type === 'collectif' && auth.utilisateur?.type === 'club',
)

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
  } catch {
    equipes.value = []
  } finally {
    chargementEquipes.value = false
  }
}

function onSportChange() {
  form.value.niveauRequisId = ''
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
    erreur.value = 'Sélectionnez l\'équipe qui participera à ce match.'
    return
  }
  chargement.value = true
  try {
    const match = await creerMatch(auth.token!, {
      sportId:        form.value.sportId,
      dateMatch:      form.value.dateMatch,
      lieu:           form.value.lieu,
      niveauRequisId: form.value.niveauRequisId !== '' ? form.value.niveauRequisId : undefined,
      description:    form.value.description || undefined,
      equipeId:       form.value.equipeId !== '' ? form.value.equipeId : undefined,
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
  <div class="page-creer conteneur">
    <div class="creer-layout">
      <!-- Sidebar info -->
      <aside class="sidebar-info carte">
        <div class="sidebar-icone">
          <span>+</span>
        </div>
        <h2 class="sidebar-titre">Créer un match</h2>
        <p class="sidebar-sous-titre">Organisez votre partie</p>

        <p class="sidebar-desc">
          Remplissez les informations pour créer un match. Tous les champs marqués * sont
          obligatoires.
        </p>

        <ul class="sidebar-infos">
          <li>
            <span class="info-icone" aria-hidden="true"><CircleCheck :size="18" stroke-width="2.25" /></span>
            Le match sera visible par tous les utilisateurs
          </li>
          <li>
            <span class="info-icone" aria-hidden="true"><Users :size="18" stroke-width="2.25" /></span>
            Vous serez automatiquement participant
          </li>
          <li>
            <span class="info-icone" aria-hidden="true"><Zap :size="18" stroke-width="2.25" /></span>
            Création instantanée après validation
          </li>
        </ul>
      </aside>

      <!-- Formulaire -->
      <div class="formulaire-section">
        <h1 class="formulaire-titre">Créer un nouveau match</h1>
        <p class="formulaire-sous-titre">
          Complétez le formulaire ci-dessous pour organiser votre match sportif
        </p>

        <div class="carte formulaire-carte">
          <div v-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>

          <form @submit.prevent="soumettre">
            <div class="grille-2">
              <div class="champ-groupe">
                <label for="sport">Sport <span class="obligatoire">*</span></label>
                <select id="sport" v-model="form.sportId" class="champ" required @change="onSportChange">
                  <option value="">Choisir un sport</option>
                  <option v-for="sport in listeSports" :key="sport.id" :value="sport.id">{{ sport.nom }}</option>
                </select>
              </div>

              <div class="champ-groupe">
                <label for="dateMatch">Date et Heure <span class="obligatoire">*</span></label>
                <input
                  id="dateMatch"
                  v-model="form.dateMatch"
                  type="datetime-local"
                  class="champ"
                  required
                />
              </div>
            </div>

            <div v-if="besoinEquipe" class="champ-groupe">
              <label for="equipe">Votre équipe <span class="obligatoire">*</span></label>
              <select
                id="equipe"
                v-model="form.equipeId"
                class="champ"
                required
                :disabled="chargementEquipes || equipes.length === 0"
              >
                <option value="">
                  {{
                    chargementEquipes
                      ? 'Chargement…'
                      : equipes.length === 0
                        ? '— aucune équipe pour ce sport —'
                        : 'Choisir une équipe'
                  }}
                </option>
                <option v-for="equipe in equipes" :key="equipe.id" :value="equipe.id">
                  {{ equipe.nom }}
                </option>
              </select>
            </div>

            <div class="champ-groupe">
              <label for="lieu">Lieu <span class="obligatoire">*</span></label>
              <input
                id="lieu"
                v-model="form.lieu"
                type="text"
                class="champ"
                placeholder="Adresse ou terrain"
                required
              />
            </div>

            <div class="champ-groupe">
              <label for="niveauRequis">Niveau requis</label>
              <select
                id="niveauRequis"
                v-model="form.niveauRequisId"
                class="champ"
                :disabled="form.sportId === ''"
              >
                <option value="">
                  {{ form.sportId !== '' ? 'Choisir un niveau' : '— choisir un sport d\'abord —' }}
                </option>
                <option v-for="niveau in niveauxDisponibles" :key="niveau.id" :value="niveau.id">
                  {{ niveau.libelle }}
                </option>
              </select>
            </div>

            <div class="champ-groupe">
              <label for="description">Description (optionnel)</label>
              <textarea
                id="description"
                v-model="form.description"
                class="champ textarea"
                placeholder="Ajoutez des détails sur votre match..."
                rows="4"
              ></textarea>
            </div>

            <div class="formulaire-actions">
              <button
                type="submit"
                class="btn btn-primaire"
                :disabled="chargement"
              >
                + {{ chargement ? 'Création...' : 'Créer match' }}
              </button>
              <button type="button" class="btn btn-secondaire" @click="annuler">
                × Annuler
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page-creer {
  padding-top: var(--espace-xl);
  padding-bottom: var(--espace-xxl);
}

.creer-layout {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: var(--espace-xl);
  align-items: start;
}

.sidebar-info {
  padding: var(--espace-l);
  position: sticky;
  top: 80px;
}

.sidebar-icone {
  width: 52px;
  height: 52px;
  background: var(--couleur-primaire);
  border-radius: var(--rayon-carte);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.6rem;
  font-weight: 700;
  margin-bottom: var(--espace-m);
}

.sidebar-titre {
  font-size: 1.1rem;
  font-weight: 700;
  margin-bottom: 0.2rem;
}

.sidebar-sous-titre {
  color: var(--couleur-texte-discret);
  font-size: 0.85rem;
  margin-bottom: var(--espace-m);
}

.sidebar-desc {
  font-size: 0.85rem;
  color: var(--couleur-texte-discret);
  margin-bottom: var(--espace-l);
  line-height: 1.5;
  padding-bottom: var(--espace-m);
  border-bottom: 1px solid var(--couleur-bordure);
}

.sidebar-infos {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: var(--espace-m);
}

.sidebar-infos li {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: var(--couleur-texte);
}

.info-icone {
  display: inline-flex;
  align-items: center;
  flex-shrink: 0;
  color: var(--couleur-primaire);
}

.formulaire-titre {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 0.3rem;
}

.formulaire-sous-titre {
  color: var(--couleur-texte-discret);
  font-size: 0.9rem;
  margin-bottom: var(--espace-l);
}

.formulaire-carte {
  padding: var(--espace-xl);
}

.textarea {
  resize: vertical;
  min-height: 100px;
}

.formulaire-actions {
  display: flex;
  gap: var(--espace-m);
  margin-top: var(--espace-m);
}

.formulaire-actions .btn {
  flex: 1;
  padding: 0.85rem;
  font-size: 1rem;
}

button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

@media (max-width: 900px) {
  .creer-layout {
    grid-template-columns: 1fr;
  }

  .sidebar-info {
    position: static;
  }
}
</style>
