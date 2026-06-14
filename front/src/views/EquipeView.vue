<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  chargerEquipe,
  demanderRejoindreEquipe,
  inviterMembre,
  rechercherJoueurs,
  repondreAdhesionEquipe,
  retirerMembre,
} from '@/services/api'
import AvatarEquipe from '@/components/equipes/AvatarEquipe.vue'
import {
  classeBadgeStatutMembre,
  libelleRoleEquipe,
  libelleStatutMembre,
} from '@/utils/equipeAffichage'
import { initialesUtilisateur, nomAffichage } from '@/utils/nomAffichage'
import IconeSection from '@/components/ui/IconeSection.vue'
import IconeLigne from '@/components/ui/IconeLigne.vue'
import {
  ArrowLeft,
  BarChart3,
  Calendar,
  Inbox,
  Mail,
  MapPin,
  UserPlus,
  Users,
} from 'lucide-vue-next'

const auth = useAuthStore()
const route = useRoute()

const equipe = ref<any>(null)
const chargement = ref(true)
const erreur = ref('')
const messageSucces = ref('')

const rechercheJoueur = ref('')
const resultatsRecherche = ref<any[]>([])
const rechercheEnCours = ref(false)
const invitationEnCours = ref<number | null>(null)
const annulationEnCours = ref<number | null>(null)
const retraitEnCours = ref<number | null>(null)
const demandeEnCours = ref(false)
const reponseDemandeEnCours = ref<number | null>(null)

let debounceRecherche: ReturnType<typeof setTimeout> | null = null

const equipeId = Number(route.params.id)

const estProprietaire = computed(() => {
  const club = equipe.value?.club
  const clubId = typeof club === 'object' ? club?.id : club
  return auth.utilisateur?.type === 'club' && clubId === auth.utilisateur?.id
})

const monMembre = computed(() =>
  equipe.value?.membres?.find((m: any) => m.utilisateur?.id === auth.utilisateur?.id) ?? null,
)

const demandesEnAttente = computed(() =>
  (equipe.value?.membres ?? []).filter(
    (m: any) => m.origine === 'demande_joueur' && m.statut === 'en_attente',
  ),
)

const membresAffichés = computed(() =>
  (equipe.value?.membres ?? []).filter(
    (m: any) =>
      m.role === 'gestionnaire'
      || m.statut === 'confirme'
      || (m.statut === 'en_attente' && m.origine === 'invitation_club'),
  ),
)

const peutDemanderRejoindre = computed(() => {
  if (auth.utilisateur?.type !== 'joueur' || estProprietaire.value) return false
  const m = monMembre.value
  if (!m) return true
  return m.statut === 'refuse'
})

const demandeJoueurEnAttente = computed(
  () =>
    monMembre.value?.origine === 'demande_joueur' && monMembre.value?.statut === 'en_attente',
)

const peutQuitterEquipe = computed(
  () =>
    auth.utilisateur?.type === 'joueur'
    && !estProprietaire.value
    && monMembre.value?.statut === 'confirme'
    && monMembre.value?.role === 'joueur',
)

onMounted(charger)

async function charger() {
  chargement.value = true
  erreur.value = ''
  try {
    equipe.value = await chargerEquipe(auth.token!, equipeId)
  } catch (e: any) {
    if (e.statut === 404) {
      erreur.value = 'Cette équipe est introuvable.'
    } else {
      erreur.value = 'Impossible de charger les données de l\'équipe.'
    }
  } finally {
    chargement.value = false
  }
}

function onRechercheInput() {
  messageSucces.value = ''
  if (debounceRecherche) clearTimeout(debounceRecherche)
  const q = rechercheJoueur.value.trim()
  if (q.length < 2) {
    resultatsRecherche.value = []
    return
  }
  debounceRecherche = setTimeout(() => lancerRecherche(q), 350)
}

async function lancerRecherche(q: string) {
  rechercheEnCours.value = true
  try {
    resultatsRecherche.value = await rechercherJoueurs(auth.token!, q)
  } catch {
    resultatsRecherche.value = []
  } finally {
    rechercheEnCours.value = false
  }
}

