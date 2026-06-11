#!/bin/bash
# setup.sh — bootstrap ONE-TIME en root
# Lance AVANT d'enregistrer le runner GitHub Actions

# Sudoers : le runner peut gérer le service sans mot de passe
cat > /etc/sudoers.d/nexus-bet << 'EOF'
github-runner ALL=(ALL) NOPASSWD: \
  /bin/cp * /etc/systemd/system/nexus-bet.service, \
  /bin/systemctl daemon-reload, \
  /bin/systemctl enable nexus-bet, \
  /bin/systemctl restart nexus-bet, \
  /bin/systemctl start nexus-bet
EOF
chmod 440 /etc/sudoers.d/nexus-bet

echo "Done. Maintenant installe le runner en tant que github-runner :"
echo "  su - github-runner"
echo "  cd ~/actions-runner"
echo "  ./config.sh --url https://github.com/PierreKROB/symfony_paris_sportif --token <TOKEN>"
echo "  exit"
echo "  ./svc.sh install github-runner && ./svc.sh start"
