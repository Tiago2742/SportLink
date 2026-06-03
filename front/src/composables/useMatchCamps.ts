import { nomAffichage, type UtilisateurAffichage } from '@/utils/nomAffichage'

/** Résumé d'une inscription au match (côté API). */
export type CampResume = {
  role?: string
  joueur?: UtilisateurAffichage & { id: number }
  equipe?: { id?: number; nom?: string; club?: UtilisateurAffichage & { id: number } }
}

/** Libellé d'un participant (équipe ou joueur) pour l'affichage du score — R6 */
export function nomParticipant(camp: CampResume | null | undefined): string {
  if (!camp) return 'Adversaire à définir'
  if (camp.equipe?.nom) return camp.equipe.nom
  if (camp.joueur) {
    return nomAffichage(camp.joueur)
  }
  return 'Participant inconnu'
}

/** @deprecated alias */
export const nomCamp = nomParticipant

export function campParRole(
  camps: CampResume[] | undefined,
  role: 'camp_1' | 'camp_2',
) {
  return camps?.find((c) => c.role === role) ?? null
}

export function libellePlacesMatch(nombreInscrits: number): string {
  if (nombreInscrits >= 2) return 'Complet · 2/2 inscrits'
  if (nombreInscrits === 1) return '1/2 inscrit · 1 place libre'
  return '0/2 inscrit · 2 places libres'
}

export function utilisateurEstInscrit(
  match: { createur?: { id: number }; camps?: CampResume[] },
  utilisateurId?: number,
): boolean {
  const uid = utilisateurId != null ? Number(utilisateurId) : null
  if (uid == null || Number.isNaN(uid)) return false
  if (Number(match.createur?.id) === uid) return true
  return (
    match.camps?.some(
      (c) =>
        Number(c.joueur?.id) === uid || Number(c.equipe?.club?.id) === uid,
    ) ?? false
  )
}
