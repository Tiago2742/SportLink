<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  chargerMesMatchs,
  chargerEquipes,
  chargerProfil,
  mettreAJourLogo,
  ajouterSportNiveau,
  modifierNiveauSport,
  supprimerCompte,
} from '@/services/api'
import { useSports } from '@/composables/useSports'
import IconeSection from '@/components/ui/IconeSection.vue'
import SelecteurSportNiveau from '@/components/form/SelecteurSportNiveau.vue'
import type { EntreeSportNiveau } from '@/components/form/SelecteurSportNiveau.vue'
import { AlertTriangle, Calendar, Image, KeyRound, Lock, Pencil, Star, Trash2, Trophy, Users } from 'lucide-vue-next'

const auth    = useAuthStore()
const router  = useRouter()
const { niveauxPour } = useSports()

const profil      = ref<any>(auth.utilisateur)
const mesEquipes  = ref<any[]>([])
const mesMatchs   = ref<any[]>([])
const chargement  = ref(true)

// ── Édition profil ────────────────────────────────────────────────────────────
const modeEdition   = ref(false)
const formEdition   = ref({ nom: '', prenom: '', localisation: '' })
const erreurEdition = ref('')

// ── Logo club ─────────────────────────────────────────────────────────────────
const modeEditionLogo = ref(false)
const formLogo        = ref('')
const enregistreLogo  = ref(false)
const erreurLogo      = ref('')

// ── Sports/niveaux ────────────────────────────────────────────────────────────
const sportEnCoursEdit = ref<Record<number, { ouvert: boolean; niveauId: number | '' }>>({})
const enregistreSport  = ref<Record<number, boolean>>({})
const erreurSport      = ref('')
const ajoutEnCours     = ref(false)

onMounted(async () => {
  const userId = auth.utilisateur?.id
  if (!userId) { chargement.value = false; return }
  try {
    const [profilFrais, matchs, equipes] = await Promise.all([
      chargerProfil(auth.token!),
      chargerMesMatchs(auth.token!),
      chargerEquipes(auth.token!, { clubId: userId }),
    ])
    profil.value = profilFrais
    auth.rafraichirProfil(profilFrais)
    mesMatchs.value  = matchs
    mesEquipes.value = equipes
  } finally {
    chargement.value = false
  }
})

// ── Computed ──────────────────────────────────────────────────────────────────
const estClub = computed(() => profil.value?.type === 'club')

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

const matchsAvenir = computed(() =>
  mesMatchs.value.filter((m) => new Date(m.dateMatch) >= new Date()),
)
const matchsPasses = computed(() =>
  mesMatchs.value.filter((m) => new Date(m.dateMatch) < new Date()),
)

// IDs des sports déjà déclarés par l'utilisateur
const sportsDeclaresIds = computed<number[]>(() =>
  (profil.value?.niveaux ?? []).map((un: any) => un.sport?.id),
)

// ── Profil édition ────────────────────────────────────────────────────────────
function ouvrirEdition() {
  formEdition.value = {
    nom:         profil.value?.nom || '',
    prenom:      profil.value?.prenom || '',
    localisation: profil.value?.localisation || '',
  }
  modeEdition.value = true
}
function annulerEdition() { modeEdition.value = false; erreurEdition.value = '' }

// ── Logo ──────────────────────────────────────────────────────────────────────
function ouvrirEditionLogo() {
  formLogo.value       = profil.value?.logo || ''
  erreurLogo.value     = ''
  modeEditionLogo.value = true
}
function annulerEditionLogo() { modeEditionLogo.value = false; erreurLogo.value = '' }

async function sauvegarderLogo() {
  if (enregistreLogo.value) return
  enregistreLogo.value = true
  erreurLogo.value     = ''
  try {
    const mis = await mettreAJourLogo(auth.token!, formLogo.value.trim() || null)
    profil.value         = mis
    auth.rafraichirProfil(mis)
    modeEditionLogo.value = false
  } catch (e: any) {
    erreurLogo.value = e.message || 'Impossible de mettre à jour le logo.'
  } finally {
    enregistreLogo.value = false
  }
}

