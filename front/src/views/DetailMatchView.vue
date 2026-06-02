<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  chargerMatch,
  supprimerMatch,
  participer,
  annulerParticipation,
  chargerMessages,
  envoyerMessage,
  chargerResultat,
  saisirResultat,
} from '@/services/api'
import BadgeStatut from '@/components/commun/BadgeStatut.vue'

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

const scoreEquipe1 = ref<number | null>(null)
const scoreEquipe2 = ref<number | null>(null)
const saisieResultat = ref(false)

onMounted(charger)

async function charger() {
  chargement.value = true
  try {
    match.value = await chargerMatch(auth.token!, matchId)
    try {
      resultat.value = await chargerResultat(auth.token!, matchId)
    } catch {
      resultat.value = null
    }
    if (estParticipantLocal.value) {
      messages.value = await chargerMessages(auth.token!, matchId)
    }
  } catch (e: any) {
    erreur.value = e.statut === 404 ? 'Match introuvable.' : 'Erreur de chargement.'
  } finally {
    chargement.value = false
  }
}

const estCreateur = computed(
  () => match.value?.createur?.id === auth.utilisateur?.id,
)

const estParticipantLocal = computed(() => {
  if (!match.value || !auth.utilisateur) return false
  if (estCreateur.value) return true
  return match.value.participations?.some(
    (p: any) => p.utilisateur?.id === auth.utilisateur.id && p.statut === 'confirmé',
  )
})

const maParticipation = computed(() =>
  match.value?.participations?.find(
    (p: any) => p.utilisateur?.id === auth.utilisateur?.id,
  ),
)

async function rejoindreMatch() {
  try {
    await participer(auth.token!, matchId)
    await charger()
  } catch (e: any) {
    if (e.statut === 422) alert('Vous participez déjà à ce match.')
  }
}

async function quitterMatch() {
  if (!maParticipation.value) return
  if (!confirm('Annuler votre participation ?')) return
  try {
    await annulerParticipation(auth.token!, matchId, maParticipation.value.id)
    await charger()
  } catch {
    alert('Impossible d\'annuler la participation.')
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
  if (scoreEquipe1.value === null || scoreEquipe2.value === null) return
  try {
    resultat.value = await saisirResultat(
      auth.token!,
      matchId,
      scoreEquipe1.value,
      scoreEquipe2.value,
    )
    saisieResultat.value = false
  } catch (e: any) {
    if (e.statut === 422) alert('Un résultat existe déjà pour ce match.')
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
  <div class="conteneur page-detail">
    <div v-if="chargement" class="chargement">Chargement...</div>
    <div v-else-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>

    <template v-else-if="match">
      <!-- En-tête match -->
      <div class="detail-entete">
        <div class="entete-gauche">
          <RouterLink to="/" class="lien-retour">← Retour</RouterLink>
          <h1>
            Match de {{ match.sport.charAt(0).toUpperCase() + match.sport.slice(1) }}
          </h1>
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
              <li><span class="info-icone">📅</span> {{ formaterDate(match.dateMatch) }}</li>
              <li v-if="match.lieu"><span class="info-icone">📍</span> {{ match.lieu }}</li>
              <li v-if="match.niveauRequis">
                <span class="info-icone">🎯</span> Niveau : {{ match.niveauRequis }}
              </li>
              <li>
                <span class="info-icone">👤</span>
                Créé par {{ match.createur?.prenom }} {{ match.createur?.nom }}
              </li>
            </ul>
          </section>

          <!-- Résultat -->
          <section class="carte section-resultat">
            <h2 class="section-h2">Résultat</h2>
            <template v-if="resultat">
              <div class="score-affichage">
                <span class="score">{{ resultat.scoreEquipe1 }}</span>
                <span class="score-separateur">—</span>
                <span class="score">{{ resultat.scoreEquipe2 }}</span>
              </div>
            </template>
            <template v-else-if="estCreateur && !saisieResultat">
              <p class="resultat-vide">Pas encore de résultat.</p>
              <button class="btn btn-secondaire" @click="saisieResultat = true">
                Saisir le résultat
              </button>
            </template>
            <template v-else-if="saisieResultat">
              <div class="saisie-score">
                <input
                  v-model.number="scoreEquipe1"
                  type="number"
                  min="0"
                  class="champ champ-score"
                  placeholder="0"
                />
                <span>—</span>
                <input
                  v-model.number="scoreEquipe2"
                  type="number"
                  min="0"
                  class="champ champ-score"
                  placeholder="0"
                />
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
                  <strong>{{ msg.expediteur?.prenom }} {{ msg.expediteur?.nom }}</strong>
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

            <!-- Actions -->
            <template v-if="!estCreateur">
              <button
                v-if="!maParticipation"
                class="btn btn-primaire btn-pleine-largeur"
                @click="rejoindreMatch"
              >
                Rejoindre ce match
              </button>
              <div v-else class="ma-participation">
                <BadgeStatut :statut="maParticipation.statut" />
                <button
                  v-if="maParticipation.statut !== 'confirmé'"
                  class="btn btn-secondaire btn-pleine-largeur"
                  style="margin-top: var(--espace-s)"
                  @click="quitterMatch"
                >
                  Annuler ma participation
                </button>
              </div>
            </template>
            <p v-else class="createur-badge">Vous êtes l'organisateur</p>

            <!-- Liste participants -->
            <div class="participants-liste" v-if="match.participations?.length">
              <h3 class="participants-titre">
                Participants ({{ match.participations.length }})
              </h3>
              <div
                v-for="p in match.participations"
                :key="p.id"
                class="participant"
              >
                <div class="participant-avatar">
                  {{ (p.utilisateur?.prenom?.[0] ?? '?') }}
                </div>
                <span>{{ p.utilisateur?.prenom }} {{ p.utilisateur?.nom }}</span>
                <BadgeStatut :statut="p.statut" />
              </div>
            </div>
            <p v-else class="participants-vide">Aucun participant pour l'instant.</p>
          </section>
        </aside>
      </div>
    </template>
  </div>
</template>

<style scoped>
.page-detail {
  padding-top: var(--espace-xl);
  padding-bottom: var(--espace-xxl);
}

.detail-entete {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: var(--espace-xl);
  gap: var(--espace-m);
}

.entete-gauche {
  display: flex;
  flex-direction: column;
  gap: var(--espace-s);
}

.entete-gauche h1 {
  font-size: 1.6rem;
  font-weight: 700;
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

.info-icone {
  font-size: 1rem;
}

.score-affichage {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--espace-l);
  padding: var(--espace-l);
  background: var(--couleur-fond);
  border-radius: var(--rayon-carte);
}

.score {
  font-size: 3rem;
  font-weight: 800;
  color: var(--couleur-primaire);
}

.score-separateur {
  font-size: 2rem;
  color: var(--couleur-texte-discret);
}

.resultat-vide {
  color: var(--couleur-texte-discret);
  font-size: 0.88rem;
  margin-bottom: var(--espace-s);
}

.saisie-score {
  display: flex;
  align-items: center;
  gap: var(--espace-m);
  margin-bottom: var(--espace-m);
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

.participant-avatar {
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

button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
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
