# Nexus Bet — Plateforme de paris sportifs fictive

Projet Symfony 8 · ESGI 4ème année IW · 2025-2026

---

## Prérequis

- PHP >= 8.4
- Composer
- MySQL 8.0
- Node.js (pour la compilation CSS Tailwind)
- Symfony CLI (optionnel mais recommandé)

---

## Installation

```bash
# 1. Cloner le dépôt
git clone <url-du-repo>
cd symfony-paris-sportif

# 2. Installer les dépendances PHP
composer install

# 3. Copier le fichier d'environnement et configurer la base de données
cp .env .env.local
# Éditer .env.local et renseigner DATABASE_URL, RIOT_API_KEY, LOL_ESPORTS_API_KEY

# 4. Créer la base de données et jouer les migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 5. Charger les données de démonstration (fixtures)
php bin/console doctrine:fixtures:load

# 6. Compiler les assets CSS (Tailwind)
php bin/console tailwind:build

# 7. Lancer le serveur de développement
symfony server:start
# ou : php -S localhost:8000 -t public/
```

---

## Comptes de démonstration

Tous les mots de passe sont `password`.

| Rôle      | Email                  | Pseudo     |
|-----------|------------------------|------------|
| Admin     | admin@nexusbet.gg      | AdminNexus |
| Manager   | manager@nexusbet.gg    | Diarapak   |
| Utilisateur | faker@nexusbet.gg    | Faker_Fan  |
| Utilisateur | t1@nexusbet.gg       | T1_Enjoyer |
| Utilisateur | geng@nexusbet.gg     | GenG_King  |
| Utilisateur | g2@nexusbet.gg       | G2_Believer|
| Utilisateur | blg@nexusbet.gg      | BLG_Support|

---

## Architecture

```
src/
├── Controller/
│   ├── Admin/          → Gestion des utilisateurs et statistiques (ROLE_ADMIN)
│   ├── Manager/        → Gestion des événements et import (ROLE_MANAGER)
│   ├── User/           → Paris, portefeuille, jeu responsable (ROLE_USER)
│   └── Api/            → GET /api/events (public)
│
├── Entity/             → User, SportEvent, Outcome, Bet, Transaction
├── Repository/         → Requêtes Doctrine, dont findPaginated()
├── Form/               → RegistrationFormType, BetType, DepositType, SportEventType
├── Service/            → Logique métier isolée dans des services
│   ├── BettingService           Valide et enregistre un pari
│   ├── WalletService            Débit / crédit du portefeuille
│   ├── SportEventService        Cycle de vie d'un événement
│   ├── OddsCalculatorService    Recalcul des cotes après chaque pari
│   └── ResponsibleGamingService Limites, auto-exclusion, délai 48h
│
├── Security/
│   ├── UserChecker.php          Bloque la connexion si suspendu/auto-exclu
│   └── Voter/
│       ├── BetVoter.php         Permissions sur les paris
│       └── SportEventVoter.php  Permissions sur les événements
│
└── Twig/Components/
    └── BetSimulator.php         Live Component : cote et gain en temps réel
```

---

## Règles métier clés

### Cycle de vie d'un événement
`BROUILLON → PUBLIE → FERME → TERMINE`  
ou `BROUILLON/PUBLIE → ANNULE`

- **BROUILLON** : invisible aux joueurs, modifiable, supprimable.
- **PUBLIE** : paris ouverts. Suppression interdite.
- **FERME** : plus de nouveaux paris. En attente du résultat.
- **TERMINE** : résultat saisi, gains calculés, lecture seule.
- **ANNULE** : paris remboursés, lecture seule.

### Cotes dynamiques
Formule : `cote = total misé sur l'événement / total misé sur cette issue`  
Bornes : entre **1.10** et **5.00**. Cote par défaut : **1.50**.  
La cote est figée au moment du pari (`lockedOdds`).

### Jeu responsable
- Inscription refusée aux moins de 18 ans.
- Auto-exclusion : bloque **la connexion** (pas seulement les paris).
- Suspension : l'admin peut bloquer un compte.
- Limites de mise et de dépôt (quotidien / hebdomadaire).
- Augmenter un plafond prend **48 heures** (mesure anti-impulsivité).

### Mots de passe
Hashés via `bcrypt` (algorithme `auto` de Symfony). La valeur stockée en base commence par `$2y$` — jamais en clair.

---

## Variables d'environnement nécessaires

| Variable            | Description                         |
|---------------------|-------------------------------------|
| `DATABASE_URL`      | DSN MySQL                           |
| `RIOT_API_KEY`      | Clé Riot Games API (EUW)            |
| `RIOT_PLATFORM`     | Plateforme Riot (`euw1`)            |
| `RIOT_REGION`       | Région Riot (`europe`)              |
| `LOL_ESPORTS_API_KEY` | Clé API LoL Esports (publique)    |
