<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { chargerMesMatchs, chargerEquipes, chargerProfil } from '@/services/api'
const auth = useAuthStore()
const router = useRouter()

const profil = ref<any>(auth.utilisateur)
const mesEquipes = ref<any[]>([])
const mesMatchs = ref<any[]>([])
const chargement = ref(true)

const modeEdition = ref(false)
const formEdition = ref({ nom: '', prenom: '', localisation: '' })
const erreurEdition = ref('')

onMounted(async () => {
  const userId = auth.utilisateur?.id
  if (!userId) {
    chargement.value = false
    return
  }
  try {
    const [matchs, equipes] = await Promise.all([
      chargerMesMatchs(auth.token!),
      chargerEquipes(auth.token!, { clubId: userId }),
    ])
    mesMatchs.value = matchs
    mesEquipes.value = equipes
  } finally {
    chargement.value = false
  }
})

function ouvrirEdition() {
  formEdition.value = {
    nom: profil.value?.nom || '',
    prenom: profil.value?.prenom || '',
    localisation: profil.value?.localisation || '',
  }
  modeEdition.value = true
}

function annulerEdition() {
  modeEdition.value = false
  erreurEdition.value = ''
}

function formaterDate(dateStr: string) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}

const matchsAvenir = computed(() =>
  mesMatchs.value.filter((m) => new Date(m.dateMatch) >= new Date()),
)

const matchsPasses = computed(() =>
  mesMatchs.value.filter((m) => new Date(m.dateMatch) < new Date()),
)

const estClub = computed(() => profil.value?.type === 'club')
</script>

<template>
  <div class="page-profil conteneur">
    <div class="profil-entete">
      <h1>Mon Profil</h1>
      <p class="sous-titre">Gérez vos informations personnelles et vos activités sportives</p>
    </div>

    <div v-if="chargement" class="chargement">Chargement...</div>

    <template v-else>
      <div class="profil-grille">
        <!-- Informations personnelles -->
        <section class="carte section-profil">
          <div class="section-titre-icone">
            <span class="titre-icone">👤</span>
            <h2>Informations personnelles</h2>
          </div>

          <template v-if="!modeEdition">
            <div class="grille-2" style="margin-bottom: var(--espace-m)">
              <div class="champ-affichage" :class="{ 'champ-affichage-pleine': estClub }">
                <label>{{ estClub ? 'Nom du club' : 'Nom' }}</label>
                <span>{{ profil?.nom || '—' }}</span>
              </div>
              <div v-if="!estClub" class="champ-affichage">
                <label>Prénom</label>
                <span>{{ profil?.prenom || '—' }}</span>
              </div>
              <div class="champ-affichage">
                <label>Type</label>
                <span>{{ profil?.type || '—' }}</span>
              </div>
              <div class="champ-affichage">
                <label>Localisation</label>
                <span>{{ profil?.localisation || '—' }}</span>
              </div>
            </div>
            <div class="champ-affichage" style="margin-bottom: var(--espace-m)">
              <label>Membre depuis</label>
              <span>{{ profil?.dateInscription ? formaterDate(profil.dateInscription) : '—' }}</span>
            </div>
            <button class="btn btn-primaire" @click="ouvrirEdition">✏️ Modifier profil</button>
          </template>

          <template v-else>
            <div v-if="erreurEdition" class="alerte alerte-erreur">{{ erreurEdition }}</div>
            <div class="grille-2">
              <div class="champ-groupe" :class="{ 'champ-groupe-pleine': estClub }">
                <label>{{ estClub ? 'Nom du club' : 'Nom' }}</label>
                <input v-model="formEdition.nom" type="text" class="champ" />
              </div>
              <div v-if="!estClub" class="champ-groupe">
                <label>Prénom</label>
                <input v-model="formEdition.prenom" type="text" class="champ" />
              </div>
            </div>
            <div class="champ-groupe">
              <label>Localisation</label>
              <input v-model="formEdition.localisation" type="text" class="champ" placeholder="Paris, Lyon..." />
            </div>
            <div style="display:flex; gap: var(--espace-s)">
              <button class="btn btn-primaire" @click="annulerEdition">Enregistrer</button>
              <button class="btn btn-secondaire" @click="annulerEdition">Annuler</button>
            </div>
          </template>
        </section>

        <!-- Sécurité -->
        <section class="carte section-profil">
          <div class="section-titre-icone">
            <span class="titre-icone">🔒</span>
            <h2>Sécurité</h2>
          </div>
          <p class="section-desc">Modifiez votre mot de passe pour sécuriser votre compte</p>
          <button class="btn btn-primaire" disabled>🔑 Changer mot de passe</button>
          <p class="bientot">Fonctionnalité à venir</p>
        </section>

        <!-- Mes équipes -->
        <section class="carte section-profil">
          <div class="section-titre-icone">
            <span class="titre-icone">👥</span>
            <h2>Mes équipes</h2>
          </div>

          <div v-if="mesEquipes.length === 0" class="vide-section">
            Vous n'avez pas encore d'équipe.
          </div>

          <div v-else class="liste-equipes">
            <div v-for="equipe in mesEquipes" :key="equipe.id" class="item-equipe">
              <div class="equipe-info">
                <strong>{{ equipe.nom }}</strong>
                <span>
                  {{ equipe.sport?.nom ?? equipe.sport }}
                  <template v-if="equipe.niveau"> — {{ equipe.niveau?.libelle ?? equipe.niveau }}</template>
                </span>
              </div>
              <RouterLink :to="`/equipes/${equipe.id}`" class="btn btn-secondaire btn-petit">
                Voir équipe
              </RouterLink>
            </div>
          </div>
        </section>

        <!-- Mes matchs -->
        <section class="carte section-profil">
          <div class="section-titre-icone">
            <span class="titre-icone">📅</span>
            <h2>Mes matchs</h2>
          </div>

          <div v-if="mesMatchs.length === 0" class="vide-section">
            Aucun match créé.
          </div>

          <div v-else class="liste-matchs-profil">
            <div
              v-for="match in [...matchsAvenir, ...matchsPasses].slice(0, 6)"
              :key="match.id"
              class="item-match"
              @click="router.push(`/matchs/${match.id}`)"
            >
              <div class="match-info">
                <strong>{{ match.sport?.nom ?? 'Sport' }}</strong>
                <span>{{ formaterDate(match.dateMatch) }}</span>
              </div>
              <span
                class="statut-match"
                :class="new Date(match.dateMatch) >= new Date() ? 'avenir' : 'passe'"
              >
                {{ new Date(match.dateMatch) >= new Date() ? 'À venir' : 'Terminé' }}
              </span>
            </div>
          </div>
        </section>
      </div>
    </template>
  </div>
