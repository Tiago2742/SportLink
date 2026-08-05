<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { creerEquipe } from '@/services/api'
import { useSports } from '@/composables/useSports'
import { ArrowLeft, Shield, UserPlus, Trophy } from 'lucide-vue-next'
import type { NiveauRef, ErreurApi } from '@/services/api'
import AppSelect from '@/components/form/AppSelect.vue'

const auth = useAuthStore()
const router = useRouter()
const { listeSports, niveauxPour, chargerCatalogue } = useSports()

onMounted(chargerCatalogue)

const sportsCollectifs = computed(() =>
  listeSports.value.filter((s) => s.type === 'collectif'),
)

const form = ref({
  nom: '',
  sportId: '' as number | '',
  niveauId: '' as number | '',
  localisation: '',
  logo: '',
})

const niveauxDisponibles = computed<NiveauRef[]>(() =>
  form.value.sportId !== '' ? niveauxPour(form.value.sportId as number) : [],
)

const erreur = ref('')
const chargement = ref(false)

function onSportChange() {
  form.value.niveauId = ''
}

async function soumettre() {
  erreur.value = ''
  if (!form.value.nom.trim() || form.value.sportId === '') {
    erreur.value = 'Le nom et le sport sont obligatoires.'
    return
  }

  chargement.value = true
  try {
    const equipe = await creerEquipe(auth.token!, {
      nom: form.value.nom.trim(),
      sportId: form.value.sportId,
      niveauId: form.value.niveauId !== '' ? form.value.niveauId : undefined,
      localisation: form.value.localisation.trim() || undefined,
      logo: form.value.logo.trim() || undefined,
    })
    router.push(`/equipes/${equipe.id}`)
  } catch (e) {
    erreur.value =
      (e as Error).message ||
      ((e as ErreurApi).donnees as { erreur?: string })?.erreur ||
      "Impossible de créer l'équipe."
  } finally {
    chargement.value = false
  }
}
</script>

