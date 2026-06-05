/** Utilisateur ou sous-objet API (créateur, joueur, expéditeur…). */
export type UtilisateurAffichage = {
  type?: string
  nom?: string | null
  prenom?: string | null
}

/** Nom affiché : raison sociale seule (club) ou prénom + nom (joueur). */
export function nomAffichage(user: UtilisateurAffichage | null | undefined): string {
  if (!user) return ''

  const nom = user.nom?.trim() ?? ''
  if (user.type === 'club') {
    return nom || 'Club'
  }

  const prenom = user.prenom?.trim() ?? ''
  if (prenom && nom) return `${prenom} ${nom}`
  return prenom || nom || ''
}

/** Initiales pour avatar (2 caractères). */
export function initialesUtilisateur(user: UtilisateurAffichage | null | undefined): string {
  if (!user) return '??'

  if (user.type === 'club') {
    return (user.nom?.trim().substring(0, 2) || '??').toUpperCase()
  }

  const p = user.prenom?.trim()[0] ?? ''
  const n = user.nom?.trim()[0] ?? ''
  const initiales = `${p}${n}`.toUpperCase()
  return initiales || '??'
}
