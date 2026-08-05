const API_URL = 'http://localhost:8000/api'

interface ErreurApi extends Error {
  statut: number
  donnees: unknown
}

// Handler enregistré par main.ts (évite tout import circulaire api ↔ auth/router)
let _onSessionExpiree: (() => void) | null = null

export function setOnSessionExpiree(handler: () => void) {
  _onSessionExpiree = handler
}

async function requete(
  methode: string,
  chemin: string,
  donnees: unknown = null,
  token: string | null = null,
): Promise<any> {
  const headers: Record<string, string> = { 'Content-Type': 'application/json' }

  if (token) {
    headers['Authorization'] = `Bearer ${token}`
  }

  const options: RequestInit = { method: methode, headers }

  if (donnees !== null) {
    options.body = JSON.stringify(donnees)
  }

  const reponse = await fetch(`${API_URL}${chemin}`, options)

  if (reponse.status === 204) return null

  const json = await reponse.json().catch(() => null)

  if (!reponse.ok) {
    // 401 sur /login_check = mauvais identifiants (erreur normale, pas de déconnexion)
    if (reponse.status === 401 && chemin !== '/login_check') {
      _onSessionExpiree?.()
    }

    const erreur = new Error(
      (json as any)?.erreur || (json as any)?.message || `Erreur ${reponse.status}`,
    ) as ErreurApi
    erreur.statut = reponse.status
    erreur.donnees = json
    throw erreur
  }

  return json
}

function construireParams(filtres: Record<string, unknown>): string {
  const entrees = Object.entries(filtres).filter(
    ([, v]) => v !== '' && v !== null && v !== undefined,
  )
  return entrees.length
    ? '?' + new URLSearchParams(Object.fromEntries(entrees.map(([k, v]) => [k, String(v)]))).toString()
    : ''
}

// Auth
export const connexion = (email: string, motDePasse: string) =>
  requete('POST', '/login_check', { email, password: motDePasse })

export const inscrire = (donnees: Record<string, unknown>) =>
  requete('POST', '/register', donnees)

// Sports (référentiel public)
export interface NiveauRef { id: number; libelle: string; ordre: number }
export interface SportRef  { id: number; nom: string; type: 'collectif' | 'individuel'; niveaux?: NiveauRef[] }

// Réputation (calculée par AvisRepository.calculerMoyennesRecues)
export interface Reputation {
  ponctualite:    number
  fairPlay:       number
  niveauConforme: number
  total:          number
}

// Niveau déclaré par un utilisateur (groupe utilisateur_niveau:read)
export interface UtilisateurNiveau {
  id:     number
  sport:  SportRef
  niveau: NiveauRef
}

// Profil public d'un utilisateur (groupe utilisateur:public)
// niveaux présent uniquement quand utilisateur:detail est aussi dans le contexte (GET /profils/{id})
export interface UtilisateurPublic {
  id:           number
  nom:          string
  prenom:       string | null
  type:         'club' | 'joueur'
  localisation: string | null
  logo:         string | null
  reputation:   Reputation | null
  niveaux?:     UtilisateurNiveau[]
}

// Profil de l'utilisateur connecté (utilisateur:read + utilisateur:detail)
export interface Profil extends UtilisateurPublic {
  email:           string
  dateInscription: string
  niveaux:         UtilisateurNiveau[]
}

// Résultat d'un match (resultat:read)
export interface Resultat {
  id:         number
  scoreCamp1: number
  scoreCamp2: number
}

// Équipe — base commune aux deux contextes de sérialisation
export interface EquipeBase {
  id:           number
  nom:          string
  sport:        SportRef
  niveau:       NiveauRef | null
  localisation: string | null
  logo:         string | null
  club:         UtilisateurPublic
}

// equipe:list — inclut membresConfirmesCount, absent de equipe:read
export interface EquipeResume extends EquipeBase {
  membresConfirmesCount: number
}

// equipe_joueur:read + optionnellement equipe_joueur:invitation
// equipe absent quand embarqué dans EquipeDetail.membres (groupe invitation non actif)
export interface EquipeJoueur {
  id:          number
  utilisateur: UtilisateurPublic
  role:        'gestionnaire' | 'joueur'
  statut:      'en_attente' | 'confirme' | 'refuse'
  origine:     'invitation_club' | 'demande_joueur'
  equipe?:     EquipeResume
}

// equipe:read — inclut membres[], absent de equipe:list
export interface EquipeDetail extends EquipeBase {
  membres: EquipeJoueur[]
}

