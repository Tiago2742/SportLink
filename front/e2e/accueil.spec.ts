import { test, expect } from '@playwright/test'

// L'accueil (/) requiert l'authentification → le visiteur est redirigé vers /connexion.
// Ces tests valident le parcours d'un visiteur non connecté.
test.describe("Page d'accueil — visiteur non authentifié", () => {
  test('redirige vers la connexion et affiche le formulaire complet', async ({ page }) => {
    await page.goto('/')

    await expect(page).toHaveURL(/connexion/)
    await expect(page.getByRole('heading', { name: 'Bon retour' })).toBeVisible()
    await expect(page.getByLabel('Email')).toBeVisible()
    await expect(page.getByLabel('Mot de passe')).toBeVisible()
    await expect(page.getByRole('button', { name: 'Se connecter' })).toBeVisible()
  })

  test("propose un lien vers l'inscription qui fonctionne", async ({ page }) => {
    await page.goto('/connexion')

    const lienInscription = page.getByRole('link', { name: 'Créer un compte' })
    await expect(lienInscription).toBeVisible()
    await lienInscription.click()
    await expect(page).toHaveURL(/inscription/)
  })
})