async function inviter(joueurId: number) {
  invitationEnCours.value = joueurId
  erreur.value = ''
  messageSucces.value = ''
  try {
    await inviterMembre(auth.token!, equipeId, joueurId)
    messageSucces.value = 'Invitation envoyée.'
    rechercheJoueur.value = ''
    resultatsRecherche.value = []
    await charger()
  } catch (e: any) {
    erreur.value = e.message || 'Invitation impossible.'
  } finally {
    invitationEnCours.value = null
  }
}

async function annulerInvitation(membreId: number) {
  annulationEnCours.value = membreId
  erreur.value = ''
  try {
    await retirerMembre(auth.token!, equipeId, membreId)
    messageSucces.value = 'Invitation annulée.'
    await charger()
  } catch (e: any) {
    erreur.value = e.message || 'Annulation impossible.'
  } finally {
    annulationEnCours.value = null
  }
}

function peutAnnulerInvitation(membre: any): boolean {
  return (
    membre.statut === 'en_attente' &&
    membre.origine === 'invitation_club' &&
    membre.role !== 'gestionnaire'
  )
}

function peutRetirerMembre(membre: any): boolean {
  return (
    estProprietaire.value
    && membre.role === 'joueur'
    && membre.statut === 'confirme'
  )
}

async function retirerMembreEquipe(membre: any) {
  const nom = nomAffichage(membre.utilisateur)
  if (
    !confirm(
      `Retirer ${nom} de l'équipe ? Cette personne ne sera plus membre de l'équipe.`,
    )
  ) {
    return
  }

  retraitEnCours.value = membre.id
  erreur.value = ''
  try {
    await retirerMembre(auth.token!, equipeId, membre.id)
    messageSucces.value = 'Membre retiré.'
    await charger()
  } catch (e: any) {
    erreur.value = e.message || 'Retrait impossible.'
  } finally {
    retraitEnCours.value = null
  }
}

async function quitterEquipe() {
  if (!monMembre.value) return
  const nom = equipe.value?.nom ?? 'cette équipe'
  if (
    !confirm(
      `Quitter l'équipe « ${nom} » ? Vous ne pourrez plus participer aux matchs de cette équipe.`,
    )
  ) {
    return
  }

  annulationEnCours.value = monMembre.value.id
  erreur.value = ''
  try {
    await retirerMembre(auth.token!, equipeId, monMembre.value.id)
    messageSucces.value = 'Vous avez quitté l\'équipe.'
    await charger()
  } catch (e: any) {
    erreur.value = e.message || 'Impossible de quitter l\'équipe.'
  } finally {
    annulationEnCours.value = null
  }
}

function dejaDansEquipe(joueurId: number): boolean {
  return equipe.value?.membres?.some(
    (m: any) =>
      m.utilisateur?.id === joueurId &&
      m.statut !== 'refuse',
  )
}

async function demanderRejoindre() {
  demandeEnCours.value = true
  erreur.value = ''
  messageSucces.value = ''
  try {
    await demanderRejoindreEquipe(auth.token!, equipeId)
    messageSucces.value = 'Demande envoyée au club.'
    await charger()
  } catch (e: any) {
    erreur.value = e.message || 'Demande impossible.'
  } finally {
    demandeEnCours.value = false
  }
}

async function annulerMaDemande() {
  if (!monMembre.value) return
  annulationEnCours.value = monMembre.value.id
  erreur.value = ''
  try {
    await retirerMembre(auth.token!, equipeId, monMembre.value.id)
    messageSucces.value = 'Demande annulée.'
    await charger()
  } catch (e: any) {
    erreur.value = e.message || 'Annulation impossible.'
  } finally {
    annulationEnCours.value = null
  }
}

async function repondreDemande(membreId: number, statut: 'confirme' | 'refuse') {
  reponseDemandeEnCours.value = membreId
  erreur.value = ''
  try {
    await repondreAdhesionEquipe(auth.token!, equipeId, membreId, statut)
    messageSucces.value = statut === 'confirme' ? 'Joueur accepté.' : 'Demande refusée.'
    await charger()
  } catch (e: any) {
    erreur.value = e.message || 'Action impossible.'
  } finally {
    reponseDemandeEnCours.value = null
  }
}
</script>

