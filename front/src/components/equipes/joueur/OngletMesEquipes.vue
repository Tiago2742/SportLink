<script setup lang="ts">
import { RouterLink } from 'vue-router'
import AvatarEquipe from '@/components/equipes/AvatarEquipe.vue'
import { libelleRoleEquipe } from '@/utils/equipeAffichage'
import { nomAffichage } from '@/utils/nomAffichage'

defineProps<{
  mesEquipes: any[]
  chargement: boolean
}>()
</script>

<template>
  <div class="onglet-mes-equipes">
    <div v-if="chargement" class="chargement">Chargement...</div>
    <div v-else-if="!mesEquipes.length" class="carte vide-centre">
      <p>Vous n'êtes membre d'aucune équipe pour le moment.</p>
      <p class="texte-discret">Utilisez l'onglet « Trouver une équipe » pour envoyer une demande.</p>
    </div>
    <div v-else class="grille-equipes">
      <article v-for="adhesion in mesEquipes" :key="adhesion.id" class="carte carte-equipe">
        <div class="carte-equipe-haut">
          <AvatarEquipe :equipe="adhesion.equipe" taille="md" />
          <div class="carte-equipe-corps">
            <h2 class="carte-equipe-titre">{{ adhesion.equipe?.nom }}</h2>
            <p class="carte-equipe-meta">
              {{ adhesion.equipe?.sport?.nom }}
              <template v-if="adhesion.equipe?.niveau"> · {{ adhesion.equipe.niveau.libelle }}</template>
            </p>
            <p class="carte-equipe-club">
              {{ nomAffichage(adhesion.equipe?.club) }}
            </p>
            <p v-if="adhesion.equipe?.localisation" class="carte-equipe-lieu">
              📍 {{ adhesion.equipe.localisation }}
            </p>
            <span class="carte-equipe-role">{{ libelleRoleEquipe(adhesion.role) }}</span>
          </div>
        </div>
        <RouterLink
          :to="`/equipes/${adhesion.equipe?.id}`"
          class="btn btn-secondaire btn-pleine-largeur"
        >
          Voir l'équipe
        </RouterLink>
      </article>
    </div>
  </div>
</template>

<style scoped>
.grille-equipes {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: var(--espace-m);
}

.carte-equipe {
  padding: var(--espace-l);
  display: flex;
  flex-direction: column;
  gap: var(--espace-m);
}

.carte-equipe-haut {
  display: flex;
  gap: var(--espace-m);
}

.carte-equipe-titre {
  font-size: 1.05rem;
  margin-bottom: 0.2rem;
}

.carte-equipe-meta,
.carte-equipe-lieu,
.carte-equipe-club {
  font-size: 0.88rem;
  color: var(--couleur-texte-discret);
}

.carte-equipe-role {
  display: inline-block;
  margin-top: 0.35rem;
  font-size: 0.78rem;
  padding: 0.15rem 0.5rem;
  background: var(--couleur-confirme-fond);
  color: var(--couleur-confirme);
  border-radius: var(--rayon-badge);
}

.texte-discret {
  font-size: 0.88rem;
  color: var(--couleur-texte-discret);
}

.vide-centre {
  text-align: center;
  padding: var(--espace-xl);
}
</style>
