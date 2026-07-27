import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import { setOnSessionExpiree } from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.mount('#app')

// Interception globale des 401 (token expiré/invalide) — hors /login_check
setOnSessionExpiree(() => {
  useAuthStore().seDeconnecter()
  router.push({ name: 'connexion', query: { sessionExpiree: '1' } })
})
