import { ref, computed } from 'vue'
import { chargerSports, type SportRef, type NiveauRef } from '@/services/api'

const catalogue = ref<SportRef[]>([])
let chargementLance = false

export function useSports() {
  async function chargerCatalogue() {
    if (chargementLance) return
    chargementLance = true
    try {
      catalogue.value = await chargerSports()
    } catch {
      chargementLance = false
    }
  }

  const listeSports = computed(() => catalogue.value)

  function niveauxPour(sportId: number): NiveauRef[] {
    return catalogue.value.find((s) => s.id === sportId)?.niveaux ?? []
  }

  function nomSport(sportId: number): string {
    return catalogue.value.find((s) => s.id === sportId)?.nom ?? ''
  }

  function nomNiveau(niveauId: number): string {
    for (const sport of catalogue.value) {
      const n = sport.niveaux?.find((n) => n.id === niveauId)
      if (n) return n.libelle
    }
    return ''
  }

  return { catalogue, listeSports, niveauxPour, nomSport, nomNiveau, chargerCatalogue }
}
