<script setup lang="ts">
import type { Component } from 'vue'

withDefaults(
  defineProps<{
    icone: Component
    discret?: boolean
    taille?: number
  }>(),
  { discret: false, taille: 16 },
)
</script>

<template>
  <span class="icone-ligne" :class="{ 'icone-ligne--discret': discret }">
    <component :is="icone" :size="taille" aria-hidden="true" />
    <span><slot /></span>
  </span>
</template>
