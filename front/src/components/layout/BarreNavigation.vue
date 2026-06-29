<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import LogoSportLink from '@/components/brand/LogoSportLink.vue'
import BadgeCompteur from '@/components/ui/BadgeCompteur.vue'
import { useEspaceEquipesJoueur } from '@/composables/useEspaceEquipesJoueur'
import { nomAffichage } from '@/utils/nomAffichage'
import {
  CalendarDays,
  CirclePlus,
  Home,
  LogOut,
  Search,
  User,
  Users,
} from 'lucide-vue-next'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const menuOuvert = ref(false)
const menuProfilOuvert = ref(false)
const profilRef = ref<HTMLElement | null>(null)
const { nbInvitations, rafraichirCompteurInvitations } = useEspaceEquipesJoueur()

const estAccueil = computed(() => route.path === '/')
const estMesMatchs = computed(
  () => route.path.startsWith('/mes-matchs') || route.path.startsWith('/matchs/'),
)
const estMesEquipesClub = computed(
  () =>
    route.path.startsWith('/mes-equipes') ||
    route.path.startsWith('/equipes/') ||
    route.path === '/creer-equipe',
)
const estMesEquipesJoueur = computed(
  () =>
    route.path.startsWith('/mes-equipes-joueur') ||
    route.path.startsWith('/equipes/') ||
    route.path === '/invitations-equipes',
)
const estRecherche = computed(() => route.path.startsWith('/rechercher'))
const estCreerMatch = computed(() => route.path === '/creer-match')
const estProfil = computed(() => route.path === '/profil')

const libelleUtilisateur = computed(() => nomAffichage(auth.utilisateur))

onMounted(() => {
  if (auth.estConnecte && auth.utilisateur?.type === 'joueur') {
    rafraichirCompteurInvitations()
  }
  document.addEventListener('click', fermerMenuProfilSiExterieur)
})

onUnmounted(() => {
  document.removeEventListener('click', fermerMenuProfilSiExterieur)
})

watch(
  () => auth.estConnecte && auth.utilisateur?.type === 'joueur',
  (ok) => {
    if (ok) rafraichirCompteurInvitations()
    else nbInvitations.value = 0
  },
)

watch(() => route.path, () => {
  menuOuvert.value = false
  menuProfilOuvert.value = false
})

function fermerMenuProfilSiExterieur(e: MouseEvent) {
  if (!menuProfilOuvert.value || !profilRef.value) return
  if (!profilRef.value.contains(e.target as Node)) {
    menuProfilOuvert.value = false
  }
}

function basculerMenuProfil() {
  menuProfilOuvert.value = !menuProfilOuvert.value
}

function fermerMenus() {
  menuOuvert.value = false
  menuProfilOuvert.value = false
}

function deconnecter() {
  fermerMenus()
  auth.seDeconnecter()
  router.push('/connexion')
}
</script>

