<script setup lang="ts">
import type { Component } from 'vue'

defineProps<{
  icone: Component
  label?: string
}>()
</script>

<template>
  <span class="icone-section" :aria-hidden="label ? undefined : true" :title="label">
    <component :is="icone" />
  </span>
</template>
