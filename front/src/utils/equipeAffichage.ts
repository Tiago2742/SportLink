export type EquipeAffichage = {
  nom: string
  logo?: string | null
}

export function initialesEquipe(nom: string | null | undefined): string {
  const n = nom?.trim() ?? ''
  return n.length >= 2 ? n.substring(0, 2).toUpperCase() : n.length === 1 ? n.toUpperCase() : '??'
}

export function libelleRoleEquipe(role?: string | null): string {
  if (role === 'gestionnaire') return 'Gestionnaire'
  return 'Joueur'
}
