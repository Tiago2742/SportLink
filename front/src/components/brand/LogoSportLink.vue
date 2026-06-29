<script setup lang="ts">
import { computed } from 'vue'
import logoComplet from '@/assets/logo_sportLink.png'

const pictoAssets = import.meta.glob<string>('@/assets/logo_sportlink_picto.png', {
  eager: true,
  import: 'default',
})
const logoPictoDedie = Object.values(pictoAssets)[0] ?? null

const props = withDefaults(
  defineProps<{
    variant?: 'complet' | 'picto'
    /** nav ≈ 40px ; auth = mise en avant connexion/inscription */
    taille?: 'defaut' | 'nav' | 'auth'
    /** Sur fond clair : atténue un éventuel fond noir du PNG (multiply) */
    fondClair?: boolean
  }>(),
  { variant: 'complet', taille: 'defaut', fondClair: true },
)

const utiliseRecadragePicto = computed(
  () => props.variant === 'picto' && logoPictoDedie === null,
)

const src = computed(() => {
  if (props.variant === 'picto' && logoPictoDedie) {
    return logoPictoDedie
  }
  return logoComplet
})

const libelle = computed(() =>
  props.variant === 'picto' ? 'SportLink' : 'SportLink — plateforme sport amateur',
)
</script>

<template>
  <span
    class="logo-sportlink"
    :class="[
      `logo-sportlink--${variant}`,
      `logo-sportlink--taille-${taille}`,
      { 'logo-sportlink--fond-clair': fondClair, 'logo-sportlink--recadre': utiliseRecadragePicto },
    ]"
  >
    <img
      :src="src"
      :alt="libelle"
      class="logo-sportlink-img"
      decoding="async"
    />
  </span>
</template>

<style scoped>
.logo-sportlink {
  display: inline-flex;
  align-items: center;
  line-height: 0;
}

.logo-sportlink-img {
  display: block;
  width: auto;
  max-width: 100%;
  height: auto;
  object-fit: contain;
}

.logo-sportlink--fond-clair .logo-sportlink-img {
  mix-blend-mode: multiply;
}

.logo-sportlink--complet.logo-sportlink--taille-defaut .logo-sportlink-img {
  height: 40px;
  width: auto;
  max-width: none;
}

.logo-sportlink--complet.logo-sportlink--taille-nav .logo-sportlink-img {
  height: 40px;
  width: auto;
  max-width: none;
}

.logo-sportlink--complet.logo-sportlink--taille-auth .logo-sportlink-img {
  height: 80px;
  width: auto;
  max-width: none;
}

.logo-sportlink--picto .logo-sportlink-img {
  height: 2rem;
  width: 2rem;
  max-height: 32px;
  max-width: 32px;
}

.logo-sportlink--picto.logo-sportlink--recadre .logo-sportlink-img {
  object-fit: cover;
  object-position: 6% center;
}
</style>
