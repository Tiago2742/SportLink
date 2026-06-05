<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { chargerMesMatchs } from '@/services/api'
import BadgeStatut from '@/components/commun/BadgeStatut.vue'
import IconeLigne from '@/components/ui/IconeLigne.vue'
import { MapPin, Trophy } from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()

const matchs = ref<any[]>([])
const chargement = ref(true)
const erreur = ref('')
const filtreStatut = ref('')

onMounted(charger)

async function charger() {
  chargement.value = true
  erreur.value = ''
  const userId = auth.utilisateur?.id
  try {
    matchs.value = userId ? await chargerMesMatchs(auth.token!) : []
  } catch {
    erreur.value = 'Impossible de charger vos matchs.'
  } finally {
    chargement.value = false
  }
}

function estPasse(dateMatch: string) {
  return new Date(dateMatch) < new Date()
}

function estTermine(match: { statut: string; dateMatch: string }) {
  return match.statut === 'termine' || estPasse(match.dateMatch)
}

const mesMatchs = computed(() => matchs.value)

const matchsFiltres = computed(() => {
  let liste = mesMatchs.value

  if (filtreStatut.value === 'passe') {
    return liste.filter((m) => estPasse(m.dateMatch))
  }
  if (filtreStatut.value === 'avenir') {
    return liste.filter((m) => !estPasse(m.dateMatch))
  }
  if (filtreStatut.value === 'termine') {
    return liste.filter((m) => estTermine(m))
  }
  if (filtreStatut.value) {
    return liste.filter((m) => m.statut === filtreStatut.value)
  }

  return liste
})

const matchsAvenir = computed(() =>
  matchsFiltres.value.filter((m) => !estPasse(m.dateMatch)),
)

const matchsPasses = computed(() =>
  matchsFiltres.value.filter((m) => estPasse(m.dateMatch)),
)

const afficherSections = computed(() => filtreStatut.value === '')

const listeUnique = computed(() => (afficherSections.value ? [] : matchsFiltres.value))

function formaterDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<template>
  <div class="page-mes-matchs conteneur">
    <div class="page-entete">
      <div>
        <h1>Mes matchs</h1>
        <p class="sous-titre">Matchs que vous organisez ou auxquels vous participez</p>
      </div>
      <RouterLink to="/creer-match" class="btn btn-primaire">+ Créer un match</RouterLink>
    </div>

    <div class="filtres-statut">
      <button class="chip" :class="{ actif: filtreStatut === '' }" @click="filtreStatut = ''">
        Tous ({{ mesMatchs.length }})
      </button>
      <button class="chip" :class="{ actif: filtreStatut === 'avenir' }" @click="filtreStatut = 'avenir'">
        À venir
      </button>
      <button class="chip" :class="{ actif: filtreStatut === 'passe' }" @click="filtreStatut = 'passe'">
        Passés
      </button>
      <button
        class="chip"
        :class="{ actif: filtreStatut === 'en_attente' }"
        @click="filtreStatut = 'en_attente'"
      >
        En attente
      </button>
      <button
        class="chip"
        :class="{ actif: filtreStatut === 'confirme' }"
        @click="filtreStatut = 'confirme'"
      >
        Confirmés
      </button>
      <button
        class="chip"
        :class="{ actif: filtreStatut === 'termine' }"
        @click="filtreStatut = 'termine'"
      >
        Terminés
      </button>
    </div>

    <div v-if="chargement" class="chargement">Chargement...</div>
    <div v-else-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>

    <template v-else>
      <template v-if="afficherSections">
        <section v-if="matchsAvenir.length > 0">
          <h2 class="section-titre">À venir ({{ matchsAvenir.length }})</h2>
          <div class="carte liste-matchs">
            <div
              v-for="match in matchsAvenir"
              :key="match.id"
              class="ligne-match"
              @click="router.push(`/matchs/${match.id}`)"
            >
              <div class="ligne-gauche">
                <div class="ligne-sport-icone" aria-hidden="true">
                  <Trophy :size="20" stroke-width="2" />
                </div>
                <div class="ligne-info">
                  <strong>{{ match.sport?.nom ?? 'Sport' }}</strong>
                  <span>{{ formaterDate(match.dateMatch) }}</span>
                  <IconeLigne
                    v-if="match.lieu"
                    :icone="MapPin"
                    :taille="14"
                    discret
                    class="ligne-lieu"
                  >
                    {{ match.lieu }}
                  </IconeLigne>
                </div>
              </div>
              <div class="ligne-droite">
                <BadgeStatut :statut="match.statut" />
                <span class="ligne-fleche">›</span>
              </div>
            </div>
          </div>
        </section>

        <section v-if="matchsPasses.length > 0" class="section-passe">
          <h2 class="section-titre">Passés ({{ matchsPasses.length }})</h2>
          <div class="carte liste-matchs liste-passee">
            <div
              v-for="match in matchsPasses"
              :key="match.id"
              class="ligne-match"
              @click="router.push(`/matchs/${match.id}`)"
            >
              <div class="ligne-gauche">
                <div class="ligne-sport-icone passé" aria-hidden="true">
                  <Trophy :size="20" stroke-width="2" />
                </div>
                <div class="ligne-info">
                  <strong>{{ match.sport?.nom ?? 'Sport' }}</strong>
                  <span>{{ formaterDate(match.dateMatch) }}</span>
                  <IconeLigne
                    v-if="match.lieu"
                    :icone="MapPin"
                    :taille="14"
                    discret
                    class="ligne-lieu"
                  >
                    {{ match.lieu }}
                  </IconeLigne>
                </div>
              </div>
              <div class="ligne-droite">
                <BadgeStatut :statut="match.statut" />
                <span class="ligne-fleche">›</span>
              </div>
            </div>
          </div>
        </section>
      </template>

      <section v-else-if="listeUnique.length > 0">
        <h2 class="section-titre">Résultats ({{ listeUnique.length }})</h2>
        <div class="carte liste-matchs" :class="{ 'liste-passee': filtreStatut === 'passe' || filtreStatut === 'termine' }">
          <div
            v-for="match in listeUnique"
            :key="match.id"
            class="ligne-match"
            @click="router.push(`/matchs/${match.id}`)"
          >
            <div class="ligne-gauche">
              <div class="ligne-sport-icone" :class="{ passé: estPasse(match.dateMatch) }" aria-hidden="true">
                <Trophy :size="20" stroke-width="2" />
              </div>
              <div class="ligne-info">
                <strong>{{ match.sport?.nom ?? 'Sport' }}</strong>
                <span>{{ formaterDate(match.dateMatch) }}</span>
                <IconeLigne
                  v-if="match.lieu"
                  :icone="MapPin"
                  :taille="14"
                  discret
                  class="ligne-lieu"
                >
                  {{ match.lieu }}
                </IconeLigne>
              </div>
            </div>
            <div class="ligne-droite">
              <BadgeStatut :statut="match.statut" />
              <span class="ligne-fleche">›</span>
            </div>
          </div>
        </div>
      </section>

      <div v-if="mesMatchs.length === 0" class="vide">
        <p>Vous ne participez à aucun match pour le moment.</p>
        <RouterLink to="/rechercher" class="btn btn-secondaire" style="margin-top: var(--espace-s)">
          Trouver un match
        </RouterLink>
      </div>

      <div v-else-if="matchsFiltres.length === 0" class="vide">
        <p>Aucun match pour ce filtre.</p>
        <button type="button" class="btn btn-secondaire" style="margin-top: var(--espace-s)" @click="filtreStatut = ''">
          Voir tous mes matchs
        </button>
      </div>
    </template>
  </div>
