<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  statut: string
}>()

const libelles: Record<string, string> = {
  confirmé: 'Confirmé',
  invité: 'En attente',
  refusé: 'Refusé',
  ouvert: 'Ouvert',
  complet: 'Complet',
  terminé: 'Terminé',
  'à venir': 'À venir',
}

const classeStatut = computed(() => {
  const correspondances: Record<string, string> = {
    confirmé: 'confirme',
    ouvert: 'confirme',
    'à venir': 'confirme',
    invité: 'attente',
    refusé: 'refuse',
    complet: 'refuse',
    terminé: 'termine',
  }
  return `badge-${correspondances[props.statut] ?? 'neutre'}`
})
</script>

<template>
  <span class="badge" :class="classeStatut">
    {{ libelles[statut] ?? statut }}
  </span>
</template>

<style scoped>
.badge {
  display: inline-block;
  padding: 0.2rem 0.7rem;
  border-radius: var(--rayon-badge);
  font-size: 0.78rem;
  font-weight: 600;
  white-space: nowrap;
}

.badge-confirme {
  background: var(--couleur-confirme-fond);
  color: var(--couleur-confirme);
}

.badge-attente {
  background: var(--couleur-attente-fond);
  color: var(--couleur-attente);
}

.badge-refuse {
  background: var(--couleur-refuse-fond);
  color: var(--couleur-refuse);
}

.badge-termine,
.badge-neutre {
  background: #f5f5f5;
  color: #757575;
}
</style>
