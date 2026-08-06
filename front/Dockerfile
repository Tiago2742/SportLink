# ── Stage builder : compile le front Vue ──────────────────────────────────
FROM node:22-alpine AS builder

WORKDIR /app

# Installer les dépendances en premier (layer Docker mis en cache si package-lock.json inchangé)
COPY package.json package-lock.json ./
RUN npm ci

# Copier les sources et compiler
COPY . .
RUN npm run build-only
# Résultat : /app/dist/

# ── Stage production : nginx sert le dist/ statique ───────────────────────
# nginx est le point d'entrée unique : sert les assets Vue et proxifie /api
# vers le conteneur PHP (voir docker/nginx.conf).
FROM nginx:alpine AS production

COPY --from=builder /app/dist /usr/share/nginx/html
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