// ── Sports / niveaux ──────────────────────────────────────────────────────────
function ouvrirChangementNiveau(unId: number, niveauActuelId: number) {
  sportEnCoursEdit.value[unId] = { ouvert: true, niveauId: niveauActuelId }
}
function annulerChangementNiveau(unId: number) {
  delete sportEnCoursEdit.value[unId]
}

async function sauvegarderNiveau(un: any) {
  const edit = sportEnCoursEdit.value[un.id]
  if (!edit || edit.niveauId === '') return
  enregistreSport.value[un.id] = true
  erreurSport.value = ''
  try {
    const mis = await modifierNiveauSport(auth.token!, un.sport.id, edit.niveauId as number)
    profil.value = mis
    auth.rafraichirProfil(mis)
    delete sportEnCoursEdit.value[un.id]
  } catch (e: any) {
    erreurSport.value = e.message || 'Impossible de modifier le niveau.'
  } finally {
    delete enregistreSport.value[un.id]
  }
}

async function onAjoutSport(liste: EntreeSportNiveau[]) {
  // Le composant émet la liste complète ; la dernière entrée est l'ajout
  const derniere = liste.at(-1)
  if (!derniere) return

  // Ignore si déjà dans la liste (sport déjà déclaré avec même niveau)
  const existant = (profil.value?.niveaux ?? []).find(
    (un: any) => un.sport?.id === derniere.sportId && un.niveau?.id === derniere.niveauId,
  )
  if (existant) return

  ajoutEnCours.value = true
  erreurSport.value  = ''
  try {
    const mis = await ajouterSportNiveau(auth.token!, derniere.sportId, derniere.niveauId)
    profil.value = mis
    auth.rafraichirProfil(mis)
  } catch (e: any) {
    erreurSport.value = e.message || 'Impossible d\'ajouter ce sport.'
  } finally {
    ajoutEnCours.value = false
  }
}

// ── Suppression de compte ─────────────────────────────────────────────────────
const showModaleSupression  = ref(false)
const suppressionEnCours    = ref(false)
const suppressionErreur     = ref('')

function ouvrirModaleSupression() {
  suppressionErreur.value = ''
  showModaleSupression.value = true
}
function fermerModaleSupression() {
  if (suppressionEnCours.value) return
  showModaleSupression.value = false
}

async function confirmerSuppression() {
  if (suppressionEnCours.value) return
  suppressionEnCours.value = true
  suppressionErreur.value  = ''
  try {
    await supprimerCompte(auth.token!)
    auth.seDeconnecter()
    router.push('/connexion')
  } catch (e: any) {
    suppressionErreur.value = e.message || 'Une erreur est survenue.'
    suppressionEnCours.value = false
  }
}

