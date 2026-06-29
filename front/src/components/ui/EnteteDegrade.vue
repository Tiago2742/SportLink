<script setup lang="ts">
defineProps<{
  titre: string
  sousTitre?: string
}>()
</script>

<template>
  <header class="entete-degrade">
    <div class="entete-degrade-contenu">
      <h1>{{ titre }}</h1>
      <p v-if="sousTitre" class="entete-sous-titre">{{ sousTitre }}</p>
    </div>
  </header>
</template>

<style scoped>
.entete-degrade {
  background: var(--degrade-entete);
  color: white;
  border-radius: var(--rayon-carte);
  padding: var(--espace-xl) var(--espace-l);
  margin-bottom: var(--espace-l);
  box-shadow: var(--ombre-carte-survol);
}

.entete-degrade h1 {
  color: white;
  margin-bottom: 0.35rem;
}

.entete-sous-titre {
  font-size: 0.95rem;
  opacity: 0.92;
  max-width: 36rem;
}
</style>
