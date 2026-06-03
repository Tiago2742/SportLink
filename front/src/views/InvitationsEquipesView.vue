<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import AvatarEquipe from '@/components/equipes/AvatarEquipe.vue'
import { chargerInvitationsEquipes, repondreInvitationEquipe } from '@/services/api'
import { nomAffichage } from '@/utils/nomAffichage'

const auth = useAuthStore()

const invitations = ref<any[]>([])
const chargement = ref(true)
const erreur = ref('')
const actionEnCours = ref<number | null>(null)

onMounted(charger)

async function charger() {
  chargement.value = true
  erreur.value = ''
  try {
    invitations.value = await chargerInvitationsEquipes(auth.token!)
  } catch {
    erreur.value = 'Impossible de charger vos invitations.'
  } finally {
    chargement.value = false
  }
}

async function repondre(invitation: any, statut: 'confirme' | 'refuse') {
  actionEnCours.value = invitation.id
  erreur.value = ''
  try {
    await repondreInvitationEquipe(
      auth.token!,
      invitation.equipe.id,
      invitation.id,
      statut,
    )
    invitations.value = invitations.value.filter((i) => i.id !== invitation.id)
  } catch (e: any) {
    erreur.value = e.message || 'Action impossible.'
  } finally {
    actionEnCours.value = null
  }
}
</script>

<template>
  <div class="page-invitations conteneur">
    <div class="entete-page">
      <h1>Invitations d'équipe</h1>
      <p class="sous-titre">Les clubs qui vous invitent — une équipe par sport maximum</p>
    </div>

    <div v-if="chargement" class="chargement">Chargement...</div>
    <div v-else-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>

    <div v-else-if="invitations.length === 0" class="carte vide-centre">
      <p>Aucune invitation en attente.</p>
      <RouterLink to="/" class="btn btn-secondaire">Retour à l'accueil</RouterLink>
    </div>

    <div v-else class="liste-invitations">
      <article v-for="inv in invitations" :key="inv.id" class="carte carte-invitation">
        <div class="invitation-haut">
          <AvatarEquipe :equipe="inv.equipe" taille="md" />
          <div class="invitation-corps">
            <h2>{{ inv.equipe?.nom }}</h2>
            <p class="invitation-meta">
              {{ inv.equipe?.sport?.nom }}
              <template v-if="inv.equipe?.niveau"> · {{ inv.equipe.niveau.libelle }}</template>
            </p>
            <p class="invitation-club">
              Club : <strong>{{ nomAffichage(inv.equipe?.club) }}</strong>
            </p>
            <p v-if="inv.equipe?.localisation" class="invitation-lieu">
              📍 {{ inv.equipe.localisation }}
            </p>
          </div>
        </div>
        <div class="invitation-actions">
          <button
            class="btn btn-primaire"
            :disabled="actionEnCours === inv.id"
            @click="repondre(inv, 'confirme')"
          >
            Accepter
          </button>
          <button
            class="btn btn-secondaire"
            :disabled="actionEnCours === inv.id"
            @click="repondre(inv, 'refuse')"
          >
            Refuser
          </button>
        </div>
      </article>
    </div>
  </div>
</template>

<style scoped>
.page-invitations {
  padding-top: var(--espace-xl);
  padding-bottom: var(--espace-xxl);
}

.entete-page {
  margin-bottom: var(--espace-xl);
}

.entete-page h1 {
  font-size: 1.5rem;
  margin-bottom: 0.25rem;
}

.sous-titre {
  color: var(--couleur-texte-discret);
  font-size: 0.9rem;
}

.liste-invitations {
  display: flex;
  flex-direction: column;
  gap: var(--espace-m);
}

.carte-invitation {
  padding: var(--espace-l);
}

.invitation-haut {
  display: flex;
  gap: var(--espace-m);
  margin-bottom: var(--espace-m);
}

.invitation-corps h2 {
  font-size: 1.1rem;
  margin-bottom: 0.2rem;
}

.invitation-meta,
.invitation-lieu {
  font-size: 0.88rem;
  color: var(--couleur-texte-discret);
}

.invitation-club {
  font-size: 0.9rem;
  margin-top: 0.35rem;
}

.invitation-actions {
  display: flex;
  gap: var(--espace-s);
  flex-wrap: wrap;
}

.vide-centre {
  text-align: center;
  padding: var(--espace-xl);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--espace-m);
}
</style>
