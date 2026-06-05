<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const email = ref('')
const motDePasse = ref('')
const erreur = ref('')
const chargement = ref(false)

async function soumettre() {
  erreur.value = ''
  chargement.value = true
  try {
    await auth.seConnecter(email.value, motDePasse.value)
    router.push('/')
  } catch (e: any) {
    erreur.value = 'Email ou mot de passe incorrect.'
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
        <h1>Connexion</h1>
        <p>Accédez à votre compte SportLink</p>
      </div>

      <form @submit.prevent="soumettre">
        <div v-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>

        <div class="champ-groupe">
          <label for="email">Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            class="champ"
            placeholder="vous@exemple.fr"
            required
            autocomplete="email"
          />
        </div>

        <div class="champ-groupe">
          <label for="mot-de-passe">Mot de passe</label>
          <input
            id="mot-de-passe"
            v-model="motDePasse"
            type="password"
            class="champ"
            placeholder="Votre mot de passe"
            required
            autocomplete="current-password"
          />
        </div>

        <button type="submit" class="btn btn-primaire btn-pleine-largeur" :disabled="chargement">
          {{ chargement ? 'Connexion...' : 'Se connecter' }}
        </button>
      </form>

      <p class="auth-lien">
        Pas encore de compte ?
        <RouterLink to="/inscription">Créer un compte</RouterLink>
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
  max-width: 420px;
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
