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
  border-radius: var(--rayon-carte, 12px);
  overflow: hidden;
  background: linear-gradient(135deg, var(--couleur-primaire-tres-claire), var(--couleur-info-fond));
  border: 2px solid var(--couleur-bordure);
  display: flex;
  align-items: center;
  justify-content: center;
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
  letter-spacing: 0.02em;
}
</style>