<template>
  <nav class="barre-nav" aria-label="Navigation principale">
    <div class="nav-interieur">
      <RouterLink to="/" class="nav-logo" aria-label="SportLink — accueil" @click="fermerMenus">
        <LogoSportLink variant="complet" taille="nav" />
      </RouterLink>

      <button
        type="button"
        class="btn-menu-mobile"
        :aria-expanded="menuOuvert"
        aria-controls="nav-liens-principaux"
        @click="menuOuvert = !menuOuvert"
      >
        <span></span>
        <span></span>
        <span></span>
        <span class="sr-only">Menu</span>
      </button>

      <div id="nav-liens-principaux" class="nav-liens" :class="{ ouvert: menuOuvert }">
        <div class="nav-liens-principaux">
          <RouterLink
            to="/"
            class="nav-lien"
            :class="{ 'nav-lien--actif': estAccueil }"
            @click="fermerMenus"
          >
            <Home :size="18" stroke-width="2.25" aria-hidden="true" />
            <span>Accueil</span>
          </RouterLink>

          <RouterLink
            to="/mes-matchs"
            class="nav-lien"
            :class="{ 'nav-lien--actif': estMesMatchs }"
            @click="fermerMenus"
          >
            <CalendarDays :size="18" stroke-width="2.25" aria-hidden="true" />
            <span>Mes matchs</span>
          </RouterLink>

          <RouterLink
            v-if="auth.utilisateur?.type === 'club'"
            to="/mes-equipes"
            class="nav-lien"
            :class="{ 'nav-lien--actif': estMesEquipesClub }"
            @click="fermerMenus"
          >
            <Users :size="18" stroke-width="2.25" aria-hidden="true" />
            <span>Mes équipes</span>
          </RouterLink>

          <RouterLink
            v-if="auth.utilisateur?.type === 'joueur'"
            to="/mes-equipes-joueur"
            class="nav-lien"
            :class="{ 'nav-lien--actif': estMesEquipesJoueur }"
            @click="fermerMenus"
          >
            <Users :size="18" stroke-width="2.25" aria-hidden="true" />
            <span>Mes équipes</span>
            <BadgeCompteur :nombre="nbInvitations" />
          </RouterLink>

          <RouterLink
            to="/rechercher"
            class="nav-lien"
            :class="{ 'nav-lien--actif': estRecherche }"
            @click="fermerMenus"
          >
            <Search :size="18" stroke-width="2.25" aria-hidden="true" />
            <span>Rechercher</span>
          </RouterLink>

          <RouterLink
            to="/creer-match"
            class="nav-lien nav-lien--accent"
            :class="{ 'nav-lien--actif': estCreerMatch }"
            @click="fermerMenus"
          >
            <CirclePlus :size="18" stroke-width="2.25" aria-hidden="true" />
            <span>Créer un match</span>
          </RouterLink>
        </div>

        <div ref="profilRef" class="nav-profil">
          <button
            type="button"
            class="nav-profil-bouton"
            :class="{ 'nav-profil-bouton--actif': estProfil || menuProfilOuvert }"
            :aria-expanded="menuProfilOuvert"
            aria-haspopup="menu"
            :aria-label="`Menu profil — ${libelleUtilisateur}`"
            @click.stop="basculerMenuProfil"
          >
            <span class="nav-profil-icone" aria-hidden="true">
              <User :size="18" stroke-width="2.5" />
            </span>
            <span class="nav-profil-libelle">Profil</span>
          </button>

          <div v-if="menuProfilOuvert" class="menu-profil" role="menu">
            <p class="menu-profil-nom">{{ libelleUtilisateur }}</p>
            <RouterLink to="/profil" class="menu-profil-lien" role="menuitem" @click="fermerMenus">
              <User :size="16" stroke-width="2.25" aria-hidden="true" />
              Mon profil
            </RouterLink>
            <button type="button" class="menu-profil-lien menu-profil-deconnexion" role="menuitem" @click="deconnecter">
              <LogOut :size="16" stroke-width="2.25" aria-hidden="true" />
              Déconnexion
            </button>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>

<style scoped>
.barre-nav {
  width: 100%;
  align-self: stretch;
  flex-shrink: 0;
  background: var(--couleur-fond-blanc);
  border-bottom: 1px solid var(--couleur-bordure);
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: var(--ombre-carte);
}

.nav-interieur {
  width: 100%;
  padding: 0 var(--espace-xl);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--espace-m);
  min-height: 72px;
  box-sizing: border-box;
}

.nav-logo {
  display: flex;
  align-items: center;
  text-decoration: none;
  flex-shrink: 0;
  padding: 0.35rem 0;
  border-radius: var(--rayon-bouton);
  transition: opacity var(--transition-rapide);
}

.nav-logo:hover {
  opacity: 0.9;
}

.nav-liens {
  display: flex;
  align-items: center;
  gap: var(--espace-m);
  flex: 1;
  justify-content: flex-end;
  min-width: 0;
}

.nav-liens-principaux {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.nav-lien {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  text-decoration: none;
  color: var(--couleur-texte);
  font-size: 0.875rem;
  font-weight: 600;
  padding: 0.5rem 0.85rem;
  border-radius: var(--rayon-bouton);
  border: 1px solid transparent;
  white-space: nowrap;
  transition:
    color var(--transition-rapide),
    background var(--transition-interaction),
    border-color var(--transition-rapide),
    transform var(--transition-interaction),
    box-shadow var(--transition-interaction);
}

.nav-lien:hover {
  color: var(--couleur-primaire);
  background: var(--couleur-primaire-tres-claire);
  transform: translateY(-1px);
}

.nav-lien--actif {
  color: var(--couleur-titre);
  background: var(--couleur-primaire-tres-claire);
  border-color: rgba(46, 125, 50, 0.22);
  box-shadow: inset 0 0 0 1px rgba(76, 175, 80, 0.12);
}

.nav-lien--actif:hover {
  color: var(--couleur-titre);
  transform: none;
}

.nav-lien--accent.nav-lien--actif {
  background: var(--couleur-accent-fond);
  border-color: rgba(154, 230, 0, 0.45);
  color: var(--couleur-accent-texte);
}

.nav-lien svg {
  flex-shrink: 0;
  opacity: 0.88;
}

.nav-lien--actif svg {
  color: var(--couleur-primaire);
  opacity: 1;
}

.nav-profil {
  position: relative;
  flex-shrink: 0;
  margin-left: 0.25rem;
}

.nav-profil-bouton {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  padding: 4px 6px 4px 4px;
  border: 2px solid transparent;
  border-radius: 999px;
  background: var(--couleur-primaire-tres-claire);
  cursor: pointer;
  transition:
    border-color var(--transition-rapide),
    box-shadow var(--transition-interaction),
    transform var(--transition-interaction),
    background var(--transition-rapide);
}

.nav-profil-icone {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--degrade-primaire);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: var(--ombre-bouton);
  transition:
    transform var(--transition-interaction),
    box-shadow var(--transition-interaction);
}

