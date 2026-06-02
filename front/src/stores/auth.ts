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

  async function sInscrire(donnees: Record<string, string>) {
    await apiInscrire(donnees)
    await seConnecter(donnees.email, donnees.password)
  }

  function seDeconnecter() {
    token.value = null
    utilisateur.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('utilisateur')
  }

  return { token, utilisateur, estConnecte, seConnecter, sInscrire, seDeconnecter }
})
