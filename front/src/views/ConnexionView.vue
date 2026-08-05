<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import LogoSportLink from '@/components/brand/LogoSportLink.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const email = ref('')
const motDePasse = ref('')
const erreur = ref('') 
const chargement = ref(false)

const messageSessionExpiree = computed(() =>
  route.query.sessionExpiree === '1' ? 'Session expirée, reconnectez-vous.' : '',
)

async function soumettre() {
  erreur.value = ''
  chargement.value = true
  try {
    await auth.seConnecter(email.value, motDePasse.value)
    router.push('/')
  } catch {
    erreur.value = 'Email ou mot de passe incorrect.'
  } finally {
    chargement.value = false
  }
}
</script>

<template>
  <div class="page-auth">

    <div class="boite-auth">

      <div class="auth-entete">
        <div class="auth-logo">
          <LogoSportLink variant="complet" taille="auth" />
        </div>
        <span class="auth-eyebrow">Connexion sécurisée</span>
        <h1 class="auth-titre">Bon retour</h1>
        <p class="auth-sous-titre">Accédez à votre compte SportLink</p>
      </div>

      <form class="auth-form" @submit.prevent="soumettre">
        <div v-if="messageSessionExpiree" class="auth-info">{{ messageSessionExpiree }}</div>
        <div v-if="erreur" class="auth-erreur">{{ erreur }}</div>

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

        <button
          type="submit"
          class="btn-submit"
          :class="{ 'btn-submit--loading': chargement }"
          :disabled="chargement"
        >
          {{ chargement ? 'Connexion en cours…' : 'Se connecter' }}
        </button>
      </form>

    </div>

    <!-- Hors carte, sur fond sombre -->
    <p class="auth-lien">
      Pas encore de compte ?
      <RouterLink to="/inscription">Créer un compte</RouterLink>
    </p>

  </div>
</template>

<style scoped>
/* ══════════════════════════════════════
   FOND DARK
══════════════════════════════════════ */
.page-auth {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--espace-l) var(--espace-m);
  background:
    radial-gradient(ellipse 55% 55% at 15% 20%, rgba(154, 230, 0, 0.06) 0%, transparent 55%),
    linear-gradient(148deg, #071a07 0%, #0d280d 40%, #1b5e20 78%, #256025 100%);
  position: relative;
  overflow: hidden;
}

/* Orbe décoratif bas-droite */
.page-auth::before {
  content: '';
  position: absolute;
  bottom: -100px;
  right: -100px;
  width: 480px;
  height: 480px;
  background: radial-gradient(circle, rgba(154, 230, 0, 0.055) 0%, transparent 65%);
  border-radius: 50%;
  pointer-events: none;
  animation: orbeFlottement 20s ease-in-out infinite;
}

/* Orbe décoratif haut-gauche */
.page-auth::after {
  content: '';
  position: absolute;
  top: -80px;
  left: -80px;
  width: 360px;
  height: 360px;
  background: radial-gradient(circle, rgba(46, 125, 50, 0.12) 0%, transparent 65%);
  border-radius: 50%;
  pointer-events: none;
  animation: orbeFlottement 26s ease-in-out infinite reverse;
}

/* ══════════════════════════════════════
   CARTE AUTH
══════════════════════════════════════ */
.boite-auth {
  width: 100%;
  max-width: 420px;
  background: #ffffff;
  border-radius: 14px;
  padding: var(--espace-xl) var(--espace-xl);
  box-shadow:
    0 0 0 1px rgba(0, 0, 0, 0.06),
    0 28px 72px rgba(0, 0, 0, 0.32),
    0 8px 24px rgba(0, 0, 0, 0.14);
  position: relative;
  z-index: 1;
  animation: cardEntree 0.65s cubic-bezier(0.22, 1, 0.36, 1) 0.05s both;
}

/* ── En-tête ── */
.auth-entete {
  text-align: center;
  margin-bottom: var(--espace-xl);
}

.auth-logo {
  display: flex;
  justify-content: center;
  margin-bottom: var(--espace-l);
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.1s both;
}

.auth-eyebrow {
  display: inline-flex;
  align-items: center;
  background: var(--couleur-accent-fond);
  color: var(--couleur-accent-texte);
  padding: 0.22rem 0.75rem;
  border-radius: var(--rayon-badge);
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-bottom: var(--espace-s);
  animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.16s both;
}

