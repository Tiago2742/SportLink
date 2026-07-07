import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { connexion as apiConnexion, inscrire as apiInscrire, chargerProfil } from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('token'))
  const utilisateur = ref<any | null>(
    JSON.parse(localStorage.getItem('utilisateur') || 'null'),
  )

  const estConnecte = computed(() => !!token.value)

  async function seConnecter(email: string, motDePasse: string) {
    const data = await apiConnexion(email, motDePasse)
    token.value = data.token
    localStorage.setItem('token', data.token)
    const profil = await chargerProfil(data.token)
    utilisateur.value = profil
    localStorage.setItem('utilisateur', JSON.stringify(profil))
  }

  async function sInscrire(donnees: Record<string, unknown> & { email: string; password: string }) {
    await apiInscrire(donnees)
    await seConnecter(donnees.email, donnees.password)
  }

  function seDeconnecter() {
    token.value = null
    utilisateur.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('utilisateur')
  }

  /** Met à jour le profil en mémoire ET dans localStorage (évite de perdre les niveaux après F5). */
  function rafraichirProfil(profil: any) {
    utilisateur.value = profil
    localStorage.setItem('utilisateur', JSON.stringify(profil))
  }

  return { token, utilisateur, estConnecte, seConnecter, sInscrire, seDeconnecter, rafraichirProfil }
})
