<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  chargerMatch,
  supprimerMatch,
  ajouterCamp,
  supprimerCamp,
  repondreCamp,
  chargerMessages,
  envoyerMessage,
  chargerResultat,
  saisirResultat,
  chargerEquipes,
} from '@/services/api'
import BadgeStatut from '@/components/commun/BadgeStatut.vue'
import { nomParticipant, campParRole, utilisateurEstInscrit } from '@/composables/useMatchCamps'
import AvatarEquipe from '@/components/equipes/AvatarEquipe.vue'
import { initialesUtilisateur, nomAffichage } from '@/utils/nomAffichage'
import { ArrowLeft, Calendar, FileText, MapPin, Target, User } from 'lucide-vue-next'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const matchId = Number(route.params.id)
const match = ref<any>(null)
const messages = ref<any[]>([])
const resultat = ref<any>(null)
const chargement = ref(true)
const erreur = ref('')

const nouveauMessage = ref('')
const envoi = ref(false)

const scoreCamp1 = ref<number | null>(null)
const scoreCamp2 = ref<number | null>(null)
const saisieResultat = ref(false)

const equipesDuClub = ref<{ id: number; nom: string }[]>([])
const equipeSelectionnee = ref<number | ''>('')
const chargementEquipes = ref(false)

onMounted(charger)

async function charger() {
  chargement.value = true
  try {
    const matchCharge = await chargerMatch(auth.token!, matchId)
    match.value = matchCharge

    await chargerEquipesDuMatch(matchCharge)

    const participant = utilisateurEstInscrit(matchCharge, auth.utilisateur?.id)

    const [res, msgs] = await Promise.all([
      chargerResultat(auth.token!, matchId).catch(() => null),
      participant ? chargerMessages(auth.token!, matchId).catch(() => []) : Promise.resolve([]),
    ])
    resultat.value = res
    messages.value = msgs ?? []
  } catch (e: any) {
    erreur.value = e.statut === 404 ? 'Match introuvable.' : 'Erreur de chargement.'
  } finally {
    chargement.value = false
  }
}

const estCreateur = computed(
  () => Number(match.value?.createur?.id) === Number(auth.utilisateur?.id),
)

const estParticipantLocal = computed(() => {
  if (!match.value || !auth.utilisateur) return false
  return estCreateur.value || dejaInscrit.value
})

const sportCollectif = computed(() => match.value?.sport?.type === 'collectif')

/** Libellé de la mesure affichée (score buts vs sets gagnés). */
const libelleMesure = computed(() => (sportCollectif.value ? 'Score' : 'Sets gagnés'))

const aideMesure = computed(() =>
  sportCollectif.value
    ? null
    : 'Nombre de sets ou manches remportés par chaque joueur.',
)

const scoreMin = 0
const scoreMax = computed(() => (sportCollectif.value ? 200 : 5))

const estClub = computed(() => auth.utilisateur?.type === 'club')
const matchConfirme = computed(() => match.value?.statut === 'confirme')

async function chargerEquipesDuMatch(m: typeof match.value) {
  equipeSelectionnee.value = ''
  equipesDuClub.value = []
  if (!m || m.sport?.type !== 'collectif' || auth.utilisateur?.type !== 'club') return
  chargementEquipes.value = true
  try {
    equipesDuClub.value = await chargerEquipes(auth.token!, {
      sportId: m.sport.id,
      clubId: auth.utilisateur.id,
    })
  } catch {
    equipesDuClub.value = []
  } finally {
    chargementEquipes.value = false
  }
}

const monCamp = computed(() => {
  const uid = Number(auth.utilisateur?.id)
  if (!uid || Number.isNaN(uid)) return null
  return (
    match.value?.camps?.find(
      (c: any) =>
        Number(c.joueur?.id) === uid || Number(c.equipe?.club?.id) === uid,
    ) ?? null
  )
})

const invitationEnAttente = computed(() => monCamp.value?.statut === 'invite')

const camp1 = computed(() => campParRole(match.value?.camps, 'camp_1'))
const camp2 = computed(() => campParRole(match.value?.camps, 'camp_2'))

const matchComplet = computed(() => (match.value?.camps?.length ?? 0) >= 2)

