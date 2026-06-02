<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useSports } from '@/composables/useSports'

const props = defineProps<{
  sport: string
  niveau: string
}>()

const emit = defineEmits<{
  'update:sport': [val: string]
  'update:niveau': [val: string]
}>()

const { listeSports, niveauxPour, chargerCatalogue } = useSports()

onMounted(chargerCatalogue)

const niveauxDisponibles = computed(() => niveauxPour(props.sport))

function onSportChange(event: Event) {
  const val = (event.target as HTMLSelectElement).value
  emit('update:sport', val)
  emit('update:niveau', '')
}

function onNiveauChange(event: Event) {
  emit('update:niveau', (event.target as HTMLSelectElement).value)
}
</script>

<template>
  <div class="filtres-sport">
    <select class="champ" :value="sport" @change="onSportChange">
      <option value="">Tous les sports</option>
      <option v-for="s in listeSports" :key="s" :value="s">{{ s }}</option>
    </select>

    <select class="champ" :value="niveau" @change="onNiveauChange" :disabled="!sport">
      <option value="">Tous niveaux</option>
      <option v-for="n in niveauxDisponibles" :key="n" :value="n">{{ n }}</option>
    </select>
  </div>
</template>

<style scoped>
.filtres-sport {
  display: flex;
  flex-direction: column;
  gap: var(--espace-s);
}
</style>
