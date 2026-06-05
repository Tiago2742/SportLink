export type AdhesionEquipe = {
  id: number
  statut?: string
  origine?: string
  role?: string
  equipe?: { id: number }
}

/** Fusionne toutes les adhésions connues du joueur (espace équipe). */
export function fusionnerAdhesions(espace: {
  mesEquipes?: AdhesionEquipe[]
  invitations?: AdhesionEquipe[]
  demandes?: AdhesionEquipe[]
}): AdhesionEquipe[] {
  const parEquipe = new Map<number, AdhesionEquipe>()

  for (const liste of [espace.mesEquipes, espace.invitations, espace.demandes]) {
    for (const a of liste ?? []) {
      const eqId = a.equipe?.id
      if (!eqId) continue
      const existant = parEquipe.get(eqId)
      if (!existant || prioriteAdhesion(a) > prioriteAdhesion(existant)) {
        parEquipe.set(eqId, a)
      }
    }
  }

  return [...parEquipe.values()]
}

function prioriteAdhesion(a: AdhesionEquipe): number {
  if (a.statut === 'confirme') return 3
  if (a.statut === 'en_attente') return 2
  if (a.statut === 'refuse') return 1
  return 0
}

export function adhesionPourEquipe(
  equipeId: number,
  adhesions: AdhesionEquipe[],
): AdhesionEquipe | undefined {
  return adhesions.find((a) => a.equipe?.id === equipeId)
}

export function peutDemanderRejoindre(
  equipeId: number,
  adhesions: AdhesionEquipe[],
): { autorise: boolean; raison?: string } {
  const a = adhesionPourEquipe(equipeId, adhesions)
  if (!a) return { autorise: true }
  if (a.statut === 'refuse') return { autorise: true }

  if (a.statut === 'confirme') {
    return { autorise: false, raison: 'Vous êtes déjà membre de cette équipe' }
  }

  if (a.statut === 'en_attente') {
    if (a.origine === 'invitation_club') {
      return { autorise: false, raison: 'Invitation en attente — répondez dans l\'onglet Invitations' }
    }
    return { autorise: false, raison: 'Demande déjà envoyée' }
  }

  return { autorise: false, raison: 'Adhésion en cours' }
}
