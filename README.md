# Arborisis

<p align="center">
  <img src="https://raw.githubusercontent.com/Arborisis/.github/main/profile/logo.svg" alt="Arborisis Logo" width="200" />
</p>

<p align="center">
  <em>Plateforme sociale premium de field recording dediee aux sons de la nature.</em>
</p>

<p align="center">
  <a href="https://arborisis.com"><img src="https://img.shields.io/badge/Website-arborisis.com-4CAF50?style=flat-square&logo=safari&logoColor=white" alt="Website" /></a>
  <a href="https://github.com/Arborisis/app/actions"><img src="https://img.shields.io/github/actions/workflow/status/Arborisis/app/ci.yml?branch=main&style=flat-square&label=CI" alt="CI" /></a>
  <a href="https://github.com/Arborisis/app/blob/main/LICENSE"><img src="https://img.shields.io/github/license/Arborisis/app?style=flat-square" alt="License" /></a>
</p>

---

## Overview

Arborisis est une plateforme sociale et educative dediee au **field recording** et a la preservation des **sons de la nature**. L'application permet aux utilisateurs de partager des enregistrements audio de lieux naturels, de decouvrir de nouveaux environnements sonores, et de contribuer a la preservation acoustique de la biodiversite.

### Fonctionnalites principales

- **Upload et partage audio** : Enregistrements haute qualite avec metadonnees GPS approximees (privacy-first)
- **Carte interactive** : Decouverte geographique des lieux d'ecoute avec filtrage par environnement
- **Gamification** : Points de visite, quetes, achievements et medals pour encourager l'exploration
- **Radio nature** : Diffusion en continu basee sur les enregistrements de la communaute
- **Analyse audio** : Classification automatique des especes via BirdNET, visualisations spectrogrammes
- **ECHO** : Systeme de dons entre utilisateurs pour soutenir les createurs
- **Discord Bot** : Integration communautaire et notifications

## Stack technique

### Backend
- **Laravel 12.x** - Framework PHP
- **PHP 8.4+** avec OPcache et JIT
- **PostgreSQL 16+** avec PostGIS pour la geolocalisation
- **Redis** - Cache, sessions, queues
- **Laravel Cashier** - Integration Stripe pour les paiements
- **Filament** - Panel d'administration

### Frontend
- **Vue 3** avec Composition API
- **Inertia.js** - SPA sans API REST
- **Tailwind CSS** - Styling utilitaire
- **Vite** - Build tool
- **Pinia** - State management
- **Wavesurfer.js** - Visualisation audio
- **Leaflet** - Carte interactive

### Audio & Data
- **FFmpeg** - Traitement audio
- **BirdNET** - Classification des especes
- **librosa** - Analyse spectrale
- **OpenSearch** - Recherche avancee

## Installation

### Prerequis

- Docker et Docker Compose
- OU PHP 8.4+, Node.js 22, PostgreSQL 16, Redis

### Docker (recommande)

```bash
git clone https://github.com/Arborisis/app.git
cd app
cp .env.example .env
# Editer .env avec vos variables
docker compose -f docker-compose.yml up -d
```

### Manuel

```bash
git clone https://github.com/Arborisis/app.git
cd app

# PHP dependencies
composer install

# Node dependencies
npm ci
npm run build

# Configuration
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate --seed

# Queues
php artisan queue:work

# Serveur de developpement
php artisan serve
npm run dev
```

## Tests

```bash
# Backend tests
php artisan test

# Frontend lint
npm run lint

# TypeScript check
npm run typecheck
```

## Architecture

```
app/
├── app/
│   ├── Console/Commands/    # Commandes artisan
│   ├── Enums/               # Enumerations metier
│   ├── Http/
│   │   ├── Controllers/     # Controllers (API + Web)
│   │   ├── Middleware/      # Middleware custom
│   │   ├── Requests/        # Form Requests
│   │   └── Resources/       # API Resources
│   ├── Jobs/                # Background jobs
│   ├── Listeners/           # Event listeners
│   ├── Models/              # Eloquent models
│   ├── Policies/            # Authorization policies
│   └── Services/            # Business logic
├── database/
│   ├── factories/           # Model factories
│   ├── migrations/          # Migrations
│   └── seeders/             # Database seeders
├── resources/
│   ├── js/                  # Vue components
│   └── views/               # Blade templates
└── tests/
    ├── Feature/             # Feature tests
    └── Unit/                # Unit tests
```

## Privacy & Securite

- **GPS exact jamais expose publiquement** - Seules les coordonnees approximees sont partagees
- **Floutage automatique** selon la sensibilite ecologique du lieu
- **Validation stricte** des uploads audio (MIME, extension, taille, duree)
- **Rate limiting** et anti-cheat sur la gamification
- **RGPD conforme** - Donnees exportables et supprimables

## Contribution

Les contributions sont les bienvenues ! Veuillez consulter [CONTRIBUTING.md](CONTRIBUTING.md) pour les guidelines.

## Repositories lies

- [discord-bot](https://github.com/Arborisis/discord-bot) - Bot Discord
- [audio-services](https://github.com/Arborisis/audio-services) - Services d'analyse audio
- [workers](https://github.com/Arborisis/workers) - Workers Cloudflare
- [infrastructure](https://github.com/Arborisis/infrastructure) - Infrastructure et deploiement

## License

[MIT License](LICENSE)