// ── Utilitaires ───────────────────────────────────────────────────────────────
function formaterDate(dateStr: string) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('fr-FR', {
    day: 'numeric', month: 'long', year: 'numeric',
  })
}
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

              <!-- Avatar / Logo -->
              <div class="profil-avatar-zone">
                <template v-if="estClub">
                  <!-- Logo existant -->
                  <template v-if="profil?.logo && !modeEditionLogo">
                    <button class="logo-btn" @click="ouvrirEditionLogo" title="Modifier le logo">
                      <img :src="profil.logo" :alt="nomComplet" class="profil-logo" />
                      <span class="logo-overlay"><Pencil :size="14" /></span>
                    </button>
                  </template>
                  <!-- Placeholder logo -->
                  <template v-else-if="!modeEditionLogo">
                    <button class="logo-btn logo-btn--vide" @click="ouvrirEditionLogo" title="Ajouter un logo">
                      <span class="logo-initiales">{{ initiales }}</span>
                      <span class="logo-overlay logo-overlay--vide">
                        <Image :size="14" />
                        <span class="logo-overlay-texte">Logo</span>
                      </span>
                    </button>
                  </template>
                  <!-- Formulaire inline logo -->
                  <div v-if="modeEditionLogo" class="logo-form">
                    <p class="logo-form-label">URL du logo</p>
                    <input
                      v-model="formLogo"
                      type="url"
                      class="champ"
                      placeholder="https://..."
                      @keyup.enter="sauvegarderLogo"
                    />
                    <div v-if="erreurLogo" class="alerte alerte-erreur alerte-sm">{{ erreurLogo }}</div>
                    <div class="logo-form-actions">
                      <button
                        class="btn btn-primaire btn-sm"
                        :disabled="enregistreLogo"
                        @click="sauvegarderLogo"
                      >{{ enregistreLogo ? '...' : 'Enregistrer' }}</button>
                      <button class="btn btn-secondaire btn-sm" @click="annulerEditionLogo">Annuler</button>
                    </div>
                  </div>
                </template>

                <!-- Joueur : avatar classique -->
                <div v-else class="profil-avatar-grand" aria-hidden="true">{{ initiales }}</div>
              </div>

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

          <!-- MODE ÉDITION profil -->
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

        <!-- ── Carte Sports pratiqués ── -->
        <section class="carte section-sports">
          <div class="section-titre-icone">
            <IconeSection :icone="Trophy" label="Sports" />
            <h2>Sports pratiqués</h2>
            <span v-if="profil?.niveaux?.length" class="section-badge">{{ profil.niveaux.length }}</span>
          </div>

          <div v-if="erreurSport" class="alerte alerte-erreur">{{ erreurSport }}</div>

          <!-- Liste des sports déjà déclarés -->
          <div v-if="profil?.niveaux?.length" class="sports-liste">
            <div v-for="un in profil.niveaux" :key="un.id" class="sport-ligne">
              <div class="sport-ligne-info">
                <span class="sport-ligne-nom">{{ un.sport?.nom }}</span>
                <template v-if="!sportEnCoursEdit[un.id]">
                  <span class="sport-ligne-niveau">{{ un.niveau?.libelle }}</span>
                  <button
                    class="btn-changer-niveau"
                    @click="ouvrirChangementNiveau(un.id, un.niveau?.id)"
                  >Changer niveau</button>
                </template>
                <template v-else>
                  <select
                    v-model="sportEnCoursEdit[un.id].niveauId"
                    class="champ champ-niveau-inline"
                  >
                    <option value="">Choisir un niveau</option>
                    <option
                      v-for="niv in niveauxPour(un.sport?.id)"
                      :key="niv.id"
                      :value="niv.id"
                    >{{ niv.libelle }}</option>
                  </select>
                  <button
                    class="btn btn-primaire btn-sm"
                    :disabled="!sportEnCoursEdit[un.id]?.niveauId || !!enregistreSport[un.id]"
                    @click="sauvegarderNiveau(un)"
                  >{{ enregistreSport[un.id] ? '...' : 'OK' }}</button>
                  <button class="btn btn-secondaire btn-sm" @click="annulerChangementNiveau(un.id)">✕</button>
                </template>
              </div>
            </div>
          </div>
          <p v-else class="section-desc">Aucun sport déclaré pour le moment.</p>

          <!-- Ajout d'un nouveau sport -->
          <div class="sports-ajout">
            <p class="sports-ajout-label">Ajouter un sport</p>
            <SelecteurSportNiveau
              :model-value="[]"
              :masquer-retrait="true"
              :filtre-type="estClub ? 'collectif' : undefined"
              @update:model-value="onAjoutSport"
            />
            <p v-if="ajoutEnCours" class="sports-ajout-info">Enregistrement…</p>
            <p v-if="estClub" class="sports-ajout-info">
              Les clubs ne peuvent déclarer que des sports collectifs.
            </p>
          </div>
        </section>

        <!-- ── Carte Réputation ── -->
        <section class="carte section-reputation">
          <div class="section-titre-icone">
            <IconeSection :icone="Star" label="Réputation" />
            <h2>Réputation</h2>
            <span v-if="profil?.reputation?.total" class="section-badge">
              {{ profil.reputation.total }} avis
            </span>
          </div>

          <template v-if="profil?.reputation">
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
            <p class="reputation-sous-desc">Les évaluations apparaîtront ici après vos matchs terminés.</p>
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

            <div class="zone-danger">
              <p class="zone-danger-titre">Zone de danger</p>
              <p class="section-desc">
                La suppression efface définitivement vos données personnelles.
                Votre historique de matchs reste conservé de manière anonymisée.
              </p>
              <button class="btn btn-danger" @click="ouvrirModaleSupression">
                <Trash2 :size="15" aria-hidden="true" />
                Supprimer mon compte
              </button>
            </div>
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

  <!-- ── Modale de confirmation de suppression ── -->
  <Teleport to="body">
    <div
      v-if="showModaleSupression"
      class="overlay-suppr"
      role="dialog"
      aria-modal="true"
      aria-labelledby="modale-suppr-titre"
      @click.self="fermerModaleSupression"
    >
      <div class="modale-suppr">
        <div class="modale-suppr-icone">
          <AlertTriangle :size="28" />
        </div>
        <h2 id="modale-suppr-titre" class="modale-suppr-titre">Supprimer mon compte</h2>
        <p class="modale-suppr-texte">
          Cette action est <strong>irréversible</strong>. Vos données personnelles
          (nom, prénom, localisation, sports déclarés) seront définitivement effacées.
          Votre historique de matchs et vos messages seront conservés de manière anonymisée.
        </p>
        <div v-if="suppressionErreur" class="alerte alerte-erreur">{{ suppressionErreur }}</div>
        <div class="modale-suppr-actions">
          <button
            class="btn btn-danger"
            :disabled="suppressionEnCours"
            @click="confirmerSuppression"
          >
            {{ suppressionEnCours ? 'Suppression en cours…' : 'Oui, supprimer mon compte' }}
          </button>
          <button
            class="btn btn-secondaire"
            :disabled="suppressionEnCours"
            @click="fermerModaleSupression"
          >
            Annuler
          </button>
        </div>
      </div>
    </div>
  </Teleport>
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
  flex-shrink: 0;
}

