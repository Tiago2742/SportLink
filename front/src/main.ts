import './assets/main.css'
import 'leaflet/dist/leaflet.css'
import 'vue-cal/dist/vuecal.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import L from 'leaflet'
import markerIconUrl from 'leaflet/dist/images/marker-icon.png'
import markerIconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png'
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png'

import App from './App.vue'
import router from './router'
import { setOnSessionExpiree } from '@/services/api'
import { useAuthStore } from '@/stores/auth'

// Fix Leaflet default marker icon detection (broken by Vite's asset bundler)
delete (L.Icon.Default.prototype as any)._getIconUrl
L.Icon.Default.mergeOptions({
  iconUrl: markerIconUrl,
  iconRetinaUrl: markerIconRetinaUrl,
  shadowUrl: markerShadowUrl,
})

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.mount('#app')

// Interception globale des 401 (token expiré/invalide) — hors /login_check
setOnSessionExpiree(() => {
  useAuthStore().seDeconnecter()
  router.push({ name: 'connexion', query: { sessionExpiree: '1' } })
})