</template>

<style scoped>
.page-profil {
  padding-top: var(--espace-xl);
  padding-bottom: var(--espace-xxl);
}

.profil-entete {
  margin-bottom: var(--espace-xl);
}

.profil-entete h1 {
  font-size: 1.6rem;
  font-weight: 700;
}

.sous-titre {
  color: var(--couleur-texte-discret);
  font-size: 0.9rem;
}

.profil-grille {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--espace-l);
}

.section-profil {
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

.titre-icone {
  font-size: 1.2rem;
}

.section-titre-icone h2 {
  font-size: 1rem;
  font-weight: 700;
}

.champ-affichage {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  margin-bottom: var(--espace-s);
}

.champ-affichage label {
  font-size: 0.75rem;
  color: var(--couleur-texte-discret);
  text-transform: uppercase;
  font-weight: 600;
  letter-spacing: 0.5px;
}

.champ-affichage span {
  font-size: 0.95rem;
  padding: 0.4rem 0.7rem;
  background: var(--couleur-fond);
  border-radius: var(--rayon-bouton);
  color: var(--couleur-texte);
}

.section-desc {
  font-size: 0.88rem;
  color: var(--couleur-texte-discret);
  margin-bottom: var(--espace-m);
}

.bientot {
  font-size: 0.78rem;
  color: var(--couleur-texte-discret);
  text-align: center;
  margin-top: var(--espace-s);
}

button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.vide-section {
  color: var(--couleur-texte-discret);
  font-size: 0.88rem;
  text-align: center;
  padding: var(--espace-l);
}

.liste-equipes {
  display: flex;
  flex-direction: column;
  gap: var(--espace-s);
}

.item-equipe {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--espace-s) var(--espace-m);
  background: var(--couleur-fond);
  border-radius: var(--rayon-bouton);
}

.equipe-info {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.equipe-info strong {
  font-size: 0.9rem;
}

.equipe-info span {
  font-size: 0.8rem;
  color: var(--couleur-texte-discret);
}

.btn-petit {
  padding: 0.3rem 0.8rem;
  font-size: 0.82rem;
}

.liste-matchs-profil {
  display: flex;
  flex-direction: column;
  gap: var(--espace-xs);
}

.item-match {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--espace-s) var(--espace-m);
  border-radius: var(--rayon-bouton);
  cursor: pointer;
  transition: background 0.15s;
}

.item-match:hover {
  background: var(--couleur-fond);
}

.match-info {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.match-info strong {
  font-size: 0.9rem;
}

.match-info span {
  font-size: 0.8rem;
  color: var(--couleur-texte-discret);
}

.statut-match {
  font-size: 0.78rem;
  font-weight: 600;
}

.statut-match.avenir {
  color: var(--couleur-confirme);
}

.statut-match.passe {
  color: var(--couleur-texte-discret);
}

@media (max-width: 768px) {
  .profil-grille {
    grid-template-columns: 1fr;
  }
}
</style>
