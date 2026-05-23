#!/bin/bash
# =============================================================================
# Arborisis — Installation complète Nginx + SSL (Host VPS)
# =============================================================================
# À exécuter sur le VPS (pas dans Docker)
# =============================================================================

set -e

echo "🌐 Installation de Nginx + SSL sur le VPS..."

# Mettre à jour
apt-get update

# Installer Nginx et Certbot
apt-get install -y nginx certbot python3-certbot-nginx

# Démarrer Nginx
systemctl start nginx
systemctl enable nginx

# Créer le répertoire pour les challenges Certbot
mkdir -p /var/www/certbot

# Copier la config
cp vps-nginx.conf /etc/nginx/sites-available/arborisis

# Activer le site
ln -sf /etc/nginx/sites-available/arborisis /etc/nginx/sites-enabled/

# Supprimer le site par défaut
rm -f /etc/nginx/sites-enabled/default

# Tester la config (sans SSL au début)
nginx -t

# Redémarrer Nginx
systemctl reload nginx

echo "✅ Nginx installé et configuré !"
echo ""
echo "Prochaines étapes :"
echo "  1. Configurez votre DNS :"
echo "     arborisis.com     A     213.199.53.8"
echo "     www.arborisis.com CNAME arborisis.com"
echo ""
echo "  2. Attendez la propagation DNS (2-48h)"
echo ""
echo "  3. Installez le SSL :"
echo "     ./setup-ssl.sh"
echo ""
echo "  4. Redémarrez Nginx après le SSL :"
echo "     systemctl reload nginx"
