<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { initialesEquipe, type EquipeAffichage } from '@/utils/equipeAffichage'

const props = withDefaults(
  defineProps<{
    equipe: EquipeAffichage | null | undefined
    taille?: 'sm' | 'md' | 'lg'
  }>(),
  { taille: 'md' },
)

const imageErreur = ref(false)

watch(
  () => props.equipe?.logo,
  () => {
    imageErreur.value = false
  },
)

const initiales = computed(() => initialesEquipe(props.equipe?.nom))

const logoUrl = computed(() => {
  const url = props.equipe?.logo?.trim()
  return url || null
})

const afficherImage = computed(() => !!logoUrl.value && !imageErreur.value)
</script>

<template>
  <div class="avatar-equipe" :class="'avatar-equipe-' + taille" role="img" :aria-label="equipe?.nom ?? 'Équipe'">
    <img
      v-if="afficherImage"
      :src="logoUrl!"
      :alt="`Logo ${equipe?.nom ?? 'équipe'}`"
      class="avatar-equipe-img"
      loading="lazy"
      @error="imageErreur = true"
    />
    <span v-else class="avatar-equipe-initiales">{{ initiales }}</span>
  </div>
</template>

<style scoped>
.avatar-equipe {
  flex-shrink: 0;
  border-radius: var(--rayon-carte);
  overflow: hidden;
  background: var(--degrade-avatar);
  border: 2px solid rgba(76, 175, 80, 0.25);
  box-shadow: var(--ombre-carte);
  display: flex;
  align-items: center;
  justify-content: center;
  transition:
    transform var(--transition-interaction),
    box-shadow var(--transition-interaction);
}

.avatar-equipe-sm {
  width: 40px;
  height: 40px;
  font-size: 0.75rem;
}

.avatar-equipe-md {
  width: 72px;
  height: 72px;
  font-size: 1.1rem;
}

.avatar-equipe-lg {
  width: 96px;
  height: 96px;
  font-size: 1.35rem;
}

.avatar-equipe-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.avatar-equipe-initiales {
  font-weight: 800;
  color: var(--couleur-primaire);
  letter-spacing: 0.04em;
}
</style>
