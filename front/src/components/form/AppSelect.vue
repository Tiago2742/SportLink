<script setup lang="ts">
import { computed } from 'vue'
import Multiselect from '@vueform/multiselect'

export interface SelectOption {
  value: number | string
  label: string
}

const props = withDefaults(defineProps<{
  modelValue: number | string | null | undefined
  options: SelectOption[]
  searchable?: boolean
  clearable?: boolean
  placeholder?: string
  disabled?: boolean
  loading?: boolean
  noResultsText?: string
}>(), {
  searchable: false,
  clearable: true,
  placeholder: 'Sélectionner',
  disabled: false,
  loading: false,
  noResultsText: 'Aucun résultat',
})

const emit = defineEmits<{
  'update:modelValue': [value: number | string | '']
  'change': [value: number | string | '']
}>()

// Convertit '' et undefined en null (état "vide" attendu par la lib),
// et null en '' à l'émission (compatibilité avec les refs parent typées number | '').
const internalValue = computed({
  get: () =>
    props.modelValue === '' || props.modelValue === null || props.modelValue === undefined
      ? null
      : props.modelValue,
  set: (val: number | string | null) => {
    const v = val === null ? '' : val
    emit('update:modelValue', v)
    emit('change', v)
  },
})
</script>

<template>
  <Multiselect
    v-model="internalValue"
    :options="options"
    :searchable="searchable"
    :placeholder="placeholder"
    :disabled="disabled"
    :loading="loading"
    :no-results-text="noResultsText"
    value-prop="value"
    label="label"
    track-by="value"
    :can-clear="clearable"
    :can-deselect="true"
    :close-on-select="true"
    class="app-select"
  />
</template>

<style>
@import '@vueform/multiselect/themes/default.css';

.app-select {
  width: 100%;
  --ms-font-size: 0.9rem;
  --ms-line-height: 1.5;
  --ms-bg: var(--couleur-fond-blanc);
  --ms-bg-disabled: var(--couleur-fond);
  --ms-border-color: var(--couleur-bordure);
  --ms-border-width: 1.5px;
  --ms-border-color-active: var(--couleur-primaire-claire);
  --ms-radius: var(--rayon-champ);
  --ms-py: 0.65rem;
  --ms-px: 0.95rem;
  --ms-ring-width: 3px;
  --ms-ring-color: rgba(76, 175, 80, 0.35);
  --ms-placeholder-color: var(--couleur-texte-discret);
  --ms-max-height: 15rem;
  --ms-option-font-size: 0.9rem;
  --ms-option-line-height: 1.4;
  --ms-option-bg: var(--couleur-fond-blanc);
  --ms-option-color: var(--couleur-texte);
  --ms-option-bg-pointed: var(--couleur-primaire-tres-claire);
  --ms-option-color-pointed: var(--couleur-primaire);
  --ms-option-bg-selected: var(--couleur-primaire);
  --ms-option-color-selected: #ffffff;
  --ms-option-bg-selected-pointed: var(--couleur-primaire);
  --ms-option-color-selected-pointed: #ffffff;
  --ms-caret-color: var(--couleur-texte-discret);
  --ms-spinner-color: var(--couleur-primaire);
  --ms-clear-color: var(--couleur-texte-discret);
}
</style>