/* ── Logo club ── */
.logo-btn {
  position: relative;
  width: 72px;
  height: 72px;
  border-radius: 50%;
  border: 2px solid var(--couleur-bordure);
  overflow: hidden;
  cursor: pointer;
  background: none;
  padding: 0;
  display: block;
}

.logo-btn:hover .logo-overlay {
  opacity: 1;
}

.profil-logo {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.logo-initiales {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  background: var(--degrade-primaire);
  color: white;
  font-size: 1.6rem;
  font-weight: 800;
}

.logo-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.18s ease;
  border-radius: 50%;
}

.logo-overlay--vide {
  flex-direction: column;
  gap: 2px;
  font-size: 0.6rem;
  font-weight: 600;
}

.logo-overlay-texte {
  font-size: 0.58rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.logo-btn--vide {
  border-style: dashed;
  border-color: var(--couleur-primaire-claire);
}

.logo-form {
  display: flex;
  flex-direction: column;
  gap: var(--espace-xs);
  width: 260px;
}

.logo-form-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--couleur-texte-discret);
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.logo-form-actions {
  display: flex;
  gap: var(--espace-xs);
}

.btn-sm {
  padding: 0.3rem 0.7rem;
  font-size: 0.8rem;
}

.alerte-sm {
  font-size: 0.8rem;
  padding: 0.35rem 0.6rem;
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
   SECTION SPORTS
══════════════════════════════════════ */
.section-sports {
  padding: var(--espace-l);
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.10s both;
}

.sports-liste {
  display: flex;
  flex-direction: column;
  gap: 0;
  margin-bottom: var(--espace-l);
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
  flex: 1;
}

.btn-changer-niveau {
  background: none;
  border: 1px solid var(--couleur-bordure);
  border-radius: var(--rayon-bouton);
  padding: 0.22rem 0.6rem;
  font-size: 0.78rem;
  color: var(--couleur-texte-discret);
  cursor: pointer;
  transition: border-color 0.15s, color 0.15s;
  white-space: nowrap;
}

.btn-changer-niveau:hover {
  border-color: var(--couleur-primaire);
  color: var(--couleur-primaire);
}

.champ-niveau-inline {
  flex: 1;
  min-width: 140px;
  padding: 0.3rem 0.5rem;
  font-size: 0.85rem;
}

.sports-ajout {
  padding-top: var(--espace-m);
  border-top: 1px solid var(--couleur-bordure);
  margin-top: var(--espace-s);
}

.sports-ajout-label {
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--couleur-texte-discret);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin-bottom: var(--espace-s);
}