<template>
  <div class="page-equipe">
    <div v-if="chargement" class="chargement conteneur">Chargement...</div>
    <div v-else-if="erreur && !equipe" class="conteneur alerte alerte-erreur" style="margin-top: var(--espace-xl)">
      {{ erreur }}
    </div>

    <template v-else-if="equipe">
      <div class="conteneur lien-equipe-haut">
        <RouterLink
          v-if="auth.utilisateur?.type === 'club'"
          to="/mes-equipes"
          class="lien-retour"
        >
          <ArrowLeft :size="16" aria-hidden="true" />
          Mes équipes
        </RouterLink>
      </div>

      <div class="equipe-entete">
        <div class="conteneur equipe-entete-interieur">
          <div class="equipe-identite">
            <AvatarEquipe :equipe="equipe" taille="lg" />
            <div>
              <span class="equipe-eyebrow">Fiche équipe</span>
              <h1 class="equipe-nom">{{ equipe.nom }}</h1>
              <div class="equipe-meta">
                <span>{{ equipe.sport?.nom ?? equipe.sport }}</span>
                <span v-if="equipe.niveau">· {{ equipe.niveau?.libelle ?? equipe.niveau }}</span>
                <span v-if="equipe.localisation" class="equipe-meta-lieu">
                  ·
                  <IconeLigne :icone="MapPin" :taille="14" discret>{{ equipe.localisation }}</IconeLigne>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="conteneur equipe-contenu">
        <div v-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>
        <div v-if="messageSucces" class="alerte alerte-succes">{{ messageSucces }}</div>

        <section
          v-if="auth.utilisateur?.type === 'joueur' && !estProprietaire"
          class="carte section-demande-joueur"
        >
          <div class="section-titre-icone">
            <IconeSection :icone="UserPlus" label="Rejoindre cette équipe" />
            <h2>Rejoindre cette équipe</h2>
          </div>
          <p v-if="demandeJoueurEnAttente" class="demande-etat">
            Votre demande est en attente de validation par le club.
          </p>
          <p v-else-if="monMembre?.statut === 'confirme'" class="demande-etat">
            Vous êtes membre de cette équipe.
          </p>
          <div class="demande-actions">
            <button
              v-if="peutDemanderRejoindre"
              class="btn btn-primaire"
              :disabled="demandeEnCours"
              @click="demanderRejoindre"
            >
              Demander à rejoindre
            </button>
            <button
              v-if="demandeJoueurEnAttente"
              class="btn btn-secondaire"
              :disabled="annulationEnCours === monMembre?.id"
              @click="annulerMaDemande"
            >
              Annuler ma demande
            </button>
            <button
              v-if="peutQuitterEquipe"
              class="btn btn-secondaire btn-quitter"
              :disabled="annulationEnCours === monMembre?.id"
              @click="quitterEquipe"
            >
              Quitter l'équipe
            </button>
          </div>
          <p class="inviter-aide">
            Une seule équipe par sport (confirmée ou en attente).
            <RouterLink to="/mes-equipes-joueur?onglet=trouver" class="lien-espace">
              Gérer dans Mes équipes
            </RouterLink>
          </p>
        </section>

        <section
          v-if="estProprietaire && demandesEnAttente.length"
          class="carte section-demandes"
        >
          <div class="section-titre-icone">
            <IconeSection :icone="Inbox" label="Demandes en attente" />
            <h2>Demandes en attente</h2>
            <span class="membres-count">{{ demandesEnAttente.length }}</span>
          </div>
          <ul class="liste-demandes">
            <li v-for="demande in demandesEnAttente" :key="demande.id" class="ligne-demande">
              <div class="demande-joueur">
                <span class="membre-avatar membre-avatar-sm">
                  {{ initialesUtilisateur(demande.utilisateur) }}
                </span>
                <strong>{{ nomAffichage(demande.utilisateur) }}</strong>
              </div>
              <div class="demande-boutons">
                <button
                  class="btn btn-primaire btn-compact"
                  :disabled="reponseDemandeEnCours === demande.id"
                  @click="repondreDemande(demande.id, 'confirme')"
                >
                  Accepter
                </button>
                <button
                  class="btn btn-secondaire btn-compact"
                  :disabled="reponseDemandeEnCours === demande.id"
                  @click="repondreDemande(demande.id, 'refuse')"
                >
                  Refuser
                </button>
              </div>
            </li>
          </ul>
        </section>

        <section v-if="estProprietaire" class="carte section-inviter">
          <div class="section-titre-icone">
            <IconeSection :icone="Mail" label="Inviter un joueur" />
            <h2>Inviter un joueur</h2>
          </div>
          <p class="inviter-aide">
            Recherchez par nom, prénom ou e-mail. Le joueur devra accepter l'invitation (une équipe par sport).
          </p>
          <input
            v-model="rechercheJoueur"
            type="search"
            class="champ-recherche"
            placeholder="Ex. Dupont ou jean.dupont@test.com"
            @input="onRechercheInput"
          />
          <p v-if="rechercheEnCours" class="recherche-etat">Recherche...</p>
          <ul v-else-if="resultatsRecherche.length" class="resultats-recherche">
            <li v-for="j in resultatsRecherche" :key="j.id">
              <span>{{ nomAffichage(j) }} <small>{{ j.email }}</small></span>
              <button
                v-if="!dejaDansEquipe(j.id)"
                class="btn btn-primaire btn-compact"
                :disabled="invitationEnCours === j.id"
                @click="inviter(j.id)"
              >
                Inviter
              </button>
              <span v-else class="deja-membre">Déjà dans l'équipe</span>
            </li>
          </ul>
        </section>

        <section class="carte section-membres">
          <div class="section-titre-icone">
            <IconeSection :icone="Users" label="Membres de l'équipe" />
            <h2>Membres de l'équipe</h2>
            <span class="membres-count">
              {{ membresAffichés.length }} membre{{ membresAffichés.length > 1 ? 's' : '' }}
            </span>
          </div>

          <div v-if="!membresAffichés.length" class="vide-section">
            Aucun membre pour le moment.
          </div>

          <div v-else class="grille-membres">
            <div
              v-for="membre in membresAffichés"
              :key="membre.id"
              class="carte-membre"
            >
              <div class="membre-avatar">
                {{ initialesUtilisateur(membre.utilisateur) }}
              </div>
              <div class="membre-info">
                <strong>{{ nomAffichage(membre.utilisateur) }}</strong>
                <span class="membre-role">{{ libelleRoleEquipe(membre.role) }}</span>
                <span
                  v-if="membre.statut && membre.statut !== 'confirme'"
                  class="membre-statut"
                  :class="classeBadgeStatutMembre(membre.statut)"
                >
                  {{ libelleStatutMembre(membre.statut) }}
                </span>
              </div>
              <button
                v-if="estProprietaire && peutAnnulerInvitation(membre)"
                class="btn-membre-action btn-annuler-invitation"
                :disabled="annulationEnCours === membre.id"
                @click="annulerInvitation(membre.id)"
              >
                Annuler l'invitation
              </button>
              <button
                v-if="peutRetirerMembre(membre)"
                class="btn-membre-action btn-retirer-membre"
                :disabled="retraitEnCours === membre.id"
                @click="retirerMembreEquipe(membre)"
              >
                Retirer
              </button>
            </div>
          </div>
        </section>

        <div class="bas-contenu">
          <section class="carte section-calendrier">
            <div class="section-titre-icone">
              <IconeSection :icone="Calendar" label="Calendrier" />
              <h2>Calendrier</h2>
            </div>
            <div class="vide-section">
              <p>Inscrivez l'équipe à des matchs pour voir le calendrier.</p>
              <RouterLink to="/rechercher" class="btn btn-secondaire" style="margin-top: var(--espace-m)">
                Trouver un match
              </RouterLink>
            </div>
          </section>

          <section class="carte section-stats">
            <div class="section-titre-icone">
              <IconeSection :icone="BarChart3" label="Statistiques de l'équipe" />
              <h2>Statistiques de l'équipe</h2>
            </div>
            <div class="stats-grille">
              <div class="stat-principale">
                <span class="stat-nombre">0</span>
                <span class="stat-label">Matchs joués</span>
              </div>
              <div class="stats-secondaires">
                <div class="stat-item victoire">
                  <span class="stat-nombre">0</span>
                  <span class="stat-label">Victoires</span>
                </div>
                <div class="stat-item defaite">
                  <span class="stat-nombre">0</span>
                  <span class="stat-label">Défaites</span>
                </div>
              </div>
            </div>
            <div class="taux-victoire">
              <div class="taux-label">
                <span>Taux de victoires</span>
                <span class="taux-valeur">—</span>
              </div>
              <div class="barre-progression">
                <div class="barre-remplie" style="width: 0%"></div>
              </div>
            </div>
            <div class="stats-buts">
              <div class="stat-but">
                <span>Buts marqués</span>
                <strong>—</strong>
              </div>
              <div class="stat-but">
                <span>Buts encaissés</span>
                <strong>—</strong>
              </div>
            </div>
          </section>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
