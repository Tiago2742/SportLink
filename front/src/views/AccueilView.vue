<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { chargerMatchs, chargerMesMatchs, ajouterCamp } from '@/services/api'
import { utilisateurEstInscrit } from '@/composables/useMatchCamps'
import { useSports } from '@/composables/useSports'
import CarteMatch from '@/components/matchs/CarteMatch.vue'
import BadgeStatut from '@/components/commun/BadgeStatut.vue'

const auth = useAuthStore()
const router = useRouter()
const { listeSports, niveauxPour, chargerCatalogue } = useSports()

const matchsDisponibles = ref<any[]>([])
const mesMatchs = ref<any[]>([])
const chargementMatchs = ref(true)
const erreur = ref('')

const recherche          = ref('')
const filtreSport        = ref<number | ''>('')
const filtreNiveau       = ref<number | ''>('')
const filtreLocalisation = ref('')

onMounted(() => {
  chargerCatalogue()
  charger()
})

async function charger() {
  chargementMatchs.value = true
  const userId = auth.utilisateur?.id
  try {
    const [disponibles, miens] = await Promise.all([
      chargerMatchs(auth.token!, { statut: 'disponible' }),
      userId ? chargerMesMatchs(auth.token!) : Promise.resolve([]),
    ])
    matchsDisponibles.value = disponibles.slice(0, 3)
    mesMatchs.value = miens
  } catch {
    erreur.value = 'Impossible de charger les matchs.'
  } finally {
    chargementMatchs.value = false
  }
}

async function rechercherMatchs() {
  router.push({
    path: '/rechercher',
    query: {
      q:            recherche.value || undefined,
      sportId:      filtreSport.value !== '' ? filtreSport.value : undefined,
      niveauId:     filtreNiveau.value !== '' ? filtreNiveau.value : undefined,
      localisation: filtreLocalisation.value || undefined,
    },
  })
}

async function rejoindreMatch(matchId: number) {
  if (!auth.utilisateur) return
  const m = matchsDisponibles.value.find((x) => x.id === matchId)
  if (m && utilisateurEstInscrit(m, auth.utilisateur.id)) return
  try {
    if (m?.sport?.type === 'individuel') {
      await ajouterCamp(auth.token!, matchId, { joueurId: auth.utilisateur.id })
      await charger()
    } else {
      router.push(`/matchs/${matchId}`)
    }
  } catch (e: any) {
    alert(e.message || 'Impossible de rejoindre ce match.')
  }
}
</script>

