import { test, expect } from '@playwright/test'
import { JOUEUR_EMAIL, E2E_PASSWORD } from './helpers/credentials'

test.describe('Authentification', () => {
  test('connexion avec identifiants valides redirige vers le tableau de bord', async ({ page }) => {
    await page.goto('/connexion')
    await page.getByLabel('Email').fill(JOUEUR_EMAIL)
    await page.getByLabel('Mot de passe').fill(E2E_PASSWORD)
    await page.getByRole('button', { name: 'Se connecter' }).click()

    await expect(page).toHaveURL('/')
    // Le h1 "Bonjour, Alex" confirme que la session est ouverte avec le bon compte
    await expect(page.getByRole('heading', { name: /Bonjour/ })).toBeVisible()
  })

  test('connexion avec mauvais mot de passe affiche un message d\'erreur', async ({ page }) => {
    await page.goto('/connexion')
    await page.getByLabel('Email').fill(JOUEUR_EMAIL)
    await page.getByLabel('Mot de passe').fill('mauvais-mot-de-passe')
    await page.getByRole('button', { name: 'Se connecter' }).click()

    await expect(page.getByText('Email ou mot de passe incorrect.')).toBeVisible()
    await expect(page).toHaveURL(/connexion/)
  })
})
