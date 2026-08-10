import type { Page } from '@playwright/test'
import { expect } from '@playwright/test'
import { JOUEUR_EMAIL, E2E_PASSWORD } from './credentials'

export async function seConnecterCommeJoueur(page: Page): Promise<void> {
  await page.goto('/connexion')
  await page.getByLabel('Email').fill(JOUEUR_EMAIL)
  await page.getByLabel('Mot de passe').fill(E2E_PASSWORD)
  await page.getByRole('button', { name: 'Se connecter' }).click()
  await expect(page).toHaveURL('/')
}
