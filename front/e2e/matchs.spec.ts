import { test, expect } from '@playwright/test'
import { seConnecterCommeJoueur } from './helpers/auth'

test.describe('Tableau de bord et recherche de matchs (connecté)', () => {
  test.beforeEach(async ({ page }) => {
    await seConnecterCommeJoueur(page)
  })

  test('le tableau de bord affiche les raccourcis principaux', async ({ page }) => {
    await expect(page.getByRole('heading', { name: /Bonjour/ })).toBeVisible()
    // Les deux raccourcis cœur métier sont présents (nav + cartes → .first() évite l'ambiguïté)
    await expect(page.getByRole('link', { name: 'Trouver un match' }).first()).toBeVisible()
    await expect(page.getByRole('link', { name: 'Créer un match' }).first()).toBeVisible()
  })

  test('la page de recherche affiche le titre et le panneau de filtres', async ({ page }) => {
    await page.goto('/rechercher')

    await expect(page.getByRole('heading', { name: 'Trouver un match' })).toBeVisible()
    // Le panneau de filtres doit être visible (sidebar "Filtres")
    await expect(page.getByRole('heading', { name: 'Filtres' })).toBeVisible()
  })

  test('le lien "Trouver un match" du tableau de bord navigue vers /rechercher', async ({ page }) => {
    await page.getByRole('link', { name: 'Trouver un match' }).first().click()

    await expect(page).toHaveURL(/rechercher/)
    await expect(page.getByRole('heading', { name: 'Trouver un match' })).toBeVisible()
  })
})
