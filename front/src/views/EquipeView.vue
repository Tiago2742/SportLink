<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { chargerEquipe } from '@/services/api'
import AvatarEquipe from '@/components/equipes/AvatarEquipe.vue'
import { libelleRoleEquipe } from '@/utils/equipeAffichage'
import { initialesUtilisateur, nomAffichage } from '@/utils/nomAffichage'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const equipe = ref<any>(null)
const chargement = ref(true)
const erreur = ref('')

const equipeId = Number(route.params.id)

onMounted(charger)

async function charger() {
  chargement.value = true
  try {
    equipe.value = await chargerEquipe(auth.token!, equipeId)
  } catch (e: any) {
    if (e.statut === 404) {
      erreur.value = 'Cette équipe est introuvable.'
    } else {
      erreur.value = 'Impossible de charger les données de l\'équipe.'
    }
  } finally {
    chargement.value = false
  }
}

const matchsAvenir = computed(() => {
  if (!equipe.value?.membres) return []
  return []
})

function formaterDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

</script>

<template>
  <div class="page-equipe">
    <div v-if="chargement" class="chargement conteneur">Chargement...</div>
    <div v-else-if="erreur" class="conteneur alerte alerte-erreur" style="margin-top: var(--espace-xl)">
      {{ erreur }}
    </div>

    <template v-else-if="equipe">
      <div class="conteneur lien-equipe-haut">
        <RouterLink
          v-if="auth.utilisateur?.type === 'club'"
          to="/mes-equipes"
          class="lien-retour"
        >
          ← Mes équipes
        </RouterLink>
      </div>

      <!-- En-tête équipe -->
      <div class="equipe-entete">
        <div class="conteneur equipe-entete-interieur">
          <div class="equipe-identite">
            <AvatarEquipe :equipe="equipe" taille="lg" />
            <div>
              <h1 class="equipe-nom">{{ equipe.nom }}</h1>
              <div class="equipe-meta">
                <span>{{ equipe.sport?.nom ?? equipe.sport }}</span>
                <span v-if="equipe.niveau">· {{ equipe.niveau?.libelle ?? equipe.niveau }}</span>
                <span v-if="equipe.localisation">· 📍 {{ equipe.localisation }}</span>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Contenu principal -->
      <div class="conteneur equipe-contenu">

        <!-- Membres -->
        <section class="carte section-membres">
          <div class="section-titre-icone">
            <span>👥</span>
            <h2>Membres de l'équipe</h2>
            <span class="membres-count">{{ equipe.membres?.length ?? 0 }} membre{{ (equipe.membres?.length ?? 0) > 1 ? 's' : '' }}</span>
          </div>

          <div v-if="!equipe.membres?.length" class="vide-section">
            Aucun membre pour le moment.
          </div>

          <div v-else class="grille-membres">
            <div
              v-for="membre in equipe.membres"
              :key="membre.id"
              class="carte-membre"
            >
              <div class="membre-avatar">
                {{ initialesUtilisateur(membre.utilisateur) }}
              </div>
              <div class="membre-info">
                <strong>{{ nomAffichage(membre.utilisateur) }}</strong>
                <span class="membre-role">{{ libelleRoleEquipe(membre.role) }}</span>
              </div>
            </div>
          </div>
        </section>

        <!-- Statistiques + Calendrier -->
        <div class="bas-contenu">
          <!-- Calendrier -->
          <section class="carte section-calendrier">
            <div class="section-titre-icone">
              <span>📅</span>
              <h2>Calendrier</h2>
            </div>

            <div class="vide-section">
              <p>Inscrivez l'équipe à des matchs pour voir le calendrier.</p>
              <RouterLink to="/rechercher" class="btn btn-secondaire" style="margin-top: var(--espace-m)">
                Trouver un match
              </RouterLink>
            </div>
          </section>

          <!-- Statistiques -->
          <section class="carte section-stats">
            <div class="section-titre-icone">
              <span>📊</span>
              <h2>Statistiques de l'équipe</h2>
            </div>

            <div class="stats-grille">
              <div class="stat-principale">
                <span class="stat-nombre">0</span>
                <span class="stat-label">Matchs joués</span>
              </div>
              <div class="stats-secondaires">
                <div class="stat-item victoire">
                  <span class="stat-nombre">0</span>
                  <span class="stat-label">Victoires</span>
                </div>
                <div class="stat-item defaite">
                  <span class="stat-nombre">0</span>
                  <span class="stat-label">Défaites</span>
                </div>
              </div>
            </div>

            <div class="taux-victoire">
              <div class="taux-label">
                <span>Taux de victoires</span>
                <span class="taux-valeur">—</span>
              </div>
              <div class="barre-progression">
                <div class="barre-remplie" style="width: 0%"></div>
              </div>
            </div>

            <div class="stats-buts">
              <div class="stat-but">
                <span>Buts marqués</span>
                <strong>—</strong>
              </div>
              <div class="stat-but">
                <span>Buts encaissés</span>
                <strong>—</strong>
              </div>
            </div>
          </section>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.page-equipe {
  padding-bottom: var(--espace-xxl);
}

