#!/bin/bash
# =============================================================================
# Arborisis — Script d'installation SSL (Certbot)
# =============================================================================
# À exécuter sur le VPS APRÈS avoir configuré le DNS
# =============================================================================

set -e

DOMAIN="arborisis.com"
EMAIL="contact@arborisis.com"  # Modifiez si nécessaire

echo "🔒 Configuration SSL pour ${DOMAIN}..."

# Installer Certbot
apt-get update
apt-get install -y certbot python3-certbot-nginx

# Créer le répertoire pour les challenges
mkdir -p /var/www/certbot

# Obtenir le certificat (mode standalone temporaire)
echo "📜 Obtention du certificat SSL..."
certbot certonly --standalone -d ${DOMAIN} -d www.${DOMAIN} --agree-tos --no-eff-email -m ${EMAIL} || {
    echo "❌ Échec de l'obtention du certificat"
    echo "Vérifiez que :"
    echo "  1. Le DNS pointe bien vers ce serveur (213.199.53.8)"
    echo "  2. Le port 80 est ouvert dans le firewall"
    echo "  3. Le domaine est correctement propagé"
    exit 1
}

# Vérifier que le certificat existe
if [ ! -f "/etc/letsencrypt/live/${DOMAIN}/fullchain.pem" ]; then
    echo "❌ Certificat non trouvé !"
    exit 1
fi

echo "✅ Certificat SSL installé !"

# Renouvellement automatique
echo "🔄 Configuration du renouvellement automatique..."
(crontab -l 2>/dev/null || true; echo "0 3 * * * certbot renew --quiet --nginx") | crontab -

echo ""
echo "✅ SSL configuré avec succès !"
echo ""
echo "Votre site est maintenant accessible en HTTPS :"
echo "  https://${DOMAIN}"
echo "  https://www.${DOMAIN}"
echo ""
echo "Le renouvellement automatique est configuré (tous les jours à 3h)."