/* ══════════════════════════════════════
   FOND PLEIN-LARGEUR
══════════════════════════════════════ */
.page-equipe {
  background: #f5f9f5;
  min-height: 100vh;
  position: relative;
  overflow: hidden;
  padding-bottom: var(--espace-xxl);
}

.page-equipe::before {
  content: '';
  position: absolute;
  top: -60px; right: -80px;
  width: 340px; height: 340px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(154, 230, 0, 0.08) 0%, transparent 60%);
  pointer-events: none;
}

.page-equipe::after {
  content: '';
  position: absolute;
  bottom: -80px; left: -80px;
  width: 280px; height: 280px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(46, 125, 50, 0.06) 0%, transparent 60%);
  pointer-events: none;
}

/* ══════════════════════════════════════
   LIEN RETOUR
══════════════════════════════════════ */
.lien-equipe-haut {
  padding-top: var(--espace-m);
  position: relative;
  z-index: 1;
}

.lien-retour {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.88rem;
  font-weight: 500;
  color: var(--couleur-texte-discret);
  text-decoration: none;
  transition: color 0.18s ease;
}

.lien-retour:hover {
  color: var(--couleur-primaire);
}

/* ══════════════════════════════════════
   EN-TÊTE ÉQUIPE
══════════════════════════════════════ */
.equipe-entete {
  background: #ffffff;
  padding: var(--espace-l) 0;
  margin-bottom: var(--espace-xl);
  position: relative;
  z-index: 1;
  animation: fadeUp 0.45s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.equipe-entete::after {
  content: '';
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 3px;
  background: var(--degrade-primaire);
}

.equipe-entete-interieur {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: var(--espace-m);
}

.equipe-identite {
  display: flex;
  align-items: center;
  gap: var(--espace-l);
}

.equipe-eyebrow {
  display: inline-flex;
  align-items: center;
  background: var(--couleur-accent-fond);
  color: var(--couleur-accent-texte);
  padding: 0.18rem 0.6rem;
  border-radius: var(--rayon-badge);
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-bottom: 0.3rem;
}

.equipe-nom {
  font-size: 1.65rem;
  font-weight: 800;
  letter-spacing: -0.025em;
  color: var(--couleur-titre);
  margin-bottom: 0.2rem;
}

.equipe-meta {
  font-size: 0.88rem;
  color: var(--couleur-texte-discret);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.equipe-meta-lieu {
  display: inline-flex;
  align-items: center;
  gap: 0.15rem;
}

/* ══════════════════════════════════════
   CONTENU PRINCIPAL
══════════════════════════════════════ */
.equipe-contenu {
  display: flex;
  flex-direction: column;
  gap: var(--espace-l);
  position: relative;
  z-index: 1;
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.1s both;
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

.section-demande-joueur,
.section-demandes,
.section-inviter,
.section-membres,
.section-calendrier,
.section-stats {
  padding: var(--espace-l);
}

/* ══════════════════════════════════════
   EN-TÊTES DE SECTION
══════════════════════════════════════ */
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

.membres-count {
  font-size: 0.73rem;
  font-weight: 600;
  color: var(--couleur-accent-texte);
  background: var(--couleur-accent-fond);
  padding: 0.22rem 0.65rem;
  border-radius: var(--rayon-badge);
}

/* ══════════════════════════════════════
   SECTION REJOINDRE
══════════════════════════════════════ */
.demande-etat {
  font-size: 0.9rem;
  margin-bottom: var(--espace-m);
}

.demande-actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--espace-s);
  margin-bottom: var(--espace-s);
}

.btn-quitter {
  color: var(--couleur-refuse) !important;
  border-color: rgba(198, 40, 40, 0.3) !important;
}

.btn-quitter:hover:not(:disabled) {
  background: var(--couleur-refuse-fond) !important;
}

.inviter-aide {
  font-size: 0.88rem;
  color: var(--couleur-texte-discret);
  margin-bottom: var(--espace-m);
}

.lien-espace {
  display: block;
  margin-top: 0.35rem;
  color: var(--couleur-primaire);
  font-size: 0.85rem;
}

/* ══════════════════════════════════════
   SECTION DEMANDES
══════════════════════════════════════ */
.liste-demandes {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--espace-s);
}

