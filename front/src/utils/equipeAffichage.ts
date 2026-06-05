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

export function libelleStatutMembre(statut?: string | null): string {
  if (statut === 'en_attente') return 'En attente'
  if (statut === 'confirme') return 'Confirmé'
  if (statut === 'refuse') return 'Refusé'
  return statut ?? ''
}

export function classeBadgeStatutMembre(statut?: string | null): string {
  if (statut === 'en_attente') return 'badge-attente'
  if (statut === 'confirme') return 'badge-confirme'
  if (statut === 'refuse') return 'badge-refuse'
  return ''
}