.sports-ajout-info {
  font-size: 0.78rem;
  color: var(--couleur-texte-discret);
  margin-top: var(--espace-xs);
  font-style: italic;
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

.item-match:last-child { border-bottom: none; }
.item-match:hover { background: var(--couleur-primaire-tres-claire); }

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
  .profil-principale { padding: var(--espace-l); }
  .profil-banner { gap: var(--espace-m); }
  .profil-avatar-grand { width: 56px; height: 56px; font-size: 1.25rem; }
  .logo-btn { width: 56px; height: 56px; }
  .profil-info-grille { grid-template-columns: 1fr 1fr; }
  .btn-modifier { width: 100%; justify-content: center; }
  .logo-form { width: 100%; }
  .sport-ligne-info { flex-wrap: wrap; }
}

/* ══════════════════════════════════════
   SECTION RÉPUTATION
══════════════════════════════════════ */
.section-reputation {
  padding: var(--espace-l);
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.11s both;
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

.reputation-sous-desc {
  font-size: 0.82rem;
  color: var(--couleur-texte-discret);
  font-style: italic;
  margin-top: 0.25rem;
}

/* ══════════════════════════════════════
   ZONE DE DANGER
══════════════════════════════════════ */
.zone-danger {
  margin-top: var(--espace-l);
  padding-top: var(--espace-m);
  border-top: 1px solid #fde8e8;
}

.zone-danger-titre {
  font-size: 0.72rem;
  font-weight: 700;
  color: #c0392b;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  margin-bottom: var(--espace-xs);
}

.btn-danger {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: #fff0f0;
  color: #c0392b;
  border: 1.5px solid #f5c6c6;
  border-radius: var(--rayon-bouton);
  padding: 0.5rem 1rem;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.18s cubic-bezier(0.22,1,0.36,1), border-color 0.18s;
}

.btn-danger:hover:not(:disabled) {
  background: #ffe0e0;
  border-color: #c0392b;
}

.btn-danger:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ══════════════════════════════════════
   MODALE SUPPRESSION
══════════════════════════════════════ */
.overlay-suppr {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 900;
  padding: var(--espace-m);
  animation: fadeIn 0.18s ease both;
}

.modale-suppr {
  background: #fff;
  border-radius: 16px;
  padding: var(--espace-xl);
  max-width: 440px;
  width: 100%;
  box-shadow: 0 24px 64px rgba(0, 0, 0, 0.18);
  animation: slideUp 0.22s cubic-bezier(0.22,1,0.36,1) both;
}

.modale-suppr-icone {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: #fff0f0;
  color: #c0392b;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: var(--espace-m);
}

.modale-suppr-titre {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--couleur-titre);
  margin-bottom: var(--espace-s);
}

.modale-suppr-texte {
  font-size: 0.88rem;
  color: var(--couleur-texte-discret);
  line-height: 1.6;
  margin-bottom: var(--espace-l);
}

.modale-suppr-texte strong {
  color: #c0392b;
}

.modale-suppr-actions {
  display: flex;
  flex-direction: column;
  gap: var(--espace-s);
}

.modale-suppr-actions .btn-danger,
.modale-suppr-actions .btn-secondaire {
  width: 100%;
  justify-content: center;
  padding: 0.65rem 1rem;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}
</style>
