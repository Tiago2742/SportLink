import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import {
  chargerEspaceEquipesJoueur,
  type EspaceEquipesJoueur,
} from '@/services/api'
import { fusionnerAdhesions, type AdhesionEquipe } from '@/utils/adhesionEquipe'

const nbInvitations = ref(0)
let chargementGlobal = false

const espaceCourant = ref<EspaceEquipesJoueur | null>(null)
const adhesionsFusionnees = ref<AdhesionEquipe[]>([])

export function useEspaceEquipesJoueur() {
  const auth = useAuthStore()

  async function chargerEspace(): Promise<EspaceEquipesJoueur> {
    if (!auth.token) {
      const vide = { mesEquipes: [], invitations: [], demandes: [], nbInvitations: 0 }
      espaceCourant.value = vide
      nbInvitations.value = 0
      adhesionsFusionnees.value = []
      return vide
    }

    const espace = await chargerEspaceEquipesJoueur(auth.token)
    espaceCourant.value = espace
    nbInvitations.value = espace.nbInvitations ?? espace.invitations?.length ?? 0
    adhesionsFusionnees.value = fusionnerAdhesions(espace)
    return espace
  }

  async function rafraichirCompteurInvitations(): Promise<void> {
    if (!auth.token || auth.utilisateur?.type !== 'joueur') {
      nbInvitations.value = 0
      return
    }
    if (chargementGlobal) return
    chargementGlobal = true
    try {
      const espace = await chargerEspaceEquipesJoueur(auth.token)
      nbInvitations.value = espace.nbInvitations ?? 0
      if (espaceCourant.value) {
        espaceCourant.value = espace
        adhesionsFusionnees.value = fusionnerAdhesions(espace)
      }
    } catch {
      /* ignore nav badge */
    } finally {
      chargementGlobal = false
    }
  }

  return {
    nbInvitations,
    espaceCourant,
    adhesionsFusionnees,
    chargerEspace,
    rafraichirCompteurInvitations,
  }
}
