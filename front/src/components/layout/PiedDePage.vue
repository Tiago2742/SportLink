<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <footer class="pied-de-page">
    <div class="pied-interieur">
      <p>
        © 2026 SportLink
        <span class="sep">—</span>
        <RouterLink to="/mentions-legales">Mentions légales</RouterLink>
        <span class="sep">—</span>
        <RouterLink to="/politique-confidentialite">Politique de confidentialité</RouterLink>
        <span class="sep">—</span>
        <a href="mailto:tiago.duval5@gmail.com">Contact</a>
      </p>
    </div>
  </footer>
</template>

<style scoped>
.pied-de-page {
  width: 100%;
  align-self: stretch;
  background: white;
  border-top: 1px solid var(--couleur-bordure);
  margin-top: auto;
  padding: var(--espace-l) 0;
}

.pied-interieur {
  max-width: var(--largeur-conteneur);
  margin: 0 auto;
  padding: 0 var(--espace-l);
  text-align: center;
  color: var(--couleur-texte-discret);
  font-size: 0.85rem;
}

.pied-interieur a {
  color: var(--couleur-primaire);
  text-decoration: none;
}

.pied-interieur a:hover {
  text-decoration: underline;
}

.sep {
  margin: 0 0.45rem;
  opacity: 0.4;
}
</style>