.auth-titre {
  font-size: 1.85rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  color: var(--couleur-titre);
  margin-bottom: 0.3rem;
  animation: fadeUp 0.55s cubic-bezier(0.22, 1, 0.36, 1) 0.22s both;
}

.auth-sous-titre {
  font-size: 0.9rem;
  color: var(--couleur-texte-discret);
  animation: fadeUp 0.55s cubic-bezier(0.22, 1, 0.36, 1) 0.28s both;
}

/* ── Formulaire ── */
.auth-form {
  animation: fadeUp 0.6s cubic-bezier(0.22, 1, 0.36, 1) 0.34s both;
}

/* Erreur avec shake */
.auth-info {
  background: #fff8e1;
  color: #e65100;
  border: 1px solid rgba(230, 81, 0, 0.22);
  border-radius: 8px;
  padding: var(--espace-s) var(--espace-m);
  font-size: 0.88rem;
  margin-bottom: var(--espace-m);
}

.auth-erreur {
  background: var(--couleur-refuse-fond);
  color: var(--couleur-refuse);
  border: 1px solid rgba(198, 40, 40, 0.22);
  border-radius: 8px;
  padding: var(--espace-s) var(--espace-m);
  font-size: 0.88rem;
  margin-bottom: var(--espace-m);
  animation: shake 0.42s cubic-bezier(0.22, 1, 0.36, 1) both;
}

/* Champs : glow focus amplifié */
.champ-groupe label {
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--couleur-titre);
  letter-spacing: 0.01em;
}

.champ-groupe .champ:focus {
  border-color: var(--couleur-primaire);
  box-shadow: 0 0 0 3px rgba(154, 230, 0, 0.28), 0 0 0 1px var(--couleur-primaire);
}

/* ── Bouton submit ── */
.btn-submit {
  width: 100%;
  padding: 0.82rem;
  background: var(--degrade-primaire);
  color: white;
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  box-shadow: var(--ombre-bouton);
  transition:
    transform 0.22s cubic-bezier(0.22, 1, 0.36, 1),
    box-shadow 0.22s cubic-bezier(0.22, 1, 0.36, 1);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  margin-top: var(--espace-s);
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: var(--ombre-bouton-survol);
}

.btn-submit:active:not(:disabled) {
  transform: scale(0.97);
  box-shadow: var(--ombre-bouton);
}

.btn-submit:disabled {
  opacity: 0.8;
  cursor: not-allowed;
}

/* Spinner quand chargement */
.btn-submit--loading::after {
  content: '';
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.35);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.65s linear infinite;
  flex-shrink: 0;
}

/* ══════════════════════════════════════
   LIEN BAS DE PAGE (sur fond dark)
══════════════════════════════════════ */
.auth-lien {
  margin-top: var(--espace-l);
  font-size: 0.9rem;
  color: rgba(255, 255, 255, 0.5);
  text-align: center;
  position: relative;
  z-index: 1;
  animation: fadeUp 0.6s cubic-bezier(0.22, 1, 0.36, 1) 0.42s both;
}

.auth-lien a {
  color: var(--couleur-accent);
  font-weight: 600;
  text-decoration: none;
  position: relative;
}

/* Underline slide gauche → droite au hover */
.auth-lien a::after {
  content: '';
  position: absolute;
  bottom: -1px;
  left: 0;
  right: 100%;
  height: 1px;
  background: var(--couleur-accent);
  transition: right 0.25s cubic-bezier(0.22, 1, 0.36, 1);
}

.auth-lien a:hover::after {
  right: 0;
}

/* ══════════════════════════════════════
   ANIMATIONS
══════════════════════════════════════ */
@keyframes cardEntree {
  from {
    opacity: 0;
    transform: translateY(24px) scale(0.97);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes fadeUp {
  from {
    opacity: 0;
    transform: translateY(14px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  20%       { transform: translateX(-5px); }
  40%       { transform: translateX(5px); }
  60%       { transform: translateX(-3px); }
  80%       { transform: translateX(3px); }
}

@keyframes orbeFlottement {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33%       { transform: translate(-18px, -28px) scale(1.05); }
  66%       { transform: translate(14px, -12px) scale(0.96); }
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* ══════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════ */
@media (max-width: 480px) {
  .boite-auth {
    padding: var(--espace-l);
  }

  .auth-titre {
    font-size: 1.5rem;
  }
}
</style>
