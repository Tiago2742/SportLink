<script setup lang="ts">
import { ref } from 'vue'
import { MapPin } from 'lucide-vue-next'

defineProps<{
  lieu: string
  latitude: number | null
  longitude: number | null
  required?: boolean
}>()

const emit = defineEmits<{
  'update:lieu': [value: string]
  'update:latitude': [value: number | null]
  'update:longitude': [value: number | null]
}>()

interface Suggestion {
  label: string
  latitude: number
  longitude: number
}

const suggestions = ref<Suggestion[]>([])
const ouvert = ref(false)
const chargement = ref(false)
let debounceTimer: ReturnType<typeof setTimeout> | null = null

function onInput(event: Event) {
  const valeur = (event.target as HTMLInputElement).value
  emit('update:lieu', valeur)
  // Effacer les coordonnées dès que le texte change (sélection précédente invalidée)
  emit('update:latitude', null)
  emit('update:longitude', null)

  if (debounceTimer) clearTimeout(debounceTimer)
  if (valeur.trim().length < 3) {
    suggestions.value = []
    ouvert.value = false
    return
  }
  debounceTimer = setTimeout(() => rechercherAdresse(valeur), 300)
}

async function rechercherAdresse(q: string) {
  chargement.value = true
  try {
    const url = `https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(q)}&limit=5`
    const reponse = await fetch(url)
    if (!reponse.ok) return
    const donnees = await reponse.json()
    suggestions.value = (donnees.features ?? []).map((f: { properties: { label: string }; geometry: { coordinates: [number, number] } }) => ({
      label: f.properties.label,
      latitude:  f.geometry.coordinates[1],
      longitude: f.geometry.coordinates[0],
    }))
    ouvert.value = suggestions.value.length > 0
  } catch {
    // Dégradation silencieuse : l'utilisateur peut continuer en texte libre
    suggestions.value = []
    ouvert.value = false
  } finally {
    chargement.value = false
  }
}

function selectionner(s: Suggestion) {
  emit('update:lieu', s.label)
  emit('update:latitude', s.latitude)
  emit('update:longitude', s.longitude)
  suggestions.value = []
  ouvert.value = false
}

function fermer() {
  // Délai pour laisser le clic sur une suggestion s'enregistrer avant la fermeture
  setTimeout(() => { ouvert.value = false }, 150)
}
</script>

<template>
  <div class="autocomplete-lieu">
    <div class="autocomplete-input-wrapper">
      <span class="autocomplete-icone" aria-hidden="true">
        <MapPin :size="16" stroke-width="2" />
      </span>
      <input
        :value="lieu"
        type="text"
        class="champ autocomplete-input"
        placeholder="Saisissez une adresse..."
        autocomplete="off"
        :required="required"
        @input="onInput"
        @blur="fermer"
        @keydown.escape="ouvert = false"
      />
      <span v-if="chargement" class="autocomplete-spinner" aria-label="Recherche en cours" />
      <span
        v-else-if="latitude != null"
        class="autocomplete-pin-ok"
        title="Adresse géolocalisée"
      >✓</span>
    </div>

    <ul v-if="ouvert && suggestions.length > 0" class="suggestions-liste" role="listbox">
      <li
        v-for="(s, i) in suggestions"
        :key="i"
        class="suggestion-item"
        role="option"
        @mousedown.prevent="selectionner(s)"
      >
        {{ s.label }}
      </li>
    </ul>
  </div>
</template>

<style scoped>
.autocomplete-lieu {
  position: relative;
}

.autocomplete-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.autocomplete-icone {
  position: absolute;
  left: 0.75rem;
  display: flex;
  color: var(--couleur-texte-discret);
  pointer-events: none;
  z-index: 1;
}

.autocomplete-input {
  padding-left: 2.2rem;
  padding-right: 2rem;
  width: 100%;
}

.autocomplete-spinner {
  position: absolute;
  right: 0.75rem;
  width: 14px;
  height: 14px;
  border: 2px solid var(--couleur-bordure);
  border-top-color: var(--couleur-primaire);
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
  flex-shrink: 0;
}

.autocomplete-pin-ok {
  position: absolute;
  right: 0.75rem;
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--couleur-primaire-fonce, #2e7d32);
  flex-shrink: 0;
}

.suggestions-liste {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  background: white;
  border: 1.5px solid #dde8dd;
  border-radius: 8px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
  list-style: none;
  z-index: 200;
  overflow: hidden;
}

.suggestion-item {
  padding: 0.6rem 0.85rem;
  font-size: 0.88rem;
  cursor: pointer;
  border-bottom: 1px solid #f0f4f0;
  transition: background 0.1s;
  line-height: 1.3;
}

.suggestion-item:last-child {
  border-bottom: none;
}

.suggestion-item:hover {
  background: #f0f9f0;
  color: var(--couleur-primaire-fonce, #2e7d32);
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