</template>

<style scoped>
.page-mes-matchs {
  padding-top: var(--espace-xl);
  padding-bottom: var(--espace-xxl);
}

.page-entete {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--espace-xl);
}

.page-entete h1 {
  font-size: 1.6rem;
  font-weight: 700;
}

.sous-titre {
  color: var(--couleur-texte-discret);
  font-size: 0.9rem;
}

.filtres-statut {
  display: flex;
  gap: var(--espace-s);
  margin-bottom: var(--espace-l);
  flex-wrap: wrap;
}

.chip {
  padding: 0.35rem 1rem;
  border: 1.5px solid var(--couleur-bordure);
  border-radius: var(--rayon-badge);
  background: white;
  cursor: pointer;
  font-size: 0.85rem;
  transition: all 0.2s;
}

.chip.actif {
  border-color: var(--couleur-primaire);
  color: var(--couleur-primaire);
  background: var(--couleur-primaire-tres-claire);
}

.section-titre {
  font-weight: 700;
  margin-bottom: var(--espace-s);
  color: var(--couleur-texte-discret);
  text-transform: uppercase;
  font-size: 0.82rem;
  letter-spacing: 0.5px;
}

.section-passe {
  margin-top: var(--espace-xl);
}

.liste-matchs {
  overflow: hidden;
}

.liste-passee {
  opacity: 0.85;
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

.ligne-gauche {
  display: flex;
  align-items: center;
  gap: var(--espace-m);
}

.ligne-sport-icone {
  width: 40px;
  height: 40px;
  background: var(--couleur-primaire-tres-claire);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: var(--couleur-primaire);
}

.ligne-sport-icone.passé {
  background: #f5f5f5;
  color: var(--couleur-texte-discret);
}

.ligne-info {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.ligne-info strong {
  font-size: 0.95rem;
}

.ligne-info span {
  font-size: 0.82rem;
  color: var(--couleur-texte-discret);
}

.ligne-lieu {
  font-size: 0.8rem;
}

.ligne-droite {
  display: flex;
  align-items: center;
  gap: var(--espace-m);
}

.ligne-fleche {
  color: var(--couleur-texte-discret);
  font-size: 1.2rem;
}

.vide {
  text-align: center;
  padding: var(--espace-xxl);
  color: var(--couleur-texte-discret);
  background: white;
  border-radius: var(--rayon-carte);
  border: 1px dashed var(--couleur-bordure);
  display: flex;
  flex-direction: column;
  align-items: center;
}
</style>
