<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import type { ErreurApi } from '@/services/api'
import LogoSportLink from '@/components/brand/LogoSportLink.vue'
import SelecteurSportNiveau, { type EntreeSportNiveau } from '@/components/form/SelecteurSportNiveau.vue'

const auth = useAuthStore()
const router = useRouter()

const form = ref({
  email: '',
  password: '',
  nom: '',
  prenom: '',
  type: 'joueur',
  localisation: '',
  consentement: false,
})

const estClub = computed(() => form.value.type === 'club')

const reglesMdp = computed(() => ({
  longueur: form.value.password.length >= 8,
  lettre:   /[a-zA-Z]/.test(form.value.password),
  chiffre:  /[0-9]/.test(form.value.password),
}))

const sports = ref<EntreeSportNiveau[]>([])
const erreur = ref('')
const chargement = ref(false)

// Vider les sports déclarés quand le type change (évite les sports individuels bloqués pour un club)
watch(() => form.value.type, () => {
  sports.value = []
})

async function soumettre() {
  erreur.value = ''

  // C7 — au moins 1 sport requis
  if (sports.value.length === 0) {
    erreur.value = 'Vous devez déclarer au moins un sport pour créer votre compte.'
    return
  }

  chargement.value = true
  try {
    const donnees: Record<string, unknown> = {
      email: form.value.email,
      password: form.value.password,
      nom: form.value.nom,
      type: form.value.type,
      localisation: form.value.localisation,
      sports: sports.value,
    }
    if (estClub.value) {
      delete donnees.prenom
    } else {
      donnees.prenom = form.value.prenom.trim()
    }
    donnees.consentement = form.value.consentement
    await auth.sInscrire(donnees as Record<string, unknown> & { email: string; password: string })
    router.push('/')
  } catch (e) {
    const err = e as ErreurApi
    if (err.statut === 409) {
      erreur.value = 'Cette adresse email est déjà utilisée.'
    } else if (err.statut === 400 || err.statut === 422) {
      const donnees = err.donnees as { erreur?: string; erreurs?: Record<string, string> } | null
      const msg = err.message || donnees?.erreur || donnees?.erreurs?.['prenom']
      erreur.value = msg || 'Veuillez remplir tous les champs obligatoires.'
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

    <div class="boite-auth">

      <div class="auth-entete">
        <div class="auth-logo">
          <LogoSportLink variant="complet" taille="auth" />
        </div>
        <span class="auth-eyebrow">Rejoignez SportLink</span>
        <h1 class="auth-titre">Créer un compte</h1>
        <p class="auth-sous-titre">Rejoignez la communauté SportLink</p>
      </div>

      <form class="auth-form" @submit.prevent="soumettre">
        <div v-if="erreur" class="auth-erreur">{{ erreur }}</div>

        <!-- Toggle type de profil -->
        <div class="champ-groupe">
          <label>Type de profil <span class="obligatoire">*</span></label>
          <div class="type-toggle" role="group" aria-label="Type de profil">
            <button
              type="button"
              class="type-btn"
              :class="{ 'type-btn--actif': form.type === 'joueur' }"
              @click="form.type = 'joueur'"
            >
              Joueur
            </button>
            <button
              type="button"
              class="type-btn"
              :class="{ 'type-btn--actif': form.type === 'club' }"
              @click="form.type = 'club'"
            >
              Club / Association
            </button>
          </div>
        </div>

        <!-- Nom (club) ou prénom + nom (joueur) -->
        <template v-if="estClub">
          <div class="champ-groupe">
            <label for="nom">Nom du club <span class="obligatoire">*</span></label>
            <input
              id="nom"
              v-model="form.nom"
              type="text"
              class="champ"
              placeholder="AS Arras Sport"
              required
            />
          </div>
        </template>

        <div v-else class="grille-2">
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
          <ul v-if="form.password" class="mdp-regles">
            <li :class="reglesMdp.longueur ? 'regle--ok' : 'regle--ko'">8 caractères minimum</li>
            <li :class="reglesMdp.lettre   ? 'regle--ok' : 'regle--ko'">Au moins une lettre</li>
            <li :class="reglesMdp.chiffre  ? 'regle--ok' : 'regle--ko'">Au moins un chiffre</li>
          </ul>
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
          <label>
            Mes sports <span class="obligatoire">*</span>
            <span class="label-optionnel">(au moins 1 requis)</span>
          </label>
          <SelecteurSportNiveau
            v-model="sports"
            :multiple="true"
            :filtre-type="estClub ? 'collectif' : undefined"
          />
        </div>

        <!-- RGPD — consentement obligatoire -->
        <div class="champ-consentement">
          <label class="consentement-label">
            <input
              v-model="form.consentement"
              type="checkbox"
              class="consentement-case"
            />
            <span>
              J'accepte la
              <a href="/politique-confidentialite" target="_blank" rel="noopener">politique de confidentialité</a>
              <span class="obligatoire"> *</span>
            </span>
          </label>
        </div>

        <button
          type="submit"
          class="btn-submit"
          :class="{ 'btn-submit--loading': chargement }"
          :disabled="chargement || !form.consentement"
        >
          {{ chargement ? 'Création en cours…' : 'Créer mon compte' }}
        </button>
      </form>

    </div>

    <!-- Hors carte, sur fond sombre -->
    <p class="auth-lien">
      Déjà un compte ?
      <RouterLink to="/connexion">Se connecter</RouterLink>
    </p>

  </div>
</template>

<style scoped>
/* ══════════════════════════════════════
   FOND DARK (identique à ConnexionView)
══════════════════════════════════════ */
.page-auth {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--espace-m) var(--espace-m);
  background:
    radial-gradient(ellipse 55% 55% at 15% 20%, rgba(154, 230, 0, 0.06) 0%, transparent 55%),
    linear-gradient(148deg, #071a07 0%, #0d280d 40%, #1b5e20 78%, #256025 100%);
  position: relative;
  overflow: hidden;
}

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
  max-width: 520px;
  background: #ffffff;
  border-radius: 14px;
  padding: 1.25rem 1.5rem;
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
  margin-bottom: 0.65rem;
}

.auth-logo {
  display: flex;
  justify-content: center;
  margin-bottom: 0.35rem;
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
  font-size: 1.5rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  color: var(--couleur-titre);
  margin-bottom: 0.1rem;
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

.auth-form .champ-groupe {
  margin-bottom: 0.55rem;
  gap: 0.2rem;
}

.auth-form .champ {
  padding: 0.5rem 0.85rem;
}

.auth-form .grille-2 {
  gap: var(--espace-m);
}

/* Erreur avec shake */
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

/* Labels plus marqués */
.champ-groupe label {
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--couleur-titre);
  letter-spacing: 0.01em;
}

.label-optionnel {
  font-weight: 400;
  color: var(--couleur-texte-discret);
  font-size: 0.78rem;
  margin-left: 0.3rem;
}

/* Glow focus amplifié */
.champ-groupe .champ:focus {
  border-color: var(--couleur-primaire);
  box-shadow: 0 0 0 3px rgba(154, 230, 0, 0.28), 0 0 0 1px var(--couleur-primaire);
}

/* ── Toggle type de profil ── */
.type-toggle {
  display: flex;
  border: 1.5px solid var(--couleur-bordure);
  border-radius: 8px;
  overflow: hidden;
}

.type-btn {
  flex: 1;
  padding: 0.45rem var(--espace-m);
  border: none;
  background: transparent;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
  color: var(--couleur-texte-discret);
  transition:
    background 0.2s ease,
    color 0.2s ease;
}

.type-btn + .type-btn {
  border-left: 1.5px solid var(--couleur-bordure);
}

.type-btn--actif {
  background: var(--couleur-primaire-tres-claire);
  color: var(--couleur-primaire);
}

.type-btn:hover:not(.type-btn--actif) {
  background: var(--couleur-fond);
  color: var(--couleur-texte);
}

/* ── Indicateur de politique de mot de passe ── */
.mdp-regles {
  list-style: none;
  padding: 0;
  margin: 0.3rem 0 0;
  display: flex;
  flex-direction: column;
  gap: 0.18rem;
}

.mdp-regles li {
  font-size: 0.78rem;
  padding-left: 1.1rem;
  position: relative;
}

.mdp-regles li::before {
  content: '✗';
  position: absolute;
  left: 0;
  font-weight: 700;
}

.regle--ok {
  color: var(--couleur-primaire-foncee, #388e3c);
}

.regle--ok::before {
  content: '✓' !important;
}

.regle--ko {
  color: var(--couleur-texte-discret);
}

/* ── Consentement RGPD ── */
.champ-consentement {
  margin-bottom: 0.2rem;
}

.consentement-label {
  display: flex;
  align-items: flex-start;
  gap: 0.55rem;
  cursor: pointer;
  font-size: 0.83rem;
  color: var(--couleur-texte);
  line-height: 1.5;
}

.consentement-case {
  flex-shrink: 0;
  width: 16px;
  height: 16px;
  margin-top: 2px;
  accent-color: var(--couleur-primaire);
  cursor: pointer;
}

.consentement-label a {
  color: var(--couleur-primaire);
  text-decoration: underline;
  text-underline-offset: 2px;
}

/* ── Bouton submit ── */
.btn-submit {
  width: 100%;
  padding: 0.68rem;
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
  margin-top: 0.4rem;
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
@media (max-width: 560px) {
  .boite-auth {
    padding: var(--espace-l);
  }

  .auth-titre {
    font-size: 1.5rem;
  }
}
</style>
