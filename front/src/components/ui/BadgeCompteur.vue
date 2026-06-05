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
  min-width: 1.15rem;
  height: 1.15rem;
  padding: 0 0.3rem;
  margin-left: 0.35rem;
  font-size: 0.68rem;
  font-weight: 700;
  line-height: 1;
  color: white;
  background: var(--couleur-refuse, #e53935);
  border-radius: 999px;
  vertical-align: middle;
}
</style>
