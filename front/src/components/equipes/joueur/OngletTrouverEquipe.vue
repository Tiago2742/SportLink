<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import AvatarEquipe from '@/components/equipes/AvatarEquipe.vue'
import { useSports } from '@/composables/useSports'
import { chargerEquipes, demanderRejoindreEquipe } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { peutDemanderRejoindre, type AdhesionEquipe } from '@/utils/adhesionEquipe'
import IconeLigne from '@/components/ui/IconeLigne.vue'
import { nomAffichage } from '@/utils/nomAffichage'
import { MapPin } from 'lucide-vue-next'

const props = defineProps<{
  adhesions: AdhesionEquipe[]
}>()

const emit = defineEmits<{
  actualiser: []
}>()

const auth = useAuthStore()
const { listeSports, chargerCatalogue } = useSports()

const nom = ref('')
const sportId = ref<number | ''>('')
const localisation = ref('')
const resultats = ref<any[]>([])
const chargement = ref(false)
const erreur = ref('')
const demandeEnCours = ref<number | null>(null)

const sportsCollectifs = computed(() =>
  listeSports.value.filter((s) => s.type === 'collectif'),
)

onMounted(chargerCatalogue)

async function rechercher() {
  const n = nom.value.trim()
  const loc = localisation.value.trim()
  if (n.length < 2 && !sportId.value && loc.length < 2) {
    erreur.value = 'Saisissez au moins 2 caractères (nom ou localisation), ou choisissez un sport.'
    resultats.value = []
    return
  }

  chargement.value = true
  erreur.value = ''
  try {
    const filtres: Record<string, unknown> = {}
    if (n.length >= 2) filtres.nom = n
    if (sportId.value) filtres.sportId = sportId.value
    if (loc.length >= 2) filtres.localisation = loc
    resultats.value = await chargerEquipes(auth.token!, filtres)
  } catch {
    erreur.value = 'Recherche impossible.'
    resultats.value = []
  } finally {
    chargement.value = false
  }
}

function etatDemande(equipeId: number) {
  return peutDemanderRejoindre(equipeId, props.adhesions)
}

function libelleBloque(equipeId: number): string | undefined {
  return etatDemande(equipeId).raison
}

async function demander(equipeId: number) {
  demandeEnCours.value = equipeId
  erreur.value = ''
  try {
    await demanderRejoindreEquipe(auth.token!, equipeId)
    emit('actualiser')
    await rechercher()
  } catch (e: any) {
    erreur.value = e.message || 'Demande impossible.'
  } finally {
    demandeEnCours.value = null
  }
}
</script>

<template>
  <div class="onglet-trouver">
    <p class="intro">
      Recherchez une équipe par nom, sport ou localisation. Une seule équipe par sport (confirmée ou en attente).
    </p>

    <form class="carte formulaire-recherche" @submit.prevent="rechercher">
      <input
        v-model="nom"
        type="search"
        class="champ"
        placeholder="Nom de l'équipe (min. 2 car.)"
      />
      <select v-model="sportId" class="champ">
        <option value="">Tous les sports collectifs</option>
        <option v-for="s in sportsCollectifs" :key="s.id" :value="s.id">{{ s.nom }}</option>
      </select>
      <input
        v-model="localisation"
        type="search"
        class="champ"
        placeholder="Localisation (min. 2 car.)"
      />
      <button type="submit" class="btn btn-primaire" :disabled="chargement">
        {{ chargement ? 'Recherche...' : 'Rechercher' }}
      </button>
    </form>

    <div v-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>

    <div v-if="!chargement && resultats.length === 0 && !erreur" class="texte-discret">
      Lancez une recherche pour afficher des équipes.
    </div>

    <div v-else-if="resultats.length" class="grille-resultats">
      <article v-for="equipe in resultats" :key="equipe.id" class="carte carte-resultat">
        <div class="resultat-haut">
          <AvatarEquipe :equipe="equipe" taille="md" />
          <div>
            <h2>{{ equipe.nom }}</h2>
            <p class="resultat-meta">
              {{ equipe.sport?.nom }}
              <template v-if="equipe.niveau"> · {{ equipe.niveau.libelle }}</template>
            </p>
            <p class="resultat-club">{{ nomAffichage(equipe.club) }}</p>
            <p v-if="equipe.localisation" class="resultat-lieu">
              <IconeLigne :icone="MapPin" :taille="14" discret>{{ equipe.localisation }}</IconeLigne>
            </p>
          </div>
        </div>
        <div class="resultat-actions">
          <template v-if="etatDemande(equipe.id).autorise">
            <button
              class="btn btn-primaire btn-pleine-largeur"
              :disabled="demandeEnCours === equipe.id"
              @click="demander(equipe.id)"
            >
              Demander à rejoindre
            </button>
          </template>
          <p v-else class="resultat-bloque">{{ libelleBloque(equipe.id) }}</p>
          <RouterLink :to="`/equipes/${equipe.id}`" class="lien-fiche">Voir la fiche</RouterLink>
        </div>
      </article>
    </div>
  </div>
</template>

<style scoped>
.intro {
  font-size: 0.9rem;
  color: var(--couleur-texte-discret);
  margin-bottom: var(--espace-m);
}

.formulaire-recherche {
  display: flex;
  flex-wrap: wrap;
  gap: var(--espace-s);
  padding: var(--espace-m);
  margin-bottom: var(--espace-m);
  align-items: center;
}

.formulaire-recherche .champ {
  flex: 1 1 160px;
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--couleur-bordure);
  border-radius: var(--rayon-bouton);
}

.grille-resultats {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: var(--espace-m);
}

.carte-resultat {
  padding: var(--espace-l);
  display: flex;
  flex-direction: column;
  gap: var(--espace-m);
}

.resultat-haut {
  display: flex;
  gap: var(--espace-m);
}

.resultat-haut h2 {
  font-size: 1.05rem;
}

.resultat-meta,
.resultat-club,
.resultat-lieu {
  font-size: 0.88rem;
  color: var(--couleur-texte-discret);
}

.resultat-bloque {
  font-size: 0.85rem;
  color: var(--couleur-texte-discret);
  margin: 0;
}

.resultat-actions {
  display: flex;
  flex-direction: column;
  gap: var(--espace-s);
}

.lien-fiche {
  font-size: 0.85rem;
  text-align: center;
  color: var(--couleur-primaire);
}

.texte-discret {
  font-size: 0.88rem;
  color: var(--couleur-texte-discret);
  text-align: center;
  padding: var(--espace-l);
}
</style>