const dejaInscrit = computed(() =>
  match.value ? utilisateurEstInscrit(match.value, auth.utilisateur?.id) : false,
)

const peutRejoindre = computed(
  () =>
    !dejaInscrit.value &&
    !matchComplet.value &&
    match.value?.statut !== 'termine' &&
    match.value?.statut !== 'annule',
)

const peutQuitter = computed(
  () => monCamp.value && !estCreateur.value && match.value?.statut !== 'termine',
)

async function rejoindreMatch() {
  if (!auth.utilisateur || !match.value) return
  try {
    if (sportCollectif.value) {
      if (!estClub.value) {
        alert('Seul un compte club peut inscrire une équipe à ce sport.')
        return
      }
      if (equipeSelectionnee.value === '') {
        alert('Choisissez l\'équipe qui jouera ce match.')
        return
      }
      await ajouterCamp(auth.token!, matchId, { equipeId: equipeSelectionnee.value as number })
    } else {
      await ajouterCamp(auth.token!, matchId, { joueurId: auth.utilisateur.id })
    }
    await charger()
  } catch (e: any) {
    alert(e.message || 'Impossible de rejoindre ce match.')
  }
}

function libelleInscription(statut: string) {
  if (statut === 'confirme') return 'Inscrit'
  if (statut === 'invite') return 'Invitation en attente'
  if (statut === 'refuse') return 'Refusé'
  return statut
}

async function quitterMatch() {
  if (!monCamp.value) return
  if (!confirm('Annuler votre participation ?')) return
  try {
    await supprimerCamp(auth.token!, matchId, monCamp.value.id)
    await charger()
  } catch {
    alert('Impossible d\'annuler la participation.')
  }
}

async function confirmerParticipation() {
  if (!monCamp.value) return
  try {
    await repondreCamp(auth.token!, matchId, monCamp.value.id, 'confirme')
    await charger()
  } catch {
    alert('Impossible de confirmer la participation.')
  }
}

async function supprimerLe() {
  if (!confirm('Supprimer ce match définitivement ?')) return
  try {
    await supprimerMatch(auth.token!, matchId)
    router.push('/')
  } catch {
    alert('Impossible de supprimer le match.')
  }
}

async function envoyerMsg() {
  if (!nouveauMessage.value.trim()) return
  envoi.value = true
  try {
    const msg = await envoyerMessage(auth.token!, matchId, nouveauMessage.value.trim())
    messages.value.push(msg)
    nouveauMessage.value = ''
  } catch {
    alert("Impossible d'envoyer le message.")
  } finally {
    envoi.value = false
  }
}

async function soumettreResultat() {
  if (scoreCamp1.value === null || scoreCamp2.value === null) return
  try {
    resultat.value = await saisirResultat(
      auth.token!,
      matchId,
      scoreCamp1.value,
      scoreCamp2.value,
    )
    saisieResultat.value = false
  } catch (e: any) {
    alert(e.message || 'Impossible d\'enregistrer le résultat.')
  }
}

function formaterDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formaterHeure(dateStr: string) {
  return new Date(dateStr).toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<template>
  <div class="page-detail">
    <div class="conteneur page-corps">
    <div v-if="chargement" class="chargement">Chargement...</div>
    <div v-else-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>

    <template v-else-if="match">
      <!-- En-tête match -->
      <div class="detail-entete">
        <div class="entete-gauche">
          <RouterLink to="/" class="lien-retour">
            <ArrowLeft :size="16" aria-hidden="true" />
            Retour
          </RouterLink>
          <div>
            <span class="page-eyebrow">Match</span>
            <h1 class="page-titre">Match de {{ match.sport?.nom ?? 'Sport' }}</h1>
          </div>
          <BadgeStatut :statut="match.statut" />
        </div>
        <div class="entete-actions">
          <button v-if="estCreateur" class="btn btn-danger btn-petit" @click="supprimerLe">
            Supprimer
          </button>
        </div>
      </div>

      <div class="detail-layout">
        <!-- Informations du match -->
        <div class="colonne-principale">
          <section class="carte section-infos">
            <h2 class="section-h2">Informations</h2>
            <ul class="infos-liste">
              <li>
                <span class="info-icone" aria-hidden="true"><Calendar :size="18" stroke-width="2.25" /></span>
                {{ formaterDate(match.dateMatch) }}
              </li>
              <li v-if="match.lieu">
                <span class="info-icone" aria-hidden="true"><MapPin :size="18" stroke-width="2.25" /></span>
                {{ match.lieu }}
              </li>
              <li v-if="match.niveauRequis">
                <span class="info-icone" aria-hidden="true"><Target :size="18" stroke-width="2.25" /></span>
                Niveau : {{ match.niveauRequis?.libelle }}
              </li>
              <li>
                <span class="info-icone" aria-hidden="true"><User :size="18" stroke-width="2.25" /></span>
                Créé par {{ nomAffichage(match.createur) }}
              </li>
              <li v-if="match.description" class="description-match">
                <span class="info-icone" aria-hidden="true"><FileText :size="18" stroke-width="2.25" /></span>
                <span>{{ match.description }}</span>
              </li>
            </ul>
          </section>

          <!-- Résultat -->
          <section class="carte section-resultat">
            <h2 class="section-h2">Résultat</h2>
            <template v-if="resultat">
              <p class="resultat-mesure-libelle">{{ libelleMesure }}</p>
              <div class="score-affichage">
                <div class="score-bloc">
                  <AvatarEquipe
                    v-if="camp1?.equipe"
                    :equipe="camp1.equipe"
                    taille="sm"
                    class="score-avatar-equipe"
                  />
                  <span class="score-nom">{{ nomParticipant(camp1) }}</span>
                  <span class="score-valeur">{{ resultat.scoreCamp1 }}</span>
                </div>
                <span class="score-separateur">—</span>
                <div class="score-bloc">
                  <AvatarEquipe
                    v-if="camp2?.equipe"
                    :equipe="camp2.equipe"
                    taille="sm"
                    class="score-avatar-equipe"
                  />
                  <span class="score-nom">{{ nomParticipant(camp2) }}</span>
                  <span class="score-valeur">{{ resultat.scoreCamp2 }}</span>
                </div>
              </div>
            </template>
            <template v-else-if="estCreateur && !saisieResultat">
              <p class="resultat-vide">Pas encore de résultat.</p>
              <button class="btn btn-secondaire" @click="saisieResultat = true">
                Saisir le résultat
              </button>
            </template>
            <template v-else-if="saisieResultat">
              <p class="resultat-mesure-libelle">{{ libelleMesure }}</p>
              <p v-if="aideMesure" class="resultat-aide">{{ aideMesure }}</p>
              <div class="saisie-score">
                <label class="saisie-camp">
                  <span class="saisie-camp-nom">{{ nomParticipant(camp1) }}</span>
                  <input
                    v-model.number="scoreCamp1"
                    type="number"
                    :min="scoreMin"
                    :max="scoreMax"
                    step="1"
                    class="champ champ-score"
                    :aria-label="`${libelleMesure} — ${nomParticipant(camp1)}`"
                    placeholder="0"
                  />
                </label>
                <span class="saisie-sep">—</span>
                <label class="saisie-camp">
                  <span class="saisie-camp-nom">{{ nomParticipant(camp2) }}</span>
                  <input
                    v-model.number="scoreCamp2"
                    type="number"
                    :min="scoreMin"
                    :max="scoreMax"
                    step="1"
                    class="champ champ-score"
                    :aria-label="`${libelleMesure} — ${nomParticipant(camp2)}`"
                    placeholder="0"
                  />
                </label>
              </div>
              <div class="saisie-actions">
                <button class="btn btn-primaire" @click="soumettreResultat">Enregistrer</button>
                <button class="btn btn-secondaire" @click="saisieResultat = false">Annuler</button>
              </div>
            </template>
            <template v-else>
              <p class="resultat-vide">Pas encore de résultat.</p>
            </template>
          </section>

          <!-- Messagerie -->
          <section class="carte section-messages" v-if="estParticipantLocal">
            <h2 class="section-h2">Discussion</h2>

            <div class="messages-liste" v-if="messages.length > 0">
              <div
                v-for="msg in messages"
                :key="msg.id"
                class="message"
                :class="{ 'message-moi': msg.expediteur?.id === auth.utilisateur?.id }"
              >
                <div class="message-meta">
                  <strong>{{ nomAffichage(msg.expediteur) }}</strong>
                  <span>{{ formaterHeure(msg.dateEnvoi) }}</span>
                </div>
                <p class="message-contenu">{{ msg.contenu }}</p>
              </div>
            </div>
            <p v-else class="messages-vide">Aucun message. Soyez le premier à écrire !</p>

            <div class="message-saisie">
              <input
                v-model="nouveauMessage"
                type="text"
                class="champ"
                placeholder="Écrire un message..."
                @keyup.enter="envoyerMsg"
              />
              <button class="btn btn-primaire" @click="envoyerMsg" :disabled="envoi">
                Envoyer
              </button>
            </div>
          </section>
        </div>

        <!-- Sidebar participations -->
        <aside class="colonne-sidebar">
          <section class="carte section-participation">
            <h2 class="section-h2">Participation</h2>

            <p class="aide-participation">
              Le match est <strong>confirmé</strong> quand les <strong>2 participants</strong> sont inscrits.
              Le statut « invité » est réservé aux invitations envoyées par un autre utilisateur.
            </p>

            <template v-if="peutRejoindre && sportCollectif && estClub">
              <div class="champ-groupe">
                <label for="equipeMatch">Votre équipe</label>
                <select
                  id="equipeMatch"
                  v-model="equipeSelectionnee"
                  class="champ"
                  :disabled="chargementEquipes || equipesDuClub.length === 0"
                >
                  <option value="">
                    {{
                      chargementEquipes
                        ? 'Chargement…'
                        : equipesDuClub.length === 0
                          ? 'Aucune équipe pour ce sport'
                          : 'Choisir une équipe'
                    }}
                  </option>
                  <option v-for="eq in equipesDuClub" :key="eq.id" :value="eq.id">
                    {{ eq.nom }}
                  </option>
                </select>
              </div>
              <button class="btn btn-primaire btn-pleine-largeur" @click="rejoindreMatch">
                Inscrire mon équipe
              </button>
            </template>

            <template v-else-if="peutRejoindre && !sportCollectif">
              <button class="btn btn-primaire btn-pleine-largeur" @click="rejoindreMatch">
                Rejoindre ce match
              </button>
            </template>

            <p v-else-if="peutRejoindre && sportCollectif && !estClub" class="info-collectif">
              Les matchs de sports d'équipe (foot, rugby, volley…) sont ouverts aux <strong>clubs</strong>
              qui inscrivent une de leurs équipes.
            </p>

            <template v-else-if="monCamp">
              <div class="ma-participation">
                <p v-if="invitationEnAttente" class="ma-participation-statut">
                  Invitation en attente — confirmez pour valider votre place.
                </p>
                <p v-else class="ma-participation-statut">
                  Vous êtes inscrit
                  <span v-if="matchConfirme"> — match confirmé</span>
                </p>
                <button
                  v-if="invitationEnAttente"
                  class="btn btn-primaire btn-pleine-largeur"
                  @click="confirmerParticipation"
                >
                  Confirmer ma participation
                </button>
                <button
                  v-if="peutQuitter"
                  class="btn btn-secondaire btn-pleine-largeur"
                  @click="quitterMatch"
                >
                  Quitter ce match
                </button>
              </div>
            </template>

            <p v-else-if="dejaInscrit && estCreateur" class="createur-badge">
              Vous organisez ce match
            </p>
            <p v-else-if="matchComplet" class="participants-vide">Ce match est complet.</p>

            <!-- Participants inscrits -->
            <div class="participants-liste" v-if="match.camps?.length">
              <h3 class="participants-titre">
                Participants ({{ match.camps.length }}/2)
              </h3>
              <div
                v-for="camp in match.camps"
                :key="camp.id"
                class="participant"
              >
                <AvatarEquipe
                  v-if="camp.equipe"
                  :equipe="camp.equipe"
                  taille="sm"
                  class="participant-avatar-equipe"
                />
                <div v-else class="participant-avatar participant-avatar-joueur">
                  {{ initialesUtilisateur(camp.joueur) }}
                </div>
                <span v-if="camp.equipe">{{ camp.equipe.nom }}</span>
                <span v-else>{{ nomAffichage(camp.joueur) }}</span>
                <span class="tag-inscription" :class="'tag-inscription-' + camp.statut">
                  {{ libelleInscription(camp.statut) }}
                </span>
              </div>
            </div>
            <p v-else class="participants-vide">Aucun participant pour l'instant.</p>
          </section>
        </aside>
      </div>
    </template>
    </div>
  </div>
</template>

<style scoped>
/* ══════════════════════════════════════
   FOND PLEIN-LARGEUR
══════════════════════════════════════ */
.page-detail {
  background: #f5f9f5;
  min-height: 100vh;
  position: relative;
  overflow: hidden;
}

.page-detail::before {
  content: '';
  position: absolute;
  top: -80px; right: -80px;
  width: 380px; height: 380px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(154, 230, 0, 0.09) 0%, transparent 60%);
  pointer-events: none;
}

