<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  statut: string
}>()

const libelles: Record<string, string> = {
  en_attente: 'En attente',
  confirme: 'Confirmé',
  termine: 'Terminé',
  annule: 'Annulé',
  invite: 'Invité',
  refuse: 'Refusé',
}

const classeStatut = computed(() => {
  const correspondances: Record<string, string> = {
    confirme: 'confirme',
    en_attente: 'attente',
    invite: 'attente',
    refuse: 'refuse',
    annule: 'refuse',
    termine: 'termine',
  }
  return `badge-${correspondances[props.statut] ?? 'neutre'}`
})
</script>

<template>
  <span class="badge" :class="classeStatut">
    {{ libelles[statut] ?? statut }}
  </span>
</template>