<template>
  <!-- Fond plein-largeur teinté -->
  <div class="page-creer-equipe">
    <div class="conteneur creer-corps">
      <div class="creer-layout">

        <!-- Sidebar éditoriale -->
        <aside class="sidebar-info">
          <span class="sidebar-eyebrow">Nouvelle équipe</span>
          <h2 class="sidebar-titre">Construisez votre équipe</h2>
          <p class="sidebar-desc">
            Créez votre équipe, définissez son niveau et son sport — les joueurs pourront vous rejoindre.
          </p>

          <!-- Avatars décoratifs -->
          <div class="sidebar-avatars">
            <div class="avatar avatar--1"></div>
            <div class="avatar avatar--2"></div>
            <div class="avatar avatar--3"></div>
            <span class="avatar-label">Votre équipe vous attend</span>
          </div>

          <ul class="sidebar-infos">
            <li>
              <span class="info-icone" aria-hidden="true">
                <Shield :size="15" stroke-width="2.25" />
              </span>
              Vous devenez gestionnaire de l'équipe
            </li>
            <li>
              <span class="info-icone" aria-hidden="true">
                <UserPlus :size="15" stroke-width="2.25" />
              </span>
              Invitez des joueurs ou acceptez leurs demandes
            </li>
            <li>
              <span class="info-icone" aria-hidden="true">
                <Trophy :size="15" stroke-width="2.25" />
              </span>
              Organisez des matchs collectifs ensemble
            </li>
          </ul>
        </aside>

        <!-- Formulaire -->
        <div class="formulaire-section">
          <div class="formulaire-entete">
            <RouterLink to="/mes-equipes" class="lien-retour">
              <ArrowLeft :size="15" aria-hidden="true" />
              Mes équipes
            </RouterLink>
            <span class="form-eyebrow">Informations de l'équipe</span>
            <h1 class="formulaire-titre">Créer une équipe</h1>
            <p class="formulaire-sous-titre">
              Une équipe est rattachée à un sport collectif. Vous serez enregistré comme gestionnaire.
            </p>
          </div>

          <form class="formulaire-carte" @submit.prevent="soumettre">
            <div v-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>

            <div class="champ-groupe">
              <label for="nom">Nom de l'équipe <span class="obligatoire">*</span></label>
              <input
                id="nom"
                v-model="form.nom"
                type="text"
                class="champ"
                placeholder="Ex. AS Arras 1ère"
                required
              />
            </div>

            <div class="champ-groupe">
              <label for="sport">Sport <span class="obligatoire">*</span></label>
              <AppSelect
                v-model="form.sportId"
                :options="sportsCollectifs.map(s => ({ value: s.id, label: s.nom }))"
                :searchable="true"
                :clearable="false"
                placeholder="Choisir un sport collectif"
                @change="onSportChange"
              />
            </div>

            <div class="cols-2">
              <div class="champ-groupe">
                <label for="niveau">Niveau</label>
                <AppSelect
                  v-model="form.niveauId"
                  :options="niveauxDisponibles.map((n: NiveauRef) => ({ value: n.id, label: n.libelle }))"
                  :searchable="false"
                  :placeholder="
                    form.sportId === ''
                      ? 'Choisissez d\'abord un sport'
                      : niveauxDisponibles.length === 0
                        ? 'Aucun niveau'
                        : 'Sans niveau précis'
                  "
                  :disabled="form.sportId === '' || niveauxDisponibles.length === 0"
                />
              </div>

              <div class="champ-groupe">
                <label for="localisation">Localisation</label>
                <input
                  id="localisation"
                  v-model="form.localisation"
                  type="text"
                  class="champ"
                  placeholder="Ville, région..."
                />
              </div>
            </div>

            <div class="champ-groupe">
              <label for="logo">Logo <span class="label-optionnel">(URL, optionnel)</span></label>
              <input
                id="logo"
                v-model="form.logo"
                type="url"
                class="champ"
                placeholder="https://..."
              />
            </div>

            <div class="formulaire-actions">
              <button type="submit" class="btn btn-primaire btn-action" :disabled="chargement">
                {{ chargement ? 'Création en cours…' : "Créer l'équipe" }}
              </button>
              <RouterLink to="/mes-equipes" class="btn btn-secondaire btn-action">
                Annuler
              </RouterLink>
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
/* ══════════════════════════════════════
   FOND PLEIN-LARGEUR
══════════════════════════════════════ */
.page-creer-equipe {
  background: #f5f9f5;
  min-height: 100vh;
  position: relative;
  overflow-x: clip;
}

.page-creer-equipe::before {
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

.page-creer-equipe::after {
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

/* ── Avatars décoratifs ── */
.sidebar-avatars {
  display: flex;
  align-items: center;
  margin-bottom: var(--espace-l);
  padding-bottom: var(--espace-m);
  border-bottom: 1px solid var(--couleur-bordure);
}

.avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  border: 2.5px solid #ffffff;
  flex-shrink: 0;
}

.avatar + .avatar {
  margin-left: -10px;
}

.avatar--1 { background: linear-gradient(135deg, #388e3c, #66bb6a); }
.avatar--2 { background: linear-gradient(135deg, #2e7d32, #9ae600); }
.avatar--3 { background: linear-gradient(135deg, #1b5e20, #43a047); }

.avatar-label {
  font-size: 0.76rem;
  color: var(--couleur-texte-discret);
  font-weight: 500;
  margin-left: var(--espace-s);
  line-height: 1.3;
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

.lien-retour {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  margin-bottom: var(--espace-m);
  font-size: 0.85rem;
  font-weight: 500;
  color: var(--couleur-texte-discret);
  text-decoration: none;
  transition: color 0.18s cubic-bezier(0.22, 1, 0.36, 1);
}

.lien-retour:hover {
  color: var(--couleur-primaire);
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
  line-height: 1.5;
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

.cols-2 {
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
  .cols-2 {
    grid-template-columns: 1fr;
  }
  .formulaire-actions {
    flex-direction: column;
  }
  .formulaire-carte {
    padding: var(--espace-l);
  }
}
</style>