.page-detail::after {
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
   EN-TÊTE DE PAGE
══════════════════════════════════════ */
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
  line-height: 1.15;
}

/* ══════════════════════════════════════
   CARTE (override scoped)
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
  z-index: 1;
}

/* ══════════════════════════════════════
   LAYOUT
══════════════════════════════════════ */
.detail-entete {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: var(--espace-xl);
  gap: var(--espace-m);
  animation: fadeUp 0.45s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.entete-gauche {
  display: flex;
  flex-direction: column;
  gap: var(--espace-s);
}

.lien-retour {
  font-size: 0.85rem;
  color: var(--couleur-primaire);
  text-decoration: none;
}

.lien-retour:hover {
  text-decoration: underline;
  background: none;
}

.btn-petit {
  padding: 0.35rem 0.9rem;
  font-size: 0.85rem;
}

.detail-layout {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: var(--espace-xl);
  align-items: start;
}

.colonne-principale {
  display: flex;
  flex-direction: column;
  gap: var(--espace-l);
}

.colonne-sidebar {
  position: sticky;
  top: 80px;
}

.section-infos,
.section-resultat,
.section-messages,
.section-participation {
  padding: var(--espace-l);
}

.section-h2 {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: var(--espace-m);
  padding-bottom: var(--espace-s);
  border-bottom: 1px solid var(--couleur-bordure);
}

.infos-liste {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: var(--espace-s);
}

.infos-liste li {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
}

.description-match {
  align-items: flex-start !important;
  white-space: pre-wrap;
  line-height: 1.5;
}

.info-icone {
  display: inline-flex;
  align-items: center;
  flex-shrink: 0;
  color: var(--couleur-primaire);
}

.lien-retour {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.score-affichage {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--espace-l);
  padding: var(--espace-l);
  background: var(--couleur-primaire-tres-claire);
  border-radius: var(--rayon-carte);
}

.score-avatar-equipe {
  margin-bottom: 0.15rem;
}

.score-bloc {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
  min-width: 0;
  flex: 1;
  text-align: center;
}

.score-nom {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--couleur-texte);
  line-height: 1.3;
  word-break: break-word;
}