.ligne-demande {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: var(--espace-m);
  padding: var(--espace-m);
  background: var(--couleur-fond);
  border-radius: calc(var(--rayon-carte) - 2px);
  transition: background 0.18s ease;
}

.ligne-demande:hover {
  background: var(--couleur-primaire-tres-claire);
}

.demande-joueur {
  display: flex;
  align-items: center;
  gap: var(--espace-s);
}

.demande-boutons {
  display: flex;
  gap: var(--espace-s);
}

/* ══════════════════════════════════════
   SECTION INVITER / RECHERCHE
══════════════════════════════════════ */
.champ-recherche {
  width: 100%;
  max-width: 420px;
  padding: 0.62rem 0.95rem;
  border: 1.5px solid var(--couleur-bordure);
  border-radius: var(--rayon-champ);
  font-size: 0.9rem;
  color: var(--couleur-texte);
  background: var(--couleur-fond-blanc);
  outline: none;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.champ-recherche:focus {
  border-color: var(--couleur-primaire);
  box-shadow: 0 0 0 3px rgba(154, 230, 0, 0.28), 0 0 0 1px var(--couleur-primaire);
}

.recherche-etat {
  font-size: 0.85rem;
  color: var(--couleur-texte-discret);
  margin-top: var(--espace-s);
}

.resultats-recherche {
  list-style: none;
  margin: var(--espace-m) 0 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.resultats-recherche li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--espace-m);
  padding: 0.55rem var(--espace-s);
  border-radius: 6px;
  font-size: 0.9rem;
  transition: background 0.15s ease;
}

