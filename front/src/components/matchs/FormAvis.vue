<script setup lang="ts">
import { reactive, computed } from 'vue'

const emit = defineEmits<{
  soumis: [{ ponctualite: number; fairPlay: number; niveauConforme: number }]
  annule: []
}>()

const criteres = [
  { cle: 'ponctualite',    libelle: 'Ponctualité' },
  { cle: 'fairPlay',       libelle: 'Fair-play' },
  { cle: 'niveauConforme', libelle: 'Niveau conforme' },
] as const

type Cle = (typeof criteres)[number]['cle']

const notes   = reactive<Record<Cle, number>>({ ponctualite: 0, fairPlay: 0, niveauConforme: 0 })
const survole = reactive<Record<Cle, number>>({ ponctualite: 0, fairPlay: 0, niveauConforme: 0 })

const pret = computed(() => criteres.every((c) => notes[c.cle] > 0))

function valider() {
  if (!pret.value) return
  emit('soumis', { ponctualite: notes.ponctualite, fairPlay: notes.fairPlay, niveauConforme: notes.niveauConforme })
}
</script>

<template>
  <div class="form-avis">
    <div class="form-avis-criteres">
      <div v-for="critere in criteres" :key="critere.cle" class="critere-ligne">
        <span class="critere-libelle">{{ critere.libelle }}</span>
        <div class="etoiles-selecteur" :aria-label="critere.libelle">
          <button
            v-for="i in 5"
            :key="i"
            type="button"
            class="etoile-btn"
            :class="{ 'etoile-btn--active': i <= (survole[critere.cle] || notes[critere.cle]) }"
            :aria-label="`${i} étoile${i > 1 ? 's' : ''}`"
            :aria-pressed="notes[critere.cle] === i"
            @mouseenter="survole[critere.cle] = i"
            @mouseleave="survole[critere.cle] = 0"
            @click="notes[critere.cle] = i"
          >★</button>
        </div>
        <span class="note-chiffre">
          {{ notes[critere.cle] > 0 ? notes[critere.cle] + '/5' : '—' }}
        </span>
      </div>
    </div>

    <p v-if="!pret" class="form-avis-hint">Notez les 3 critères pour valider.</p>

    <div class="form-avis-actions">
      <button class="btn btn-primaire" :disabled="!pret" @click="valider">
        Envoyer mon évaluation
      </button>
      <button class="btn btn-secondaire" type="button" @click="emit('annule')">
        Annuler
      </button>
    </div>
  </div>
</template>

<style scoped>
.form-avis {
  display: flex;
  flex-direction: column;
  gap: var(--espace-m);
}

.form-avis-criteres {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.critere-ligne {
  display: flex;
  align-items: center;
  gap: var(--espace-m);
  padding: 0.65rem 0;
  border-bottom: 1px solid var(--couleur-bordure);
}

.critere-ligne:last-child {
  border-bottom: none;
}

.critere-libelle {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--couleur-titre);
  min-width: 130px;
}

.etoiles-selecteur {
  display: flex;
  gap: 3px;
  flex: 1;
}

.etoile-btn {
  background: none;
  border: none;
  font-size: 1.4rem;
  line-height: 1;
  cursor: pointer;
  color: #d0d0d0;
  padding: 0.1rem;
  transition: color 0.1s, transform 0.1s;
}

.etoile-btn:hover,
.etoile-btn--active {
  color: #f5a623;
}

.etoile-btn:hover {
  transform: scale(1.15);
}

.note-chiffre {
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--couleur-texte-discret);
  min-width: 2.4rem;
  text-align: right;
}

.form-avis-hint {
  font-size: 0.8rem;
  color: var(--couleur-texte-discret);
  font-style: italic;
  text-align: center;
}

.form-avis-actions {
  display: flex;
  gap: var(--espace-s);
}

button:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}
</style>