// match_camp:read ; equipe XOR joueur selon le type de sport
export interface MatchCamp {
  id:     number
  role:   'camp_1' | 'camp_2'
  statut: 'invite' | 'confirme' | 'refuse'
  equipe: EquipeResume | null
  joueur: UtilisateurPublic | null
}

// Avis déposé par l'utilisateur courant sur un match (game:read uniquement)
export interface MonAvis {
  ponctualite:    number
  fairPlay:       number
  niveauConforme: number
}

// game:list ou game:read
// description, resultat, monAvis présents uniquement sur game:read (GET /matchs/{id})
export interface Match {
  id:                     number
  sport:                  SportRef
  niveauRequis:           NiveauRef | null
  dateMatch:              string
  lieu:                   string | null
  latitude:               number | null
  longitude:              number | null
  statut:                 'en_attente' | 'confirme' | 'termine' | 'annule'
  createur:               UtilisateurPublic
  camps:                  MatchCamp[]
  nombreCamps:            number
  demandesEnAttenteCount: number
  monStatutDemande:       'en_attente' | 'acceptee' | 'refusee' | 'annulee' | null
  description?:           string | null
  resultat?:              Resultat | null
  monAvis?:               MonAvis | null
}

// message:read
export interface Message {
  id:         number
  contenu:    string
  dateEnvoi:  string
  expediteur: UtilisateurPublic
}

// demande:read ; clé JSON = "demandeur" (getDemandeur(), pas getJoueur())
export interface DemandeMatch {
  id:           number
  demandeur:    UtilisateurPublic
  equipe:       EquipeResume | null
  statut:       'en_attente' | 'acceptee' | 'refusee' | 'annulee'
  dateCreation: string
}

export const chargerSports = (): Promise<SportRef[]> =>
  fetch(`${API_URL}/sports`).then((r) => r.json())

// Profil
export const chargerProfil = (token: string) =>
  requete('GET', '/profil', null, token)

// Matchs
export const chargerMatchs = (token: string, filtres: Record<string, unknown> = {}) =>
  requete('GET', '/matchs' + construireParams(filtres), null, token)

/** Matchs créés ou rejoints par l'utilisateur connecté */
export const chargerMesMatchs = (token: string, filtres: Record<string, unknown> = {}) =>
  chargerMatchs(token, { mesMatchs: 1, ...filtres })

/** Matchs des équipes dont le joueur est membre confirmé */
export const chargerMesMatchsEquipes = (token: string) =>
  requete('GET', '/matchs?mesMatchsEquipes=1', null, token)

export const chargerMatch = (token: string, id: number) =>
  requete('GET', `/matchs/${id}`, null, token)

export const creerMatch = (token: string, donnees: Record<string, unknown>) =>
  requete('POST', '/matchs', donnees, token)

export const modifierMatch = (token: string, id: number, donnees: Record<string, unknown>) =>
  requete('PUT', `/matchs/${id}`, donnees, token)

export const supprimerMatch = (token: string, id: number) =>
  requete('DELETE', `/matchs/${id}`, null, token)

// Camps
export const ajouterCamp = (
  token: string,
  matchId: number,
  donnees: { equipeId?: number; joueurId?: number },
) => requete('POST', `/matchs/${matchId}/camps`, donnees, token)

export const repondreCamp = (token: string, matchId: number, campId: number, statut: string) =>
  requete('PATCH', `/matchs/${matchId}/camps/${campId}`, { statut }, token)

// Profil — logo
export const mettreAJourLogo = (token: string, logo: string | null) =>
  requete('PATCH', '/profil/logo', { logo }, token)

// Profil — mot de passe
export const changerMotDePasse = (token: string, ancienMotDePasse: string, nouveauMotDePasse: string) =>
  requete('PATCH', '/profil/mot-de-passe', { ancienMotDePasse, nouveauMotDePasse }, token)

// Profil — sports/niveaux
export const ajouterSportNiveau = (token: string, sportId: number, niveauId: number) =>
  requete('POST', '/profil/sports', { sportId, niveauId }, token)

export const modifierNiveauSport = (token: string, sportId: number, niveauId: number) =>
  requete('PATCH', `/profil/sports/${sportId}`, { niveauId }, token)

export const supprimerCamp = (token: string, matchId: number, campId: number) =>
  requete('DELETE', `/matchs/${matchId}/camps/${campId}`, null, token)

// Messages
export const chargerMessages = (token: string, matchId: number) =>
  requete('GET', `/matchs/${matchId}/messages`, null, token)

export const envoyerMessage = (token: string, matchId: number, contenu: string) =>
  requete('POST', `/matchs/${matchId}/messages`, { contenu }, token)

// Résultat
export const chargerResultat = (token: string, matchId: number) =>
  requete('GET', `/matchs/${matchId}/resultat`, null, token)

