<script setup lang="ts">
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import BarreNavigation from '@/components/layout/BarreNavigation.vue'
import PiedDePage from '@/components/layout/PiedDePage.vue'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const auth = useAuthStore()

const afficherLayout = computed(
  () => auth.estConnecte && route.path !== '/connexion' && route.path !== '/inscription',
)
</script>

<template>
  <!-- Nav : frère direct de #app, hors tout conteneur centré -->
  <BarreNavigation v-if="afficherLayout" />

  <!-- Contenu pages : pleine largeur ; centrage via .conteneur dans chaque vue -->
  <main class="app-main">
    <RouterView />
  </main>

  <!-- Pied : frère direct de #app, fond pleine largeur -->
  <PiedDePage v-if="afficherLayout" />
</template>
