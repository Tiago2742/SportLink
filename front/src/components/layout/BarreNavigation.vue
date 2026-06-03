<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const menuOuvert = ref(false)

function deconnecter() {
  auth.seDeconnecter()
  router.push('/connexion')
}
</script>

<template>
  <nav class="barre-nav">
    <div class="nav-interieur">
      <RouterLink to="/" class="nav-logo">
        <span class="logo-emoji">⚽</span>
        <span class="logo-texte">
          <strong>SportLink</strong>
          <small>Connectez équipes &amp; joueurs</small>
        </span>
      </RouterLink>

      <button class="btn-menu-mobile" @click="menuOuvert = !menuOuvert" aria-label="Menu">
        <span></span>
        <span></span>
        <span></span>
      </button>

      <div class="nav-liens" :class="{ ouvert: menuOuvert }">
        <RouterLink to="/" @click="menuOuvert = false">Accueil</RouterLink>
        <RouterLink to="/mes-matchs" @click="menuOuvert = false">Mes matchs</RouterLink>
        <RouterLink
          v-if="auth.utilisateur?.type === 'club'"
          to="/mes-equipes"
          @click="menuOuvert = false"
        >
          Mes équipes
        </RouterLink>
        <RouterLink to="/rechercher" @click="menuOuvert = false">Rechercher</RouterLink>
        <RouterLink to="/creer-match" @click="menuOuvert = false">Créer un match</RouterLink>
        <RouterLink to="/profil" @click="menuOuvert = false">Profil</RouterLink>
        <button class="btn-deconnexion" @click="deconnecter">Déconnexion</button>
      </div>
    </div>
  </nav>
</template>

<style scoped>
.barre-nav {
  background: white;
  border-bottom: 1px solid var(--couleur-bordure);
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
}

.nav-interieur {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 var(--espace-l);
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 64px;
}

.nav-logo {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  text-decoration: none;
  color: var(--couleur-texte);
}

.logo-emoji {
  font-size: 1.6rem;
}

.logo-texte {
  display: flex;
  flex-direction: column;
  line-height: 1.1;
}

.logo-texte strong {
  font-size: 1rem;
  color: var(--couleur-primaire);
}

.logo-texte small {
  font-size: 0.65rem;
  color: var(--couleur-texte-discret);
}

.nav-liens {
  display: flex;
  align-items: center;
  gap: 1.2rem;
}

.nav-liens a {
  text-decoration: none;
  color: #444;
  font-size: 0.9rem;
  padding: 0.2rem 0;
  border-bottom: 2px solid transparent;
  transition:
    color 0.2s,
    border-color 0.2s;
}

.nav-liens a:hover {
  color: var(--couleur-primaire);
  background: none;
}

.nav-liens a.router-link-exact-active {
  color: var(--couleur-primaire);
  border-bottom-color: var(--couleur-primaire);
  background: none;
}

.btn-deconnexion {
  background: none;
  border: 1px solid var(--couleur-bordure);
  border-radius: var(--rayon-bouton);
  padding: 0.35rem 0.9rem;
  cursor: pointer;
  font-size: 0.85rem;
  color: var(--couleur-texte-discret);
  transition: all 0.2s;
}

.btn-deconnexion:hover {
  border-color: #e53935;
  color: #e53935;
}

.btn-menu-mobile {
  display: none;
  flex-direction: column;
  gap: 5px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
}

.btn-menu-mobile span {
  display: block;
  width: 22px;
  height: 2px;
  background: var(--couleur-texte);
  border-radius: 2px;
}

@media (max-width: 768px) {
  .btn-menu-mobile {
    display: flex;
  }

  .nav-liens {
    display: none;
    position: absolute;
    top: 64px;
    left: 0;
    right: 0;
    background: white;
    flex-direction: column;
    align-items: flex-start;
    padding: var(--espace-m) var(--espace-l);
    gap: var(--espace-m);
    border-bottom: 1px solid var(--couleur-bordure);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }

  .nav-liens.ouvert {
    display: flex;
  }

  .nav-liens a {
    font-size: 1rem;
  }
}
</style>
