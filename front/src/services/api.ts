const API_URL = 'http://localhost:8000/api'

interface ErreurApi extends Error {
  statut: number
  donnees: unknown
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
export interface SportRef  { id: number; nom: string; type: string; niveaux: NiveauRef[] }

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

/** @deprecated Utiliser inviterMembre */
export const ajouterMembre = inviterMembre

export const repondreInvitationEquipe = (
  token: string,
  equipeId: number,
  membreId: number,
  statut: 'confirme' | 'refuse',
) => requete('PATCH', `/equipes/${equipeId}/membres/${membreId}`, { statut }, token)

export const retirerMembre = (token: string, equipeId: number, membreId: number) =>
  requete('DELETE', `/equipes/${equipeId}/membres/${membreId}`, null, token)

export const chargerInvitationsEquipes = (token: string) =>
  requete('GET', '/invitations-equipes', null, token)

export const rechercherJoueurs = (token: string, q: string) =>
  requete('GET', '/joueurs/recherche' + construireParams({ q }), null, token)