.score-valeur {
  font-size: 2.5rem;
  font-weight: 800;
  color: var(--couleur-primaire);
  line-height: 1;
}

.score-separateur {
  font-size: 1.75rem;
  color: var(--couleur-texte-discret);
  flex-shrink: 0;
}

.resultat-mesure-libelle {
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--couleur-texte);
  margin-bottom: var(--espace-xs);
}

.resultat-aide {
  font-size: 0.82rem;
  color: var(--couleur-texte-discret);
  margin-bottom: var(--espace-m);
  line-height: 1.4;
}

.resultat-vide {
  color: var(--couleur-texte-discret);
  font-size: 0.88rem;
  margin-bottom: var(--espace-s);
}

.saisie-score {
  display: flex;
  align-items: flex-end;
  justify-content: center;
  gap: var(--espace-m);
  margin-bottom: var(--espace-m);
  flex-wrap: wrap;
}

.saisie-camp {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
  flex: 1;
  min-width: 120px;
}

.saisie-camp-nom {
  font-size: 0.82rem;
  font-weight: 600;
  text-align: center;
  line-height: 1.3;
}

.saisie-sep {
  padding-bottom: 0.5rem;
  color: var(--couleur-texte-discret);
  font-size: 1.25rem;
}

.champ-score {
  width: 80px;
  text-align: center;
  font-size: 1.2rem;
  font-weight: 700;
}

