<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useSports } from '@/composables/useSports'

const props = defineProps<{
  sport: number | ''
  niveau: number | ''
  sports?: { id: number; nom: string }[]
}>()

const emit = defineEmits<{
  'update:sport':  [val: number | '']
  'update:niveau': [val: number | '']
}>()

const { listeSports, niveauxPour, chargerCatalogue } = useSports()

onMounted(chargerCatalogue)

const listeFiltree = computed(() => props.sports ?? listeSports.value)

const niveauxDisponibles = computed(() =>
  props.sport !== '' ? niveauxPour(props.sport as number) : [],
)

function onSportChange(event: Event) {
  const val = (event.target as HTMLSelectElement).value
  emit('update:sport', val !== '' ? Number(val) : '')
  emit('update:niveau', '')
}

function onNiveauChange(event: Event) {
  const val = (event.target as HTMLSelectElement).value
  emit('update:niveau', val !== '' ? Number(val) : '')
}
</script>

<template>
  <div class="filtres-sport">
    <select class="champ" :value="sport !== '' ? sport : ''" @change="onSportChange">
      <option value="">Tous les sports</option>
      <option v-for="s in listeFiltree" :key="s.id" :value="s.id">{{ s.nom }}</option>
    </select>

    <select class="champ" :value="niveau !== '' ? niveau : ''" @change="onNiveauChange" :disabled="sport === ''">
      <option value="">Tous niveaux</option>
      <option v-for="n in niveauxDisponibles" :key="n.id" :value="n.id">{{ n.libelle }}</option>
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
