import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/apercu-design',
      name: 'apercu-design',
      component: () => import('@/views/ApercuFondationsView.vue'),
    },
    {
      path: '/connexion',
      name: 'connexion',
      component: () => import('@/views/ConnexionView.vue'),
    },
    {
      path: '/inscription',
      name: 'inscription',
      component: () => import('@/views/InscriptionView.vue'),
    },
    {
      path: '/',
      name: 'accueil',
      component: () => import('@/views/AccueilView.vue'),
      meta: { requiertAuth: true },
    },
    {
      path: '/mes-matchs',
      name: 'mes-matchs',
      component: () => import('@/views/MesMatchsView.vue'),
      meta: { requiertAuth: true },
    },
    {
      path: '/rechercher',
      name: 'rechercher',
      component: () => import('@/views/RechercheView.vue'),
      meta: { requiertAuth: true },
    },
    {
      path: '/creer-match',
      name: 'creer-match',
      component: () => import('@/views/CreerMatchView.vue'),
      meta: { requiertAuth: true },
    },
    {
      path: '/profil',
      name: 'profil',
      component: () => import('@/views/ProfilView.vue'),
      meta: { requiertAuth: true },
    },
    {
      path: '/mes-equipes',
      name: 'mes-equipes',
      component: () => import('@/views/MesEquipesView.vue'),
      meta: { requiertAuth: true, requiertClub: true },
    },
    {
      path: '/equipes/creer',
      name: 'creer-equipe',
      component: () => import('@/views/CreerEquipeView.vue'),
      meta: { requiertAuth: true, requiertClub: true },
    },
    {
      path: '/mes-equipes-joueur',
      name: 'mes-equipes-joueur',
      component: () => import('@/views/MesEquipesJoueurView.vue'),
      meta: { requiertAuth: true, requiertJoueur: true },
    },
    {
      path: '/invitations-equipes',
      redirect: (to) => ({
        path: '/mes-equipes-joueur',
        query: { ...to.query, onglet: 'invitations' },
      }),
    },
    {
      path: '/equipes/:id',
      name: 'equipe',
      component: () => import('@/views/EquipeView.vue'),
      meta: { requiertAuth: true },
    },
    {
      path: '/matchs/:id',
      name: 'match-detail',
      component: () => import('@/views/DetailMatchView.vue'),
      meta: { requiertAuth: true },
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/',
    },
  ],
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.requiertAuth && !auth.estConnecte) {
    return { name: 'connexion' }
  }
  if (to.meta.requiertClub && auth.utilisateur?.type !== 'club') {
    return { name: 'accueil' }
  }
  if (to.meta.requiertJoueur && auth.utilisateur?.type !== 'joueur') {
    return { name: 'accueil' }
  }
  if ((to.name === 'connexion' || to.name === 'inscription') && auth.estConnecte) {
    return { name: 'accueil' }
  }
})

export default router
