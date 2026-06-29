<script setup lang="ts">
defineProps<{
  nombre: number
}>()
</script>

<template>
  <span v-if="nombre > 0" class="badge-compteur" :aria-label="`${nombre} en attente`">
    {{ nombre > 99 ? '99+' : nombre }}
  </span>
</template>

<style scoped>
.badge-compteur {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.2rem;
  height: 1.2rem;
  padding: 0 0.35rem;
  margin-left: 0.35rem;
  font-size: 0.68rem;
  font-weight: 800;
  line-height: 1;
  color: var(--couleur-accent-texte);
  background: var(--couleur-accent);
  border: 1px solid rgba(26, 51, 0, 0.12);
  border-radius: 999px;
  vertical-align: middle;
  box-shadow: 0 2px 6px rgba(154, 230, 0, 0.45);
}
</style>
