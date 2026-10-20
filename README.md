# CheckMaster

Plateforme de **gestion des commissions de validation** pour les soutenances de Master 2 MIAGE à l’[Université Félix Houphouët-Boigny (UFHB)](https://www.univ-fhb.edu.ci/).

L’application centralise le dépôt des mémoires, le suivi des dossiers, les réunions de commission, les décisions, la messagerie et l’administration pédagogique / RH.

## Stack technique

| Couche | Technologie |
|--------|-------------|
| Backend | PHP 8.2 (architecture MVC) |
| Serveur | Apache (`mod_rewrite`) |
| Base de données | MySQL 8.0 |
| Front | HTML, Tailwind CSS (CDN), JavaScript |
| PDF | Dompdf |
| Email | PHPMailer |
| Documents Word | PhpWord |
| Édition en ligne | OnlyOffice (optionnel) |
| Conteneurisation | Docker Compose |

## Fonctionnalités principales

- **Espace étudiant** — dépôt de documents, suivi de dossier, calendrier, messagerie, réclamations
- **Espace personnel administratif** — inscriptions, UE/ECUE, frais, comptes rendus, statistiques
- **Espace commission** — consultation des rapports, validation, réunions, suivi des décisions
- **Ressources humaines** — enseignants, personnel administratif, grades, fonctions, spécialités
- **Étudiants à cheval** — matières à rattraper et calcul des frais associés
- **Sauvegardes / restaurations** — dump BDD et fichiers
- **Piste d’audit** — journalisation et export des actions
- **Authentification** — connexion, mot de passe oublié, 2FA (selon configuration)

## Structure du projet

```
check_master/
├── app/
│   ├── Controllers/     # Contrôleurs MVC
│   ├── Models/          # Accès données (PDO)
│   ├── Views/           # Vues PHP (espaces, listes, modals)
│   └── Core/            # Noyau applicatif
├── config/              # BDD, mail, OnlyOffice, chemins
├── public/              # Point d’entrée web (DocumentRoot)
│   ├── app.php          # Routeur principal (session + espaces)
│   ├── pageConnexion.php
│   └── assets/          # CSS, JS, traitements AJAX
├── routes/              # Déclarations de routes
├── storage/             # Uploads, logs, sauvegardes, dump SQL
├── docs/                # Documentation complémentaire
├── docker-compose.yml
└── Dockerfile
```

## Prérequis

- [Docker](https://docs.docker.com/get-docker/) et Docker Compose  
  **ou** PHP 8.2+, Apache/Nginx, MySQL 8, extensions `pdo_mysql` / `mysqli` / `mbstring`

## Démarrage rapide (Docker)

```bash
# Depuis la racine du projet
docker compose up -d --build
# ou
./start-docker.sh
```

Services exposés (selon `docker-compose.yml`) :

| Service | URL / port |
|---------|------------|
| Application | http://localhost:8083 |
| phpMyAdmin | http://localhost:8082 |
| MySQL | `localhost:3306` |

Identifiants MySQL / phpMyAdmin par défaut :

- Base : `check_master_db`
- Utilisateur app : `check_master_user` / `check_master_password`
- Root : `root` / `root`

Le dump initial est monté depuis `storage/data/check_master_db.sql`.

### Commandes utiles

```bash
docker compose logs -f      # logs
docker compose down         # arrêt
docker compose restart      # redémarrage
./start-docker.sh --clean   # rebuild + nettoyage volumes
```

## Configuration

Connexion BDD (`config/config.php`) — variables d’environnement Docker ou valeurs par défaut :

| Variable | Défaut |
|----------|--------|
| `DB_HOST` | `db` |
| `DB_NAME` | `check_master_db` |
| `DB_USER` | `root` |
| `DB_PASSWORD` | `root` |

Autres fichiers :

- `config/mail.php` — envoi d’emails
- `config/onlyoffice.php` — édition de documents
- `config/paths.php` — chemins / `BASE_PATH` (Docker port `8083` → `/`)

## Accès applicatif

1. Ouvrir http://localhost:8083
2. Se connecter via **Connexion** (`pageConnexion.php`)
3. Redirection selon le groupe utilisateur (`app.php`) :

| Groupe | Espace |
|--------|--------|
| 1 | Étudiant |
| 2–4 | Personnel administratif |
| 5–9 | Commission / enseignants / admin |

Guide utilisateur détaillé : [`public/README.md`](public/README.md).

## Dépendances PHP

Installées sous `vendor/` (Composer) :

- `phpmailer/phpmailer`
- `dompdf/dompdf`
- `symfony/var-dumper`

PhpWord est déclaré dans `public/composer.json` (`phpoffice/phpword`).

## Documentation complémentaire

| Document | Contenu |
|----------|---------|
| [`public/README.md`](public/README.md) | Guide d’utilisation (espaces, dépôt docs, dépannage) |
| [`ONLYOFFICE_SETUP.md`](ONLYOFFICE_SETUP.md) | Intégration OnlyOffice |
| [`COMPTE_RENDU_SETUP.md`](COMPTE_RENDU_SETUP.md) | Comptes rendus |
| [`SYSTEME_ETUDIANTS_CHEVAL.md`](SYSTEME_ETUDIANTS_CHEVAL.md) | Étudiants à cheval |
| [`CONTROLEURS_ET_MODELES_CREES.md`](CONTROLEURS_ET_MODELES_CREES.md) | RH, sauvegardes, audit |
| [`docs/EMAIL_SYSTEM.md`](docs/EMAIL_SYSTEM.md) | Système d’emails |

## Stockage

```
storage/
├── data/           # Dump SQL d’initialisation
├── uploads/        # profils, rapports, comptes rendus, réclamations…
├── sauvegardes/    # backups applicatifs
├── logs/
└── templates/
```

Ces répertoires sont créés / permissionnés au démarrage Docker.

## Licence / contexte

Projet académique — **CheckMaster**, UFHB.  
Voir aussi le guide utilisateur dans `public/README.md` pour le support et les bonnes pratiques.
