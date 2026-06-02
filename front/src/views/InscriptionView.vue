<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import SelecteurSportNiveau from '@/components/form/SelecteurSportNiveau.vue'
import type { EntreeSportNiveau } from '@/components/form/SelecteurSportNiveau.vue'

const auth = useAuthStore()
const router = useRouter()

const form = ref({
  email: '',
  password: '',
  nom: '',
  prenom: '',
  type: 'joueur',
  localisation: '',
})

const sports = ref<EntreeSportNiveau[]>([])
const erreur = ref('')
const chargement = ref(false)

async function soumettre() {
  erreur.value = ''
  chargement.value = true
  try {
    await auth.sInscrire({ ...form.value, sports: sports.value })
    router.push('/')
  } catch (e: any) {
    if (e.statut === 409) {
      erreur.value = 'Cette adresse email est déjà utilisée.'
    } else if (e.statut === 400) {
      erreur.value = 'Veuillez remplir tous les champs obligatoires.'
    } else {
      erreur.value = "Une erreur est survenue lors de l'inscription."
    }
  } finally {
    chargement.value = false
  }
}
</script>

<template>
  <div class="page-auth">
    <div class="boite-auth carte">
      <div class="auth-entete">
        <span class="logo-emoji">⚽</span>
        <h1>Créer un compte</h1>
        <p>Rejoignez la communauté SportLink</p>
      </div>

      <form @submit.prevent="soumettre">
        <div v-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>

        <div class="grille-2">
          <div class="champ-groupe">
            <label for="prenom">Prénom <span class="obligatoire">*</span></label>
            <input
              id="prenom"
              v-model="form.prenom"
              type="text"
              class="champ"
              placeholder="Paul"
              required
            />
          </div>
          <div class="champ-groupe">
            <label for="nom">Nom <span class="obligatoire">*</span></label>
            <input
              id="nom"
              v-model="form.nom"
              type="text"
              class="champ"
              placeholder="Martin"
              required
            />
          </div>
        </div>

        <div class="champ-groupe">
          <label for="email">Email <span class="obligatoire">*</span></label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            class="champ"
            placeholder="vous@exemple.fr"
            required
            autocomplete="email"
          />
        </div>

        <div class="champ-groupe">
          <label for="password">Mot de passe <span class="obligatoire">*</span></label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            class="champ"
            placeholder="Minimum 8 caractères"
            required
            autocomplete="new-password"
          />
        </div>

        <div class="champ-groupe">
          <label for="type">Type de profil <span class="obligatoire">*</span></label>
          <select id="type" v-model="form.type" class="champ" required>
            <option value="joueur">Joueur</option>
            <option value="entraineur">Entraîneur</option>
            <option value="organisateur">Organisateur</option>
          </select>
        </div>

        <div class="champ-groupe">
          <label for="localisation">Localisation</label>
          <input
            id="localisation"
            v-model="form.localisation"
            type="text"
            class="champ"
            placeholder="Paris, Lyon..."
          />
        </div>

        <div class="champ-groupe">
          <label>Mes sports <span class="label-optionnel">(optionnel)</span></label>
          <SelecteurSportNiveau v-model="sports" :multiple="true" />
        </div>

        <button type="submit" class="btn btn-primaire btn-pleine-largeur" :disabled="chargement">
          {{ chargement ? 'Création...' : 'Créer mon compte' }}
        </button>
      </form>

      <p class="auth-lien">
        Déjà un compte ?
        <RouterLink to="/connexion">Se connecter</RouterLink>
      </p>
    </div>
  </div>
</template>

<style scoped>
.page-auth {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--espace-m);
  background: var(--couleur-fond);
}

.boite-auth {
  width: 100%;
  max-width: 520px;
  padding: var(--espace-xl);
}

.auth-entete {
  text-align: center;
  margin-bottom: var(--espace-xl);
}

.auth-entete .logo-emoji {
  font-size: 2.5rem;
  display: block;
  margin-bottom: var(--espace-s);
}

.auth-entete h1 {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--couleur-texte);
  margin-bottom: 0.3rem;
}

.auth-entete p {
  color: var(--couleur-texte-discret);
  font-size: 0.9rem;
}

.label-optionnel {
  font-weight: 400;
  color: var(--couleur-texte-discret);
  font-size: 0.8rem;
}

.auth-lien {
  text-align: center;
  margin-top: var(--espace-l);
  font-size: 0.9rem;
  color: var(--couleur-texte-discret);
}

.auth-lien a {
  color: var(--couleur-primaire);
  text-decoration: none;
  font-weight: 500;
}

.auth-lien a:hover {
  text-decoration: underline;
  background: none;
}

button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
</style>
