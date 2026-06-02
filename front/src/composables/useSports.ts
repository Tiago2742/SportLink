import { ref, computed } from 'vue'
import { chargerSports } from '@/services/api'

const catalogue = ref<Record<string, string[]>>({})
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

  const listeSports = computed(() => Object.keys(catalogue.value))

  function niveauxPour(sport: string): string[] {
    return catalogue.value[sport] ?? []
  }

  return { catalogue, listeSports, niveauxPour, chargerCatalogue }
}
