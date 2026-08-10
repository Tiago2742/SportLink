import { defineConfig, devices } from '@playwright/test'

// En CI (BASE_URL=http://localhost:4173) : le job build le front puis lance npm run preview.
// En local (Docker up) : Playwright réutilise le Vite dev server sur :5173 ;
//   si Docker n'est pas démarré, il lance npm run dev (mais l'API ne répondra pas).
const baseURL = process.env.BASE_URL ?? 'http://localhost:5173'
const isCI = !!process.env.CI

export default defineConfig({
  testDir: './e2e',
  fullyParallel: false,
  forbidOnly: isCI,
  retries: isCI ? 2 : 0,
  workers: isCI ? 1 : undefined,
  reporter: [['html', { open: 'never' }], ['list']],

  use: {
    baseURL,
    trace: 'on-first-retry',
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],

  webServer: {
    // CI : sert le dist/ pré-buildé. Local : réutilise Docker ou démarre Vite.
    command: isCI ? 'npm run preview' : 'npm run dev',
    url: baseURL,
    reuseExistingServer: !isCI,
    timeout: 30_000,
  },
})
