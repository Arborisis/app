# 🚀 Déploiement Production — Arborisis VPS (arborisis.com)

## Configuration DNS requise

Avant de commencer, configurez ces enregistrements DNS chez votre registrar :

```
Type    Nom                Valeur               TTL
A       arborisis.com      213.199.53.8         3600
CNAME   www.arborisis.com  arborisis.com        3600
```

Attendez la propagation DNS (vérifiez avec : `dig arborisis.com +short`)

## Fichiers créés

| Fichier | Description |
|---------|-------------|
| `Dockerfile.prod` | Image Docker production (PHP-FPM + Nginx + Supervisor) |
| `docker-compose.yml` | Orchestration complète (app + PostgreSQL + Redis + Queue + Scheduler) |
| `.env.prod` | Template des variables d'environnement production |
| `deploy-vps.sh` | Script de déploiement automatique |
| `transfer-to-vps.sh` | Transfert du code vers le VPS |
| `install-nginx-vps.sh` | Installation Nginx reverse proxy |
| `vps-nginx.conf` | Configuration Nginx (HTTP + HTTPS) |
| `setup-ssl.sh` | Installation automatique du certificat SSL (Certbot) |
| `DEPLOYMENT_VPS.md` | Ce fichier |

## Déploiement rapide (une commande)

```bash
# Sur votre machine locale
rsync -avz --exclude='node_modules' --exclude='vendor' \
  ./app/ root@213.199.53.8:/opt/arborisis/
```

```bash
# Sur le VPS
ssh root@213.199.53.8
cd /opt/arborisis
cp .env.prod .env
nano .env  # Remplir APP_KEY, DB_PASSWORD, REDIS_PASSWORD
chmod +x *.sh
./install-nginx-vps.sh   # Installe Nginx
./deploy-vps.sh          # Build et démarre l'app
./setup-ssl.sh           # Installe le SSL (après propagation DNS)
```

## Déploiement détaillé

### 1. Transférer le code

```bash
# Depuis votre machine locale
rsync -avz --progress \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='.git' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='bootstrap/cache/*' \
    --exclude='public/build' \
    --exclude='public/hot' \
    --exclude='.env' \
    ./ root@213.199.53.8:/opt/arborisis/
```

### 2. Configurer l'environnement

```bash
# Sur le VPS
cd /opt/arborisis
cp .env.prod .env
nano .env
```

**Variables obligatoires :**
- `APP_KEY` : Générer avec `php artisan key:generate --show` (sur votre machine Laravel)
- `DB_PASSWORD` : Mot de passe PostgreSQL fort
- `REDIS_PASSWORD` : Mot de passe Redis fort
- `APP_URL` : `https://arborisis.com`

### 3. Installer Nginx (Host)

```bash
chmod +x install-nginx-vps.sh
./install-nginx-vps.sh
```

Cela installe Nginx et configure le reverse proxy vers le conteneur Docker (port 8080).

### 4. Déployer l'application

```bash
chmod +x deploy-vps.sh
./deploy-vps.sh
```

Cela build l'image Docker, démarre PostgreSQL, Redis, et l'application.

### 5. Installer le SSL (HTTPS)

⚠️ **Attendez que le DNS soit propagé** (vérifiez avec `dig arborisis.com +short`)

```bash
chmod +x setup-ssl.sh
./setup-ssl.sh
```

Cela obtient un certificat Let's Encrypt et configure le renouvellement automatique.

### 6. Redémarrer Nginx

```bash
systemctl reload nginx
```

Votre site est maintenant accessible sur **https://arborisis.com**

## Commandes utiles

```bash
# Voir les logs
docker compose logs -f app
docker compose logs -f queue
docker compose logs -f scheduler

# Redémarrer un service
docker compose restart app

# Entrer dans le container
docker compose exec app sh

# Exécuter une commande artisan
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan cache:clear

# Vérifier la santé
curl http://localhost:8080/health
curl -I https://arborisis.com

# Arrêper tout
docker compose down

# Mettre à jour (après un git pull)
docker compose build --no-cache
docker compose up -d
```

## Architecture

```
Internet
    │
    ▼
┌─────────────────────────────────────────┐
│           arborisis.com:443             │
│         (HTTPS / Let's Encrypt)         │
├─────────────────────────────────────────┤
│                VPS Host                 │
│  ┌─────────────────────────────────┐    │
│  │     Nginx Reverse Proxy         │    │
│  │   (SSL + Headers + Proxy)       │    │
│  └─────────────┬───────────────────┘    │
│                │                        │
│  ┌─────────────▼───────────────────┐    │
│  │      Docker Compose Network     │    │
│  │  ┌─────────┐ ┌─────┐ ┌──────┐  │    │
│  │  │   App   │ │ DB  │ │ Redis│  │    │
│  │  │(PHP+NG) │ │(PG) │ │      │  │    │
│  │  └─────────┘ └─────┘ └──────┘  │    │
│  │  ┌─────────┐ ┌──────────────┐  │    │
│  │  │  Queue  │ │  Scheduler   │  │    │
│  │  │ Workers │ │    (Cron)    │  │    │
│  │  └─────────┘ └──────────────┘  │    │
│  └─────────────────────────────────┘    │
└─────────────────────────────────────────┘
```

## Vérification post-déploiement

1. **HTTP** : `curl http://arborisis.com` → doit rediriger vers HTTPS
2. **HTTPS** : `curl -I https://arborisis.com` → 200 OK
3. **Healthcheck** : `curl https://arborisis.com/health` → "healthy"
4. **SSL** : `openssl s_client -connect arborisis.com:443` → certificat valide
5. **Headers** : `curl -I https://arborisis.com` → HSTS, X-Frame-Options, etc.

## Dépannage

### Le DNS ne pointe pas vers le VPS
```bash
dig arborisis.com +short
# Doit retourner : 213.199.53.8
```

### Certbot échoue
- Vérifiez que le port 80 est ouvert : `ufw allow 80/tcp`
- Vérifiez le DNS : `dig arborisis.com +short`
- Attendez 24-48h après la modification DNS

### Erreur 502 Bad Gateway
```bash
# Vérifier que l'app Docker tourne
docker compose ps
# Vérifier les logs
docker compose logs app
```

### Erreur de connexion base de données
```bash
# Vérifier PostgreSQL
docker compose exec db pg_isready -U arborisis
# Vérifier les variables d'environnement
docker compose exec app env | grep DB_
```

## Renouvellement SSL

Le renouvellement est automatique (cron quotidien à 3h). Pour forcer :
```bash
certbot renew --force-renewal
systemctl reload nginx
```

## Ports utilisés

| Port | Service | Description |
|------|---------|-------------|
| 80 | Nginx | HTTP (redirection HTTPS) |
| 443 | Nginx | HTTPS |
| 8080 | Docker | Application Laravel (interne) |
| 5432 | Docker | PostgreSQL (interne, localhost uniquement) |
| 6379 | Docker | Redis (interne, localhost uniquement) |

## Contact

En cas de problème : contact@arborisis.com