export const saisirResultat = (
  token: string,
  matchId: number,
  scoreCamp1: number,
  scoreCamp2: number,
) => requete('POST', `/matchs/${matchId}/resultat`, { scoreCamp1, scoreCamp2 }, token)

// Équipes
export const chargerEquipes = (token: string, filtres: Record<string, unknown> = {}) =>
  requete('GET', '/equipes' + construireParams(filtres), null, token)

export const chargerEquipe = (token: string, id: number) =>
  requete('GET', `/equipes/${id}`, null, token)

export const creerEquipe = (token: string, donnees: Record<string, unknown>) =>
  requete('POST', '/equipes', donnees, token)

export const modifierEquipe = (token: string, id: number, donnees: Record<string, unknown>) =>
  requete('PUT', `/equipes/${id}`, donnees, token)

export const supprimerEquipe = (token: string, id: number) =>
  requete('DELETE', `/equipes/${id}`, null, token)

export const inviterMembre = (token: string, equipeId: number, utilisateurId: number) =>
  requete('POST', `/equipes/${equipeId}/membres`, { utilisateur_id: utilisateurId }, token)

/** Demande d'adhésion joueur → club (POST membres sans utilisateur_id). */
export const demanderRejoindreEquipe = (token: string, equipeId: number) =>
  requete('POST', `/equipes/${equipeId}/membres`, {}, token)

/** @deprecated Utiliser inviterMembre */
export const ajouterMembre = inviterMembre

/** Réponse à une invitation (joueur) ou à une demande (club). */
export const repondreAdhesionEquipe = (
  token: string,
  equipeId: number,
  membreId: number,
  statut: 'confirme' | 'refuse',
) => requete('PATCH', `/equipes/${equipeId}/membres/${membreId}`, { statut }, token)

/** @deprecated Utiliser repondreAdhesionEquipe */
export const repondreInvitationEquipe = repondreAdhesionEquipe

export const retirerMembre = (token: string, equipeId: number, membreId: number) =>
  requete('DELETE', `/equipes/${equipeId}/membres/${membreId}`, null, token)

export const chargerInvitationsEquipes = (token: string) =>
  requete('GET', '/invitations-equipes', null, token)

export interface EspaceEquipesJoueur {
  mesEquipes:    EquipeJoueur[]
  invitations:   EquipeJoueur[]
  demandes:      EquipeJoueur[]
  nbInvitations: number
}

export const chargerEspaceEquipesJoueur = (token: string): Promise<EspaceEquipesJoueur> =>
  requete('GET', '/joueur/espace-equipes', null, token)

export const rechercherJoueurs = (token: string, q: string, sportId?: number) =>
  requete('GET', '/joueurs/recherche' + construireParams({ q, ...(sportId ? { sportId } : {}) }), null, token)

export const supprimerCompte = (token: string) =>
  requete('DELETE', '/profil', null, token)

// Demandes de match
export const demanderRejoindreMatch = (token: string, matchId: number, equipeId?: number) =>
  requete('POST', `/matchs/${matchId}/demandes`, equipeId ? { equipeId } : {}, token)

export const chargerMaDemande = (token: string, matchId: number) =>
  requete('GET', `/matchs/${matchId}/ma-demande`, null, token)

export const chargerDemandesMatch = (token: string, matchId: number, statut?: string) =>
  requete('GET', `/matchs/${matchId}/demandes` + (statut ? `?statut=${statut}` : ''), null, token)

export const repondreDemandeMatch = (
  token: string,
  matchId: number,
  demandeId: number,
  statut: 'acceptee' | 'refusee',
) => requete('PATCH', `/matchs/${matchId}/demandes/${demandeId}`, { statut }, token)

export const annulerDemandeMatch = (token: string, matchId: number, demandeId: number) =>
  requete('DELETE', `/matchs/${matchId}/demandes/${demandeId}`, null, token)

// Avis / Réputation
export const deposerAvis = (
  token: string,
  matchId: number,
  donnees: { ponctualite: number; fairPlay: number; niveauConforme: number },
) => requete('POST', `/matchs/${matchId}/avis`, donnees, token)

export const chargerProfilPublic = (token: string, id: number) =>
  requete('GET', `/profils/${id}`, null, token)

// Stats matchs
export interface StatBloc {
  joues:     number
  victoires: number
  defaites:  number
  nuls:      number
  ratio:     number
}

export interface MesStats {
  individuels: StatBloc
  equipes:     StatBloc | null
}

export const chargerMesStats = (token: string): Promise<MesStats> =>
  requete('GET', '/stats', null, token)
