<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useSports } from '@/composables/useSports'
import AppSelect from '@/components/form/AppSelect.vue'

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

// Computed v-models : évitent des event handlers natifs
const sportComputed = computed({
  get: (): number | '' => props.sport,
  set: (val: number | string | '') => {
    emit('update:sport', val === '' ? '' : (val as number))
    emit('update:niveau', '')
  },
})

const niveauComputed = computed({
  get: (): number | '' => props.niveau,
  set: (val: number | string | '') => {
    emit('update:niveau', val === '' ? '' : (val as number))
  },
})
</script>

<template>
  <div class="filtres-sport">
    <AppSelect
      v-model="sportComputed"
      :options="listeFiltree.map(s => ({ value: s.id, label: s.nom }))"
      :searchable="true"
      placeholder="Tous les sports"
    />

    <AppSelect
      v-model="niveauComputed"
      :options="niveauxDisponibles.map(n => ({ value: n.id, label: n.libelle }))"
      :searchable="false"
      placeholder="Tous niveaux"
      :disabled="sport === ''"
    />
  </div>
</template>

<style scoped>
.filtres-sport {
  display: flex;
  flex-direction: column;
  gap: var(--espace-s);
}
</style>