.lien-equipe-haut {
  padding-top: var(--espace-m);
}

.lien-retour {
  font-size: 0.9rem;
  color: var(--couleur-texte-discret);
  text-decoration: none;
}

.lien-retour:hover {
  color: var(--couleur-primaire);
}

.equipe-entete {
  background: white;
  border-bottom: 1px solid var(--couleur-bordure);
  padding: var(--espace-l) 0;
  margin-bottom: var(--espace-xl);
}

.equipe-entete-interieur {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: var(--espace-m);
}

.equipe-identite {
  display: flex;
  align-items: center;
  gap: var(--espace-l);
}

.equipe-nom {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 0.2rem;
}

.equipe-meta {
  font-size: 0.9rem;
  color: var(--couleur-texte-discret);
  display: flex;
  gap: 0.5rem;
}

.equipe-contenu {
  display: flex;
  flex-direction: column;
  gap: var(--espace-l);
}

.section-membres,
.section-calendrier,
.section-stats {
  padding: var(--espace-l);
}

.section-titre-icone {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  margin-bottom: var(--espace-l);
  padding-bottom: var(--espace-m);
  border-bottom: 1px solid var(--couleur-bordure);
}

.section-titre-icone h2 {
  font-size: 1rem;
  font-weight: 700;
  flex: 1;
}

.membres-count {
  font-size: 0.82rem;
  color: var(--couleur-texte-discret);
  background: var(--couleur-fond);
  padding: 0.2rem 0.6rem;
  border-radius: var(--rayon-badge);
}

.grille-membres {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: var(--espace-m);
}

.carte-membre {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: var(--espace-l);
  background: var(--couleur-fond);
  border-radius: var(--rayon-carte);
  gap: var(--espace-s);
}

.membre-avatar {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--couleur-primaire);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  font-weight: 700;
}

.membre-info {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.membre-info strong {
  font-size: 0.9rem;
}

.membre-info span {
  font-size: 0.78rem;
  color: var(--couleur-texte-discret);
}

.bas-contenu {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--espace-l);
}

.vide-section {
  text-align: center;
  padding: var(--espace-l);
  color: var(--couleur-texte-discret);
  font-size: 0.88rem;
}

.stats-grille {
  display: flex;
  gap: var(--espace-m);
  margin-bottom: var(--espace-l);
}

.stat-principale {
  flex: 1;
  background: var(--couleur-primaire);
  color: white;
  border-radius: var(--rayon-carte);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--espace-l);
}

.stats-secondaires {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: var(--espace-s);
}

.stat-item {
  flex: 1;
  border-radius: var(--rayon-carte);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--espace-s);
}

.stat-item.victoire {
  background: var(--couleur-confirme-fond);
  color: var(--couleur-confirme);
}

.stat-item.defaite {
  background: var(--couleur-refuse-fond);
  color: var(--couleur-refuse);
}

.stat-nombre {
  font-size: 1.6rem;
  font-weight: 800;
  line-height: 1;
}

.stat-label {
  font-size: 0.78rem;
  opacity: 0.8;
}

.taux-victoire {
  margin-bottom: var(--espace-m);
}

.taux-label {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  margin-bottom: 0.3rem;
}

.taux-valeur {
  font-weight: 700;
  color: var(--couleur-primaire);
}

.barre-progression {
  height: 8px;
  background: var(--couleur-fond);
  border-radius: 4px;
  overflow: hidden;
}

.barre-remplie {
  height: 100%;
  background: var(--couleur-primaire);
  border-radius: 4px;
  transition: width 0.5s ease;
}

.stats-buts {
  display: flex;
  flex-direction: column;
  gap: var(--espace-xs);
}

.stat-but {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.4rem 0;
  border-bottom: 1px solid var(--couleur-bordure);
  font-size: 0.88rem;
}

.stat-but span {
  color: var(--couleur-texte-discret);
}

.stat-but strong {
  font-weight: 700;
}

@media (max-width: 768px) {
  .bas-contenu {
    grid-template-columns: 1fr;
  }

  .equipe-entete-interieur {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