.resultats-recherche li:hover {
  background: var(--couleur-primaire-tres-claire);
}

.resultats-recherche small {
  color: var(--couleur-texte-discret);
  margin-left: 0.35rem;
}

.btn-compact {
  padding: 0.32rem 0.72rem;
  font-size: 0.82rem;
}

.deja-membre {
  font-size: 0.8rem;
  color: var(--couleur-texte-discret);
}

/* ══════════════════════════════════════
   MEMBRES — LAYOUT HORIZONTAL
══════════════════════════════════════ */
.grille-membres {
  display: flex;
  flex-direction: column;
  gap: var(--espace-s);
}

.carte-membre {
  display: flex;
  flex-direction: row;
  align-items: center;
  padding: var(--espace-m);
  background: var(--couleur-fond);
  border: 1.5px solid #e8f0e8;
  border-radius: calc(var(--rayon-carte) - 2px);
  gap: var(--espace-m);
  transition: border-color 0.18s ease, background 0.18s ease;
}

.carte-membre:hover {
  border-color: var(--couleur-primaire-claire);
  background: #f0f8f0;
}

.membre-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: linear-gradient(135deg, #388e3c, #66bb6a);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  font-weight: 700;
  flex-shrink: 0;
}

.membre-avatar-sm {
  width: 40px;
  height: 40px;
  font-size: 0.85rem;
}

.membre-info {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  flex: 1;
  min-width: 0;
  text-align: left;
}

