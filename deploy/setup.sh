#!/bin/bash
# setup.sh — à exécuter UNE SEULE FOIS sur le serveur en root
# Usage: sudo bash deploy/setup.sh

set -e

APP_DIR="/var/www/nexus-bet"
REPO="https://github.com/PierreKROB/symfony_paris_sportif.git"
SERVICE="nexus-bet"
RUNNER_USER="github-runner"   # utilisateur qui fait tourner le runner GitHub Actions

echo "==> Création du dossier app"
mkdir -p "$APP_DIR"
chown www-data:www-data "$APP_DIR"

echo "==> Clone du repo"
git clone "$REPO" "$APP_DIR"
chown -R www-data:www-data "$APP_DIR"

echo "==> Installation du service systemd"
cp "$APP_DIR/deploy/nexus-bet.service" /etc/systemd/system/
systemctl daemon-reload
systemctl enable "$SERVICE"
systemctl start "$SERVICE"

echo "==> Sudoers : autoriser le runner à restart le service sans mot de passe"
echo "$RUNNER_USER ALL=(ALL) NOPASSWD: /bin/systemctl restart $SERVICE" \
    > /etc/sudoers.d/nexus-bet
chmod 440 /etc/sudoers.d/nexus-bet

echo ""
echo "==> Done. Vérifie le statut :"
echo "    systemctl status $SERVICE"
echo ""
echo "==> Maintenant crée /var/www/nexus-bet/.env.local avec les vraies credentials"
echo "    et lance le runner GitHub Actions."
