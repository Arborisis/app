#!/bin/bash
# =============================================================================
# Arborisis — Script de déploiement VPS (Production)
# =============================================================================
# Usage: ./deploy-vps.sh
# =============================================================================

set -e

echo "🚀 Démarrage du déploiement Arborisis Production..."

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Vérifier que .env existe
if [ ! -f ".env" ]; then
    echo -e "${RED}❌ Fichier .env manquant !${NC}"
    echo "Copiez .env.prod en .env et remplissez les valeurs :"
    echo "  cp .env.prod .env"
    echo "  nano .env"
    exit 1
fi

# Vérifier les variables critiques
REQUIRED_VARS=("APP_KEY" "DB_PASSWORD" "REDIS_PASSWORD")
for var in "${REQUIRED_VARS[@]}"; do
    if ! grep -q "^${var}=" .env || grep -q "^${var}=\$" .env || grep -q "^${var}=votre-" .env; then
        echo -e "${RED}❌ Variable ${var} non configurée dans .env${NC}"
        exit 1
    fi
done

echo -e "${YELLOW}📦 Construction des images Docker...${NC}"
docker compose -f docker-compose.yml build --no-cache

echo -e "${YELLOW}🗄️  Démarrage des services de base de données...${NC}"
docker compose -f docker-compose.yml up -d db redis

# Attendre que PostgreSQL soit prêt
echo -e "${YELLOW}⏳ Attente de PostgreSQL...${NC}"
sleep 10

echo -e "${YELLOW}🚀 Démarrage de l'application...${NC}"
docker compose -f docker-compose.yml up -d app queue scheduler

echo -e "${YELLOW}🔧 Optimisations Laravel...${NC}"
docker compose -f docker-compose.yml exec -T app php artisan optimize:clear || true
docker compose -f docker-compose.yml exec -T app php artisan optimize || true
docker compose -f docker-compose.yml exec -T app php artisan storage:link || true

echo -e "${GREEN}✅ Déploiement terminé !${NC}"
echo ""
echo -e "${GREEN}🌐 Application accessible sur : http://$(hostname -I | awk '{print $1}'):8080${NC}"
echo ""
echo "Commandes utiles :"
echo "  docker compose logs -f app     # Logs applicatifs"
echo "  docker compose logs -f queue   # Logs queue worker"
echo "  docker compose ps              # Statut des services"
echo "  docker compose down            # Arrêter tous les services"
