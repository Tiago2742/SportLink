<script setup lang="ts">
import { ref } from 'vue'
import AvatarEquipe from '@/components/equipes/AvatarEquipe.vue'
import { repondreAdhesionEquipe } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import IconeLigne from '@/components/ui/IconeLigne.vue'
import { nomAffichage } from '@/utils/nomAffichage'
import { MapPin } from 'lucide-vue-next'

defineProps<{
  invitations: any[]
  chargement: boolean
}>()

const emit = defineEmits<{
  actualiser: []
}>()

const auth = useAuthStore()
const erreur = ref('')
const actionEnCours = ref<number | null>(null)

async function repondre(invitation: any, statut: 'confirme' | 'refuse') {
  actionEnCours.value = invitation.id
  erreur.value = ''
  try {
    await repondreAdhesionEquipe(
      auth.token!,
      invitation.equipe.id,
      invitation.id,
      statut,
    )
    emit('actualiser')
  } catch (e: any) {
    erreur.value = e.message || 'Action impossible.'
  } finally {
    actionEnCours.value = null
  }
}
</script>

<template>
  <div class="onglet-invitations">
    <div v-if="erreur" class="alerte alerte-erreur">{{ erreur }}</div>
    <div v-if="chargement" class="chargement">Chargement...</div>
    <div v-else-if="!invitations.length" class="carte vide-centre">
      <p>Aucune invitation en attente.</p>
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
              <IconeLigne :icone="MapPin" :taille="14" discret>{{ inv.equipe.localisation }}</IconeLigne>
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
}
</style>