<template>
  <div class="page-accueil">
    <!-- Barre de recherche rapide -->
    <section class="section-recherche">
      <div class="conteneur">
        <div class="carte recherche-rapide">
          <input
            v-model="recherche"
            type="text"
            class="champ recherche-input"
            placeholder="Rechercher un match..."
            @keyup.enter="rechercherMatchs"
          />
          <div class="recherche-filtres">
            <select v-model="filtreSport" class="champ" @change="filtreNiveau = ''">
              <option value="">Sport</option>
              <option v-for="sport in listeSports" :key="sport.id" :value="sport.id">{{ sport.nom }}</option>
            </select>
            <select v-model="filtreNiveau" class="champ" :disabled="filtreSport === ''">
              <option value="">Niveau</option>
              <option v-for="n in niveauxPour(filtreSport as number)" :key="n.id" :value="n.id">{{ n.libelle }}</option>
            </select>
            <input
              v-model="filtreLocalisation"
              type="text"
              class="champ"
              placeholder="Localisation"
            />
            <button class="btn btn-primaire" @click="rechercherMatchs">Chercher</button>
          </div>
        </div>
      </div>
    </section>

    <!-- Matchs disponibles -->
    <section class="section-contenu conteneur">
      <h2 class="section-titre">Matchs disponibles</h2>

      <div v-if="chargementMatchs" class="chargement">Chargement des matchs...</div>
      <div v-else-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>
      <div v-else-if="matchsDisponibles.length === 0" class="vide">
        Aucun match disponible pour le moment.
      </div>
      <div v-else class="grille-3">
        <CarteMatch
          v-for="match in matchsDisponibles"
          :key="match.id"
          :match="match"
          :afficher-bouton-rejoindre="true"
          @rejoindre="rejoindreMatch"
        />
      </div>

      <div class="voir-plus" v-if="matchsDisponibles.length > 0">
        <RouterLink to="/rechercher" class="btn btn-secondaire">
          Voir tous les matchs
        </RouterLink>
      </div>
    </section>

    <!-- Mes prochains matchs -->
    <section class="section-contenu conteneur" v-if="auth.estConnecte">
      <h2 class="section-titre">Mes prochains matchs</h2>

      <div v-if="mesMatchs.length === 0" class="vide">
        Vous n'avez pas encore de matchs créés.
        <RouterLink to="/creer-match">Créer un match</RouterLink>
      </div>

      <div v-else class="carte liste-matchs">
        <div
          v-for="match in mesMatchs"
          :key="match.id"
          class="ligne-match"
          @click="router.push(`/matchs/${match.id}`)"
        >
          <div class="ligne-match-info">
            <strong class="ligne-sport">{{ match.sport?.nom ?? 'Sport' }}</strong>
            <span class="ligne-date">
              {{
                new Date(match.dateMatch).toLocaleDateString('fr-FR', {
                  weekday: 'long',
                  day: 'numeric',
                  month: 'long',
                })
              }}
            </span>
            <span class="ligne-lieu" v-if="match.lieu">{{ match.lieu }}</span>
          </div>
          <BadgeStatut :statut="match.statut" />
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.page-accueil {
  padding-bottom: var(--espace-xxl);
}

.section-recherche {
  background: white;
  padding: var(--espace-xl) 0;
  border-bottom: 1px solid var(--couleur-bordure);
}

.recherche-rapide {
  padding: var(--espace-l);
}

.recherche-input {
  font-size: 1rem;
  padding: 0.75rem 1rem;
  margin-bottom: var(--espace-m);
}

.recherche-filtres {
  display: flex;
  gap: var(--espace-m);
  flex-wrap: wrap;
  align-items: center;
}

.recherche-filtres .champ {
  flex: 1;
  min-width: 120px;
}

.recherche-filtres .btn {
  white-space: nowrap;
}

.section-contenu {
  margin-top: var(--espace-xxl);
}

.section-titre {
  font-size: 1.4rem;
  font-weight: 700;
  margin-bottom: var(--espace-l);
  color: var(--couleur-texte);
}

.vide {
  text-align: center;
  padding: var(--espace-xl);
  color: var(--couleur-texte-discret);
  background: white;
  border-radius: var(--rayon-carte);
  border: 1px dashed var(--couleur-bordure);
}

.vide a {
  color: var(--couleur-primaire);
  text-decoration: none;
  font-weight: 500;
  margin-left: 0.4rem;
}

.voir-plus {
  text-align: center;
  margin-top: var(--espace-xl);
}

.liste-matchs {
  overflow: hidden;
}

.ligne-match {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--espace-m) var(--espace-l);
  border-bottom: 1px solid var(--couleur-bordure);
  cursor: pointer;
  transition: background 0.15s;
}

.ligne-match:last-child {
  border-bottom: none;
}

.ligne-match:hover {
  background: var(--couleur-fond);
}

.ligne-match-info {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.ligne-sport {
  font-size: 0.95rem;
  color: var(--couleur-texte);
}

.ligne-date,
.ligne-lieu {
  font-size: 0.82rem;
  color: var(--couleur-texte-discret);
}

@media (max-width: 768px) {
  .recherche-filtres {
    flex-direction: column;
  }

  .recherche-filtres .champ,
  .recherche-filtres .btn {
    width: 100%;
  }
}
</style>