.membre-info strong {
  font-size: 0.9rem;
  color: var(--couleur-titre);
  font-weight: 700;
}

.membre-role {
  font-size: 0.78rem;
  color: var(--couleur-texte-discret);
}

.membre-statut {
  font-size: 0.71rem;
  font-weight: 600;
  padding: 0.2rem 0.55rem;
  border-radius: var(--rayon-badge);
  align-self: flex-start;
  margin-top: 0.15rem;
}

.badge-attente {
  background: var(--couleur-attente-fond, #fff8e1);
  color: var(--couleur-attente, #f57c00);
}

.badge-confirme {
  background: var(--couleur-confirme-fond);
  color: var(--couleur-confirme);
}

.badge-refuse {
  background: var(--couleur-refuse-fond);
  color: var(--couleur-refuse);
}

/* ── Boutons d'action membre ── */
.btn-membre-action {
  flex-shrink: 0;
  background: none;
  border: 1.5px solid var(--couleur-bordure);
  border-radius: 6px;
  padding: 0.28rem 0.7rem;
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: background 0.18s, border-color 0.18s, color 0.18s;
}

.btn-annuler-invitation {
  color: var(--couleur-texte-discret);
}

.btn-annuler-invitation:hover:not(:disabled) {
  background: var(--couleur-fond);
  color: var(--couleur-texte);
  border-color: var(--couleur-texte-discret);
}

.btn-retirer-membre {
  color: var(--couleur-refuse);
  border-color: rgba(198, 40, 40, 0.25);
}

.btn-retirer-membre:hover:not(:disabled) {
  background: var(--couleur-refuse-fond);
  border-color: var(--couleur-refuse);
}

.btn-membre-action:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ══════════════════════════════════════
   BAS — CALENDRIER + STATS
══════════════════════════════════════ */
.bas-contenu {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--espace-l);
}

.vide-section {
  text-align: center;
  padding: var(--espace-l);
  color: var(--couleur-texte-discret);
  font-size: 0.88rem;
}

/* ── Statistiques ── */
.stats-grille {
  display: flex;
  gap: var(--espace-m);
  margin-bottom: var(--espace-l);
}

.stat-principale {
  flex: 1;
  background: var(--degrade-primaire);
  color: white;
  border-radius: var(--rayon-carte);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--espace-l);
}

.stats-secondaires {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: var(--espace-s);
}

.stat-item {
  flex: 1;
  border-radius: var(--rayon-carte);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--espace-s);
}

.stat-item.victoire {
  background: var(--couleur-confirme-fond);
  color: var(--couleur-confirme);
}

.stat-item.defaite {
  background: var(--couleur-refuse-fond);
  color: var(--couleur-refuse);
}

.stat-nombre {
  font-size: 1.6rem;
  font-weight: 800;
  line-height: 1;
}

.stat-label {
  font-size: 0.78rem;
  opacity: 0.8;
}

.taux-victoire {
  margin-bottom: var(--espace-m);
}

.taux-label {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  margin-bottom: 0.3rem;
}

.taux-valeur {
  font-weight: 700;
  color: var(--couleur-primaire);
}

.barre-progression {
  height: 8px;
  background: var(--couleur-fond);
  border-radius: 4px;
  overflow: hidden;
}

.barre-remplie {
  height: 100%;
  background: var(--degrade-primaire);
  border-radius: 4px;
}

.stats-buts {
  display: flex;
  flex-direction: column;
  gap: var(--espace-xs);
}

.stat-but {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.4rem 0;
  border-bottom: 1px solid var(--couleur-bordure);
  font-size: 0.88rem;
}

.stat-but span {
  color: var(--couleur-texte-discret);
}

.stat-but strong {
  font-weight: 700;
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
@media (max-width: 768px) {
  .bas-contenu {
    grid-template-columns: 1fr;
  }

  .equipe-entete-interieur {
    flex-direction: column;
    align-items: flex-start;
  }

  .equipe-nom {
    font-size: 1.35rem;
  }
}

@media (max-width: 560px) {
  .carte-membre {
    flex-wrap: wrap;
  }
}
</style>
