<template>
  <footer class="pied-de-page">
    <div class="pied-interieur">
      <p>© 2026 SportLink — <a href="#">Mentions légales</a> — <a href="#">Contact</a></p>
    </div>
  </footer>
</template>

<style scoped>
.pied-de-page {
  background: white;
  border-top: 1px solid var(--couleur-bordure);
  margin-top: auto;
  padding: var(--espace-l) 0;
}

.pied-interieur {
  max-width: 1200px;
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
  background: none;
}
</style>
