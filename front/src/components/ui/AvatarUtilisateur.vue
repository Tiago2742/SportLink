<script setup lang="ts">
import { computed } from 'vue'
import { initialesUtilisateur, nomAffichage, type UtilisateurAffichage } from '@/utils/nomAffichage'

const props = withDefaults(
  defineProps<{
    utilisateur: UtilisateurAffichage | null | undefined
    taille?: 'sm' | 'md'
  }>(),
  { taille: 'sm' },
)

const initiales = computed(() => initialesUtilisateur(props.utilisateur))
const libelle = computed(() => nomAffichage(props.utilisateur) || 'Utilisateur')
</script>

<template>
  <div
    class="avatar-utilisateur"
    :class="`avatar-utilisateur--${taille}`"
    role="img"
    :aria-label="libelle"
  >
    <span class="avatar-utilisateur-initiales">{{ initiales }}</span>
  </div>
</template>

<style scoped>
.avatar-utilisateur {
  flex-shrink: 0;
  border-radius: 50%;
  overflow: hidden;
  background: var(--degrade-primaire);
  border: 2px solid rgba(255, 255, 255, 0.85);
  box-shadow: var(--ombre-carte);
  display: flex;
  align-items: center;
  justify-content: center;
  transition:
    transform var(--transition-interaction),
    box-shadow var(--transition-interaction);
}

.avatar-utilisateur--sm {
  width: 34px;
  height: 34px;
  font-size: 0.68rem;
}

.avatar-utilisateur--md {
  width: 40px;
  height: 40px;
  font-size: 0.78rem;
}

.avatar-utilisateur-initiales {
  font-weight: 800;
  color: #ffffff;
  letter-spacing: 0.04em;
  line-height: 1;
  user-select: none;
}
</style>
