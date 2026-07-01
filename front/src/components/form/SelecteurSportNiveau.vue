<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useSports } from '@/composables/useSports'

export interface EntreeSportNiveau {
  sportId: number
  niveauId: number
}

const props = defineProps<{
  modelValue: EntreeSportNiveau[]
  multiple?: boolean
  filtreType?: string      // 'collectif' | 'individuel' — filtre la liste des sports
  masquerRetrait?: boolean // cache le bouton × sur les badges
}>()

const emit = defineEmits<{
  'update:modelValue': [valeur: EntreeSportNiveau[]]
}>()

const { listeSports, niveauxPour, nomSport, nomNiveau, chargerCatalogue } = useSports()

const sportEnCours  = ref<number | ''>('')
const niveauEnCours = ref<number | ''>('')

onMounted(chargerCatalogue)

const sportsFiltres = computed(() =>
  props.filtreType
    ? listeSports.value.filter((s) => s.type === props.filtreType)
    : listeSports.value,
)

const niveauxDisponibles = computed(() =>
  sportEnCours.value !== '' ? niveauxPour(sportEnCours.value as number) : [],
)

function onSportChange() {
  niveauEnCours.value = ''
}

function ajouter() {
  if (sportEnCours.value === '' || niveauEnCours.value === '') return

  const sportId  = sportEnCours.value as number
  const niveauId = niveauEnCours.value as number

  const existant = props.modelValue.findIndex((e) => e.sportId === sportId)
  const copie    = [...props.modelValue]

  if (existant >= 0) {
    copie[existant] = { sportId, niveauId }
  } else {
    copie.push({ sportId, niveauId })
  }

  emit('update:modelValue', copie)
  sportEnCours.value  = ''
  niveauEnCours.value = ''
}

function retirer(index: number) {
  const copie = [...props.modelValue]
  copie.splice(index, 1)
  emit('update:modelValue', copie)
}
</script>

<template>
  <div class="selecteur-sport-niveau">
    <!-- Sports déjà ajoutés -->
    <div v-if="modelValue.length > 0" class="sports-ajoutes">
      <div v-for="(entree, i) in modelValue" :key="i" class="sport-badge">
        <span class="sport-nom">{{ nomSport(entree.sportId) }}</span>
        <span class="niveau-nom">{{ nomNiveau(entree.niveauId) }}</span>
        <button v-if="!masquerRetrait" type="button" class="btn-retirer" @click="retirer(i)" title="Retirer">×</button>
      </div>
    </div>
    <p v-else class="aucun-sport">Aucun sport ajouté.</p>

    <!-- Ajout d'un sport -->
    <div class="ajout-sport">
      <select v-model="sportEnCours" class="champ" @change="onSportChange">
        <option value="">Choisir un sport</option>
        <option v-for="sport in sportsFiltres" :key="sport.id" :value="sport.id">
          {{ sport.nom }}
        </option>
      </select>

      <select v-model="niveauEnCours" class="champ" :disabled="sportEnCours === ''">
        <option value="">
          {{ sportEnCours !== '' ? 'Choisir un niveau' : '— sélectionner un sport d\'abord —' }}
        </option>
        <option v-for="niveau in niveauxDisponibles" :key="niveau.id" :value="niveau.id">
          {{ niveau.libelle }}
        </option>
      </select>

      <button
        type="button"
        class="btn btn-primaire"
        :disabled="sportEnCours === '' || niveauEnCours === ''"
        @click="ajouter"
      >
        + Ajouter
      </button>
    </div>
  </div>
</template>

<style scoped>
.selecteur-sport-niveau {
  display: flex;
  flex-direction: column;
  gap: var(--espace-s);
}

.sports-ajoutes {
  display: flex;
  flex-wrap: wrap;
  gap: var(--espace-xs);
}

.sport-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  background: var(--couleur-primaire-tres-claire);
  border: 1px solid var(--couleur-primaire-claire);
  border-radius: var(--rayon-badge);
  padding: 0.25rem 0.6rem;
  font-size: 0.82rem;
}

.sport-nom {
  font-weight: 600;
  color: var(--couleur-primaire);
}

.niveau-nom {
  color: var(--couleur-texte-discret);
}

.niveau-nom::before {
  content: '·';
  margin-right: 0.3rem;
}

.btn-retirer {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--couleur-texte-discret);
  font-size: 1rem;
  line-height: 1;
  padding: 0 0 0 0.2rem;
  transition: color 0.15s;
}

.btn-retirer:hover {
  color: #e53935;
}

.aucun-sport {
  font-size: 0.85rem;
  color: var(--couleur-texte-discret);
}

.ajout-sport {
  display: flex;
  gap: var(--espace-s);
  align-items: center;
  flex-wrap: wrap;
}

.ajout-sport .champ {
  flex: 1;
  min-width: 140px;
}

.ajout-sport .btn {
  white-space: nowrap;
  flex-shrink: 0;
}

button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
