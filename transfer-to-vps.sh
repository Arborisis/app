#!/bin/bash
# =============================================================================
# Arborisis — Script de transfert vers le VPS
# =============================================================================
# À exécuter sur votre machine locale
# Usage: ./transfer-to-vps.sh
# =============================================================================

set -e

# Configuration
VPS_IP="213.199.53.8"
VPS_USER="root"
REMOTE_DIR="/opt/arborisis"

echo "🚀 Transfert d'Arborisis vers le VPS ${VPS_IP}..."

# Vérifier que rsync est installé
if ! command -v rsync &> /dev/null; then
    echo "❌ rsync n'est pas installé. Installez-le avec :"
    echo "   macOS: brew install rsync"
    echo "   Ubuntu/Debian: sudo apt-get install rsync"
    exit 1
fi

# Vérifier que les fichiers de production existent
REQUIRED_FILES=("Dockerfile.prod" "docker-compose.yml" ".env.prod" "deploy-vps.sh")
for file in "${REQUIRED_FILES[@]}"; do
    if [ ! -f "$file" ]; then
        echo "❌ Fichier requis manquant : $file"
        exit 1
    fi
done

# Créer le répertoire distant
echo "📁 Création du répertoire distant..."
ssh ${VPS_USER}@${VPS_IP} "mkdir -p ${REMOTE_DIR}"

# Transférer les fichiers (exclure les fichiers inutiles)
echo "📦 Transfert des fichiers..."
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
    --exclude='*.log' \
    ./ ${VPS_USER}@${VPS_IP}:${REMOTE_DIR}/

echo ""
echo "✅ Transfert terminé !"
echo ""
echo "Prochaines étapes sur le VPS :"
echo "  1. ssh ${VPS_USER}@${VPS_IP}"
echo "  2. cd ${REMOTE_DIR}"
echo "  3. cp .env.prod .env"
echo "  4. nano .env  # Remplir les variables"
echo "  5. chmod +x deploy-vps.sh install-nginx-vps.sh"
echo "  6. ./install-nginx-vps.sh  # Si Nginx pas encore installé"
echo "  7. ./deploy-vps.sh"
echo ""
echo "Ou exécutez automatiquement :"
echo "  ssh ${VPS_USER}@${VPS_IP} 'cd ${REMOTE_DIR} && cp .env.prod .env && chmod +x deploy-vps.sh && ./deploy-vps.sh'"
