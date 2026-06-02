<script setup lang="ts">
import { RouterView } from 'vue-router'
import BarreNavigation from '@/components/layout/BarreNavigation.vue'
import PiedDePage from '@/components/layout/PiedDePage.vue'
</script>

<template>
  <BarreNavigation v-if="$route.path !== '/connexion' && $route.path !== '/inscription'" />
  <main class="contenu-principal">
    <RouterView />
  </main>
  <PiedDePage v-if="$route.path !== '/connexion' && $route.path !== '/inscription'" />
</template>

<style>
.contenu-principal {
  flex: 1;
}
</style>
