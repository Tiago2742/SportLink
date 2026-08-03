<script setup lang="ts">
import { nomAffichage, type UtilisateurAffichage } from '@/utils/nomAffichage'

defineProps<{
  utilisateur: (UtilisateurAffichage & { id?: number | null }) | null | undefined
}>()
</script>

<template>
  <RouterLink
    v-if="utilisateur?.id"
    :to="`/profils/${utilisateur.id}`"
    class="lien-utilisateur"
  >{{ nomAffichage(utilisateur) }}</RouterLink>
  <span v-else>{{ nomAffichage(utilisateur) }}</span>
</template>

<style scoped>
.lien-utilisateur {
  color: inherit;
  font-weight: inherit;
  font-size: inherit;
  text-decoration: none;
  border-bottom: 1px solid transparent;
  transition: color 0.15s, border-color 0.15s;
}

.lien-utilisateur:hover {
  color: var(--couleur-primaire);
  border-bottom-color: var(--couleur-primaire);
}
</style>