.nav-profil-libelle {
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--couleur-titre);
  letter-spacing: 0.01em;
  padding-right: 0.15rem;
}

.nav-profil-bouton:hover {
  border-color: rgba(76, 175, 80, 0.4);
  background: #dff0e1;
  transform: translateY(-1px);
}

.nav-profil-bouton:hover .nav-profil-icone {
  box-shadow: var(--ombre-bouton-survol);
  transform: scale(1.04);
}

.nav-profil-bouton--actif {
  border-color: var(--couleur-primaire-claire);
  background: var(--couleur-primaire-tres-claire);
  box-shadow: var(--ombre-focus);
}

.nav-profil-bouton--actif .nav-profil-icone {
  box-shadow: var(--ombre-bouton-survol);
}

.menu-profil {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  min-width: 220px;
  background: var(--couleur-fond-blanc);
  border: 1px solid var(--couleur-bordure);
  border-radius: var(--rayon-carte);
  box-shadow: var(--ombre-carte-survol);
  padding: 0.45rem;
  z-index: 200;
  animation: menu-profil-entree 0.18s ease-out;
}

@keyframes menu-profil-entree {
  from {
    opacity: 0;
    transform: translateY(-6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.menu-profil-nom {
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--couleur-titre);
  padding: 0.45rem 0.65rem 0.55rem;
  border-bottom: 1px solid var(--couleur-bordure);
  margin-bottom: 0.35rem;
  line-height: 1.3;
}

.menu-profil-lien {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  width: 100%;
  padding: 0.55rem 0.65rem;
  border-radius: var(--rayon-bouton);
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--couleur-texte);
  text-decoration: none;
  border: none;
  background: none;
  cursor: pointer;
  text-align: left;
  transition:
    background var(--transition-rapide),
    color var(--transition-rapide);
}

.menu-profil-lien:hover {
  background: var(--couleur-primaire-tres-claire);
  color: var(--couleur-primaire);
}

.menu-profil-deconnexion:hover {
  background: var(--couleur-refuse-fond);
  color: var(--couleur-refuse);
}

.btn-menu-mobile {
  display: none;
  flex-direction: column;
  gap: 5px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 6px;
  border-radius: var(--rayon-bouton);
  transition: background var(--transition-rapide);
}

.btn-menu-mobile:hover {
  background: var(--couleur-primaire-tres-claire);
}

.btn-menu-mobile span:not(.sr-only) {
  display: block;
  width: 22px;
  height: 2px;
  background: var(--couleur-titre);
  border-radius: 2px;
  transition: background var(--transition-rapide);
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

@media (max-width: 1024px) {
  .nav-lien span {
    display: none;
  }

  .nav-lien {
    padding: 0.5rem 0.65rem;
  }

  .nav-lien--accent span {
    display: none;
  }
}

@media (max-width: 768px) {
  .nav-interieur {
    min-height: 68px;
    position: relative;
  }

  .btn-menu-mobile {
    display: flex;
  }

  .nav-liens {
    display: none;
    position: fixed;
    top: 68px;
    left: 0;
    right: 0;
    width: 100%;
    background: var(--couleur-fond-blanc);
    flex-direction: column;
    align-items: stretch;
    padding: var(--espace-m) var(--espace-l) var(--espace-l);
    gap: var(--espace-m);
    border-bottom: 1px solid var(--couleur-bordure);
    box-shadow: var(--ombre-carte-survol);
  }

  .nav-liens.ouvert {
    display: flex;
  }

  .nav-liens-principaux {
    flex-direction: column;
    align-items: stretch;
    gap: 0.35rem;
  }

  .nav-lien {
    width: 100%;
    justify-content: flex-start;
    font-size: 0.95rem;
    padding: 0.65rem 0.85rem;
  }

  .nav-lien span {
    display: inline;
  }

  .nav-profil {
    width: 100%;
    margin-left: 0;
    padding-top: 0.35rem;
    border-top: 1px solid var(--couleur-bordure);
  }

  .nav-profil-bouton {
    width: 100%;
    border-radius: var(--rayon-bouton);
    padding: 0.5rem 0.65rem;
    justify-content: flex-start;
    gap: 0.65rem;
    border-width: 1px;
  }

  .menu-profil {
    position: static;
    margin-top: 0.5rem;
    box-shadow: none;
    animation: none;
  }
}
</style>
