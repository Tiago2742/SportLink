<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { creerMatch } from '@/services/api'

const auth = useAuthStore()
const router = useRouter()

const form = ref({
  sport: '',
  dateMatch: '',
  lieu: '',
  nbreJoueurs: '',
  niveauRequis: '',
  description: '',
})

const erreur = ref('')
const succes = ref(false)
const chargement = ref(false)

async function soumettre() {
  erreur.value = ''
  if (!form.value.sport || !form.value.dateMatch || !form.value.lieu || !form.value.niveauRequis) {
    erreur.value = 'Veuillez remplir tous les champs obligatoires.'
    return
  }
  chargement.value = true
  try {
    const match = await creerMatch(auth.token!, {
      sport: form.value.sport,
      dateMatch: form.value.dateMatch,
      lieu: form.value.lieu,
      niveauRequis: form.value.niveauRequis,
    })
    router.push(`/matchs/${match.id}`)
  } catch (e: any) {
    if (e.statut === 400) {
      erreur.value = 'Données invalides. Vérifiez les champs.'
    } else {
      erreur.value = 'Impossible de créer le match. Réessayez.'
    }
  } finally {
    chargement.value = false
  }
}

function annuler() {
  router.push('/')
}
</script>

<template>
  <div class="page-creer conteneur">
    <div class="creer-layout">
      <!-- Sidebar info -->
      <aside class="sidebar-info carte">
        <div class="sidebar-icone">
          <span>+</span>
        </div>
        <h2 class="sidebar-titre">Créer un match</h2>
        <p class="sidebar-sous-titre">Organisez votre partie</p>

        <p class="sidebar-desc">
          Remplissez les informations pour créer un match. Tous les champs marqués * sont
          obligatoires.
        </p>

        <ul class="sidebar-infos">
          <li>
            <span class="info-icone">✅</span>
            Le match sera visible par tous les utilisateurs
          </li>
          <li>
            <span class="info-icone">👥</span>
            Vous serez automatiquement participant
          </li>
          <li>
            <span class="info-icone">⚡</span>
            Création instantanée après validation
          </li>
        </ul>
      </aside>

      <!-- Formulaire -->
      <div class="formulaire-section">
        <h1 class="formulaire-titre">Créer un nouveau match</h1>
        <p class="formulaire-sous-titre">
          Complétez le formulaire ci-dessous pour organiser votre match sportif
        </p>

        <div class="carte formulaire-carte">
          <div v-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>

          <form @submit.prevent="soumettre">
            <div class="grille-2">
              <div class="champ-groupe">
                <label for="sport">Sport <span class="obligatoire">*</span></label>
                <select id="sport" v-model="form.sport" class="champ" required>
                  <option value="">Choisir un sport</option>
                  <option value="football">Football</option>
                  <option value="basketball">Basketball</option>
                  <option value="tennis">Tennis</option>
                  <option value="volleyball">Volleyball</option>
                  <option value="rugby">Rugby</option>
                  <option value="handball">Handball</option>
                  <option value="badminton">Badminton</option>
                </select>
              </div>

              <div class="champ-groupe">
                <label for="dateMatch">Date et Heure <span class="obligatoire">*</span></label>
                <input
                  id="dateMatch"
                  v-model="form.dateMatch"
                  type="datetime-local"
                  class="champ"
                  required
                />
              </div>
            </div>

            <div class="champ-groupe">
              <label for="lieu">Lieu <span class="obligatoire">*</span></label>
              <input
                id="lieu"
                v-model="form.lieu"
                type="text"
                class="champ"
                placeholder="Adresse ou terrain"
                required
              />
            </div>

            <div class="grille-2">
              <div class="champ-groupe">
                <label for="nbreJoueurs">Nombre de joueurs requis</label>
                <input
                  id="nbreJoueurs"
                  v-model="form.nbreJoueurs"
                  type="number"
                  min="2"
                  max="100"
                  class="champ"
                  placeholder="Nombre de joueurs"
                />
              </div>

              <div class="champ-groupe">
                <label for="niveauRequis">Niveau requis <span class="obligatoire">*</span></label>
                <select id="niveauRequis" v-model="form.niveauRequis" class="champ" required>
                  <option value="">Débutant / Intermédiaire / Avancé</option>
                  <option value="débutant">Débutant</option>
                  <option value="intermédiaire">Intermédiaire</option>
                  <option value="avancé">Avancé</option>
                  <option value="tous niveaux">Tous niveaux</option>
                </select>
              </div>
            </div>

            <div class="champ-groupe">
              <label for="description">Description (optionnel)</label>
              <textarea
                id="description"
                v-model="form.description"
                class="champ textarea"
                placeholder="Ajoutez des détails sur votre match..."
                rows="4"
              ></textarea>
            </div>

            <div class="formulaire-actions">
              <button
                type="submit"
                class="btn btn-primaire"
                :disabled="chargement"
              >
                + {{ chargement ? 'Création...' : 'Créer match' }}
              </button>
              <button type="button" class="btn btn-secondaire" @click="annuler">
                × Annuler
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page-creer {
  padding-top: var(--espace-xl);
  padding-bottom: var(--espace-xxl);
}

.creer-layout {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: var(--espace-xl);
  align-items: start;
}

.sidebar-info {
  padding: var(--espace-l);
  position: sticky;
  top: 80px;
}

.sidebar-icone {
  width: 52px;
  height: 52px;
  background: var(--couleur-primaire);
  border-radius: var(--rayon-carte);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.6rem;
  font-weight: 700;
  margin-bottom: var(--espace-m);
}

.sidebar-titre {
  font-size: 1.1rem;
  font-weight: 700;
  margin-bottom: 0.2rem;
}

.sidebar-sous-titre {
  color: var(--couleur-texte-discret);
  font-size: 0.85rem;
  margin-bottom: var(--espace-m);
}

.sidebar-desc {
  font-size: 0.85rem;
  color: var(--couleur-texte-discret);
  margin-bottom: var(--espace-l);
  line-height: 1.5;
  padding-bottom: var(--espace-m);
  border-bottom: 1px solid var(--couleur-bordure);
}

.sidebar-infos {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: var(--espace-m);
}

.sidebar-infos li {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: var(--couleur-texte);
}

.info-icone {
  font-size: 1rem;
  flex-shrink: 0;
}

.formulaire-titre {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 0.3rem;
}

.formulaire-sous-titre {
  color: var(--couleur-texte-discret);
  font-size: 0.9rem;
  margin-bottom: var(--espace-l);
}

.formulaire-carte {
  padding: var(--espace-xl);
}

.textarea {
  resize: vertical;
  min-height: 100px;
}

.formulaire-actions {
  display: flex;
  gap: var(--espace-m);
  margin-top: var(--espace-m);
}

.formulaire-actions .btn {
  flex: 1;
  padding: 0.85rem;
  font-size: 1rem;
}

button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

@media (max-width: 900px) {
  .creer-layout {
    grid-template-columns: 1fr;
  }

  .sidebar-info {
    position: static;
  }
}
</style>
