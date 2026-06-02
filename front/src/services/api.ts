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
export const chargerSports = (): Promise<Record<string, string[]>> =>
  fetch(`${API_URL}/sports`).then((r) => r.json())

// Profil
export const chargerProfil = (token: string) =>
  requete('GET', '/profil', null, token)

// Matchs
export const chargerMatchs = (token: string, filtres: Record<string, unknown> = {}) =>
  requete('GET', '/matchs' + construireParams(filtres), null, token)

export const chargerMatch = (token: string, id: number) =>
  requete('GET', `/matchs/${id}`, null, token)

export const creerMatch = (token: string, donnees: Record<string, unknown>) =>
  requete('POST', '/matchs', donnees, token)

export const modifierMatch = (token: string, id: number, donnees: Record<string, unknown>) =>
  requete('PUT', `/matchs/${id}`, donnees, token)

export const supprimerMatch = (token: string, id: number) =>
  requete('DELETE', `/matchs/${id}`, null, token)

// Participations
export const participer = (token: string, matchId: number) =>
  requete('POST', `/matchs/${matchId}/participations`, null, token)

export const modifierStatutParticipation = (
  token: string,
  matchId: number,
  participationId: number,
  statut: string,
) => requete('PATCH', `/matchs/${matchId}/participations/${participationId}`, { statut }, token)

export const annulerParticipation = (token: string, matchId: number, participationId: number) =>
  requete('DELETE', `/matchs/${matchId}/participations/${participationId}`, null, token)

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
  scoreEquipe1: number,
  scoreEquipe2: number,
) => requete('POST', `/matchs/${matchId}/resultat`, { scoreEquipe1, scoreEquipe2 }, token)

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

export const ajouterMembre = (
  token: string,
  equipeId: number,
  utilisateurId: number,
  role: string | null = null,
) => requete('POST', `/equipes/${equipeId}/membres`, { utilisateur_id: utilisateurId, role }, token)

export const retirerMembre = (token: string, equipeId: number, membreId: number) =>
  requete('DELETE', `/equipes/${equipeId}/membres/${membreId}`, null, token)