.saisie-actions {
  display: flex;
  gap: var(--espace-s);
}

.messages-liste {
  display: flex;
  flex-direction: column;
  gap: var(--espace-s);
  max-height: 300px;
  overflow-y: auto;
  margin-bottom: var(--espace-m);
  padding-right: var(--espace-xs);
}

.message {
  padding: var(--espace-s) var(--espace-m);
  background: var(--couleur-fond);
  border-radius: var(--rayon-bouton);
}

.message.message-moi {
  background: var(--couleur-primaire-tres-claire);
  border-left: 3px solid var(--couleur-primaire);
}

.message-meta {
  display: flex;
  gap: var(--espace-s);
  align-items: center;
  margin-bottom: 0.2rem;
}

.message-meta strong {
  font-size: 0.82rem;
}

.message-meta span {
  font-size: 0.75rem;
  color: var(--couleur-texte-discret);
}

.message-contenu {
  font-size: 0.88rem;
}

.messages-vide {
  color: var(--couleur-texte-discret);
  font-size: 0.85rem;
  text-align: center;
  padding: var(--espace-m);
  margin-bottom: var(--espace-m);
}

.message-saisie {
  display: flex;
  gap: var(--espace-s);
}

.ma-participation {
  text-align: center;
  padding: var(--espace-m);
  background: var(--couleur-fond);
  border-radius: var(--rayon-bouton);
  margin-bottom: var(--espace-m);
}

.createur-badge {
  text-align: center;
  font-size: 0.88rem;
  color: var(--couleur-primaire);
  font-weight: 600;
  padding: var(--espace-s);
  background: var(--couleur-primaire-tres-claire);
  border-radius: var(--rayon-bouton);
  margin-bottom: var(--espace-m);
}

.participants-titre {
  font-size: 0.82rem;
  text-transform: uppercase;
  color: var(--couleur-texte-discret);
  letter-spacing: 0.5px;
  margin-bottom: var(--espace-s);
  margin-top: var(--espace-m);
}

.participants-liste {
  margin-top: var(--espace-m);
}

.participant {
  display: flex;
  align-items: center;
  gap: var(--espace-s);
  padding: 0.4rem 0;
  border-bottom: 1px solid var(--couleur-bordure);
  font-size: 0.88rem;
}

.participant:last-child {
  border-bottom: none;
}

.participant-avatar-equipe {
  flex-shrink: 0;
}

.participant-avatar-joueur {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: var(--couleur-primaire);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 700;
  flex-shrink: 0;
}

.participant span:nth-child(2) {
  flex: 1;
}

.participants-vide {
  color: var(--couleur-texte-discret);
  font-size: 0.85rem;
  text-align: center;
  padding: var(--espace-m);
}

.aide-participation {
  font-size: 0.82rem;
  color: var(--couleur-texte-discret);
  line-height: 1.45;
  margin-bottom: var(--espace-m);
  padding: var(--espace-s);
  background: var(--couleur-fond);
  border-radius: var(--rayon-bouton);
}

.info-collectif {
  font-size: 0.85rem;
  color: var(--couleur-texte-discret);
  line-height: 1.45;
  padding: var(--espace-s);
}

.ma-participation-statut {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--couleur-primaire);
  margin-bottom: var(--espace-s);
}

.tag-inscription {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.15rem 0.5rem;
  border-radius: var(--rayon-badge);
  margin-left: auto;
}

.tag-inscription-confirme {
  background: var(--couleur-confirme-fond);
  color: var(--couleur-confirme);
}

.tag-inscription-invite {
  background: var(--couleur-attente-fond);
  color: var(--couleur-attente);
}

.tag-inscription-refuse {
  background: var(--couleur-refuse-fond);
  color: var(--couleur-refuse);
}

button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* ══════════════════════════════════════
   ANIMATIONS
══════════════════════════════════════ */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(14px); }
  to   { opacity: 1; transform: translateY(0); }
}

.colonne-principale {
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.08s both;
}

.colonne-sidebar {
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.14s both;
}

@media (max-width: 900px) {
  .detail-layout {
    grid-template-columns: 1fr;
  }

  .colonne-sidebar {
    position: static;
  }
}
</style>
