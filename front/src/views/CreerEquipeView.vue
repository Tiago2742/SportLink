<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { creerEquipe } from '@/services/api'
import { useSports } from '@/composables/useSports'
import type { NiveauRef } from '@/services/api'

const auth = useAuthStore()
const router = useRouter()
const { listeSports, niveauxPour, chargerCatalogue } = useSports()

onMounted(chargerCatalogue)

const sportsCollectifs = computed(() =>
  listeSports.value.filter((s) => s.type === 'collectif'),
)

const form = ref({
  nom: '',
  sportId: '' as number | '',
  niveauId: '' as number | '',
  localisation: '',
  logo: '',
})

const niveauxDisponibles = computed<NiveauRef[]>(() =>
  form.value.sportId !== '' ? niveauxPour(form.value.sportId as number) : [],
)

const erreur = ref('')
const chargement = ref(false)

function onSportChange() {
  form.value.niveauId = ''
}

async function soumettre() {
  erreur.value = ''
  if (!form.value.nom.trim() || form.value.sportId === '') {
    erreur.value = 'Le nom et le sport sont obligatoires.'
    return
  }

  chargement.value = true
  try {
    const equipe = await creerEquipe(auth.token!, {
      nom: form.value.nom.trim(),
      sportId: form.value.sportId,
      niveauId: form.value.niveauId !== '' ? form.value.niveauId : undefined,
      localisation: form.value.localisation.trim() || undefined,
      logo: form.value.logo.trim() || undefined,
    })
    router.push(`/equipes/${equipe.id}`)
  } catch (e: any) {
    erreur.value =
      e.message ||
      (e.donnees as { erreur?: string })?.erreur ||
      'Impossible de créer l\'équipe.'
  } finally {
    chargement.value = false
  }
}
</script>

<template>
  <div class="page-creer-equipe conteneur">
    <RouterLink to="/mes-equipes" class="lien-retour">← Mes équipes</RouterLink>

    <h1>Créer une équipe</h1>
    <p class="sous-titre">
      Une équipe est rattachée à un sport collectif. Vous serez enregistré comme gestionnaire de l'équipe.
    </p>

    <form class="carte formulaire-equipe" @submit.prevent="soumettre">
      <div v-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>

      <div class="champ-groupe">
        <label for="nom">Nom de l'équipe <span class="obligatoire">*</span></label>
        <input
          id="nom"
          v-model="form.nom"
          type="text"
          class="champ"
          placeholder="Ex. AS Arras 1ère"
          required
        />
      </div>

      <div class="champ-groupe">
        <label for="sport">Sport <span class="obligatoire">*</span></label>
        <select
          id="sport"
          v-model="form.sportId"
          class="champ"
          required
          @change="onSportChange"
        >
          <option value="">Choisir un sport collectif</option>
          <option v-for="sport in sportsCollectifs" :key="sport.id" :value="sport.id">
            {{ sport.nom }}
          </option>
        </select>
      </div>

      <div class="champ-groupe">
        <label for="niveau">Niveau</label>
        <select
          id="niveau"
          v-model="form.niveauId"
          class="champ"
          :disabled="form.sportId === '' || niveauxDisponibles.length === 0"
        >
          <option value="">
            {{
              form.sportId === ''
                ? 'Choisissez d\'abord un sport'
                : niveauxDisponibles.length === 0
                  ? 'Aucun niveau'
                  : 'Sans niveau précis'
            }}
          </option>
          <option v-for="n in niveauxDisponibles" :key="n.id" :value="n.id">
            {{ n.libelle }}
          </option>
        </select>
      </div>

      <div class="champ-groupe">
        <label for="localisation">Localisation</label>
        <input
          id="localisation"
          v-model="form.localisation"
          type="text"
          class="champ"
          placeholder="Ville, région..."
        />
      </div>

      <div class="champ-groupe">
        <label for="logo">Logo <span class="label-optionnel">(URL, optionnel)</span></label>
        <input
          id="logo"
          v-model="form.logo"
          type="url"
          class="champ"
          placeholder="https://..."
        />
      </div>

      <button type="submit" class="btn btn-primaire" :disabled="chargement">
        {{ chargement ? 'Création...' : 'Créer l\'équipe' }}
      </button>
    </form>
  </div>
</template>

<style scoped>
.page-creer-equipe {
  padding-top: var(--espace-xl);
  padding-bottom: var(--espace-xxl);
  max-width: 560px;
}

.lien-retour {
  display: inline-block;
  margin-bottom: var(--espace-m);
  font-size: 0.9rem;
  color: var(--couleur-texte-discret);
  text-decoration: none;
}

.lien-retour:hover {
  color: var(--couleur-primaire);
}

.page-creer-equipe h1 {
  font-size: 1.5rem;
  font-weight: 700;
}

.sous-titre {
  color: var(--couleur-texte-discret);
  font-size: 0.9rem;
  margin: 0.5rem 0 var(--espace-l);
  line-height: 1.45;
}

.formulaire-equipe {
  padding: var(--espace-l);
  display: flex;
  flex-direction: column;
  gap: var(--espace-m);
}

.label-optionnel {
  font-weight: 400;
  color: var(--couleur-texte-discret);
  font-size: 0.8rem;
}

.obligatoire {
  color: var(--couleur-erreur, #c62828);
}
</style>
