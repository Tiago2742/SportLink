<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AvatarEquipe from '@/components/equipes/AvatarEquipe.vue'
import { chargerEquipes } from '@/services/api'
import IconeLigne from '@/components/ui/IconeLigne.vue'
import { MapPin } from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()

const equipes = ref<any[]>([])
const chargement = ref(true)
const erreur = ref('')

onMounted(charger)

async function charger() {
  chargement.value = true
  erreur.value = ''
  try {
    equipes.value = await chargerEquipes(auth.token!, {
      clubId: auth.utilisateur!.id,
    })
  } catch {
    erreur.value = 'Impossible de charger vos équipes.'
  } finally {
    chargement.value = false
  }
}
</script>

<template>
  <div class="page-mes-equipes conteneur">
    <div class="entete-page">
      <div>
        <h1>Mes équipes</h1>
        <p class="sous-titre">Gérez les équipes de votre club par sport</p>
      </div>
      <RouterLink to="/equipes/creer" class="btn btn-primaire">
        + Créer une équipe
      </RouterLink>
    </div>

    <div v-if="chargement" class="chargement">Chargement...</div>
    <div v-else-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>

    <div v-else-if="equipes.length === 0" class="carte vide-centre">
      <p>Vous n'avez pas encore créé d'équipe.</p>
      <RouterLink to="/equipes/creer" class="btn btn-primaire">
        Créer ma première équipe
      </RouterLink>
    </div>

    <div v-else class="grille-equipes">
      <article v-for="equipe in equipes" :key="equipe.id" class="carte carte-equipe">
        <div class="carte-equipe-haut">
          <AvatarEquipe :equipe="equipe" taille="md" />
          <div class="carte-equipe-corps">
            <h2 class="carte-equipe-titre">{{ equipe.nom }}</h2>
            <p class="carte-equipe-meta">
              {{ equipe.sport?.nom ?? 'Sport' }}
              <template v-if="equipe.niveau"> · {{ equipe.niveau.libelle }}</template>
            </p>
            <IconeLigne
              v-if="equipe.localisation"
              :icone="MapPin"
              :taille="14"
              discret
              class="carte-equipe-lieu"
            >
              {{ equipe.localisation }}
            </IconeLigne>
          </div>
        </div>
        <RouterLink :to="`/equipes/${equipe.id}`" class="btn btn-secondaire btn-pleine-largeur">
          Voir l'équipe
        </RouterLink>
      </article>
    </div>
  </div>
</template>

<style scoped>
.page-mes-equipes {
  padding-top: var(--espace-xl);
  padding-bottom: var(--espace-xxl);
}

.entete-page {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--espace-m);
  margin-bottom: var(--espace-xl);
}

.entete-page h1 {
  font-size: 1.6rem;
  font-weight: 700;
}

.sous-titre {
  color: var(--couleur-texte-discret);
  font-size: 0.9rem;
  margin-top: 0.25rem;
}

.vide-centre {
  text-align: center;
  padding: var(--espace-xl);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--espace-m);
}

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
  align-items: flex-start;
  gap: var(--espace-m);
}

.carte-equipe-corps {
  flex: 1;
  min-width: 0;
}

.carte-equipe-titre {
  font-size: 1.05rem;
  font-weight: 700;
  margin-bottom: 0.35rem;
}

.carte-equipe-meta {
  font-size: 0.88rem;
  color: var(--couleur-texte-discret);
}

.carte-equipe-lieu {
  font-size: 0.85rem;
  margin-top: 0.35rem;
  color: var(--couleur-texte-discret);
}
</style>
