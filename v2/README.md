# Check Master v2 — lot 2 : comptes et accès

Socle Laravel 13 / PHP 8.4, React 19 / TypeScript, Inertia 3, Tailwind 4 et Vite 8.
Cette version comprend la connexion, les comptes, les types et groupes historiques, les permissions de gestion, le profil personnel, le changement de mot de passe, MySQL 8.4 et les tests des accès.

Les mémoires, les validations et les données historiques ne sont pas encore migrés. Les types et groupes sont repris ; les droits des modules métier suivront avec ces modules. Le tableau de bord métier reste sans statistiques fictives. La liste des comptes utilise les données réelles de la v2.

Voir le [guide du lot 2](docs/access-control.md) pour les règles d’accès, les correspondances historiques et l’attribution explicite du premier administrateur.

## Continuité de l’interface

La v2 reprend les logos, la palette bleue, la police Poppins et la structure des écrans Check Master existants.
Les améliorations portent sur la lisibilité, l’accessibilité et l’affichage mobile.
Les rubriques métier restent clairement indisponibles tant que leurs données et droits ne sont pas migrés.
Voir les [repères de continuité](docs/interface-continuity.md) à respecter pour chaque prochain écran.

## Démarrer sur Mac

Installer puis ouvrir [Docker Desktop](https://docs.docker.com/desktop/setup/install/mac-install/).
Git et Docker Compose v2 sont les seuls outils nécessaires sur le Mac : PHP, Composer et Node tournent dans des conteneurs.

Depuis un nouveau dossier de travail :

```bash
git clone --branch refactor/v2-access-control https://github.com/AngeGo11/check_master.git check-master-v2
cd check-master-v2/v2
bash scripts/setup.sh
docker compose exec app php artisan checkmaster:create-user
```

Ouvrir **http://localhost:8085** puis utiliser le compte créé. La saisie du mot de passe dans le terminal est masquée.

Si le dépôt existe déjà, enregistrer les modifications locales puis, à sa racine :

```bash
git fetch origin
git switch --track origin/refactor/v2-access-control
cd v2
bash scripts/setup.sh
docker compose exec app php artisan checkmaster:create-user
```

Si la branche locale existe déjà, utiliser `git switch refactor/v2-access-control` puis `git pull --ff-only`.

La création d’un compte n’accorde aucun droit d’administration. Pour le compte nominatif choisi, exécuter `docker compose exec app php artisan checkmaster:grant-admin responsable@example.com` en remplaçant l’adresse d’exemple. Confirmer l’identité affichée, puis se reconnecter. Les comptes créés depuis l’interface doivent personnaliser leur mot de passe temporaire avant d’accéder aux services.

Le script :
1. crée `.env` avec une clé applicative et deux mots de passe MySQL aléatoires ;
2. construit PHP-FPM, installe les dépendances et compile React ;
3. démarre une base **check_master_v2** vide dans un volume propre à cette application ;
4. applique les migrations v2 puis lance Nginx ;
5. attend une réponse HTTP valide de la page de connexion.

Relancer le script conserve les secrets existants et les données. Il n'importe aucun dump historique.
Le serveur est exposé uniquement sur `127.0.0.1`. MySQL et PHP-FPM n'ont aucun port publié sur le Mac.

## Vérifier

```bash
bash scripts/check.sh
```

Cette commande valide Composer, exécute les tests et vérifie la page servie par Nginx.
La compilation TypeScript et Vite est vérifiée pendant `setup.sh`.
Les tests utilisent une base SQLite en mémoire distincte de MySQL ; le démarrage et les migrations utilisent réellement MySQL.

Les tests couvrent les accès invités, la connexion, la rotation de session, les mauvais identifiants, la limitation des tentatives, la confidentialité des réponses, la déconnexion, CSRF, la création de compte, les permissions, la révocation des sessions et la protection du dernier administrateur.

Le workflow [Check Master v2](../.github/workflows/v2-checks.yml) exécute la même installation sur GitHub Actions.
Les deux branches des lots 1 et 2 sont vérifiées. L’enregistrement automatique des fichiers de verrouillage est limité à la branche du lot 1 ; ceux-ci sont déjà présents dans le lot 2. Le workflow ne fusionne aucune branche.
Les installations suivantes utilisent `composer.lock` et `package-lock.json`.
Si les droits Actions interdisent l'enregistrement, les fichiers restent téléchargeables dans l'artefact `verified-v2-lockfiles` du workflow ; ils peuvent aussi être produits et commités depuis le Mac après vérification.

## Commandes courantes

Toutes les commandes ci-dessous se lancent depuis `v2/`.

| Action | Commande |
| --- | --- |
| Arrêter, en conservant la base | `docker compose down` |
| Redémarrer les services | `docker compose up -d app web` |
| Journaux | `docker compose logs --tail=100 app web` |
| Voir les services | `docker compose ps` |
| Recompiler après une modification React/CSS | `docker compose run --rm --no-deps node npm run build` |
| Appliquer les migrations | `docker compose exec app php artisan migrate` |
| Créer un compte | `docker compose exec app php artisan checkmaster:create-user` |
| Lancer les tests | `docker compose exec -T app php artisan test` |

Ne pas supprimer le volume MySQL pour résoudre une simple erreur de démarrage.
Si le port 8085 est occupé, modifier ensemble `APP_PORT` et `APP_URL` dans `.env`, puis relancer `bash scripts/setup.sh`.
Si Docker signale qu'il ne peut pas joindre son démon, ouvrir Docker Desktop et attendre son démarrage.
Si un téléchargement échoue, vérifier la connexion Internet puis relancer le script ; les opérations sont réexécutables.
Si des tables de session sont absentes, exécuter les migrations ci-dessus.

## Organisation

| Dossier | Responsabilité |
| --- | --- |
| `app/Http/Controllers` | Réponses HTTP et orchestration |
| `app/Http/Requests` | Validation et authentification des entrées |
| `app/Http/Middleware` | Données partagées avec Inertia, explicitement limitées |
| `app/Models` | Persistance, relations et hachage des mots de passe |
| `app/Console/Commands` | Administration par terminal |
| `config`, `bootstrap`, `routes` | Configuration centrale et routage |
| `resources/js/pages` | Pages React |
| `resources/js/components`, `layouts` | Composants et structure de navigation |
| `resources/css` | Styles et tokens |
| `database/migrations` | Schéma versionné ; tables InnoDB sur MySQL |
| `storage/app/private` | Fichiers privés, sans route publique automatique |
| `tests/Feature` | Tests des parcours critiques |
| `docker`, `scripts` | Environnement et commandes d'installation |

Les Actions métier, Policies, Jobs, Notifications et services PDF seront introduits avec les opérations qu'ils portent.

## Sécurité et limites du lot

- Authentification Laravel avec mots de passe hachés, CSRF et renouvellement de session.
- Sessions en base, chiffrées, cookie HttpOnly / SameSite=Lax distinct de la version historique.
- Limitation par couple adresse e-mail/IP et limitation globale par IP sur la connexion.
- Aucun formulaire d'inscription public et aucun compte fourni avec un mot de passe par défaut.
- Aucune reprise des mots de passe SHA-256 de l’ancienne application ; la migration des comptes historiques reste un lot distinct.
- Types et groupes historiques, permissions de gestion des comptes, vérifications serveur et révocation des sessions. Aucun droit de validation métier activé à ce stade.
- `.env`, journaux, dépendances et documents exclus du nouveau code versionné.
- Les anciens fichiers sensibles déjà présents dans l'historique du dépôt ne sont pas nettoyés par l'ajout de `v2/`.

Ce Compose est destiné au **développement local et à la validation du socle**.
Avant la production : terminer les droits et règles métier, valider les migrations de données, préparer des images immuables avec dépendances verrouillées, configurer HTTPS et `SESSION_SECURE_COOKIE=true`, gérer les secrets hors dépôt, organiser sauvegardes/restauration et supervision. Les identifiants SMTP exposés dans l'ancien projet doivent être révoqués avant toute remise en service.

## Origine du socle

Les fichiers de configuration et d'amorçage sont adaptés du [squelette Laravel 13](https://github.com/laravel/laravel/tree/9ffef3bc4241e3cc333ef267258a404d0d95f194), distribué sous licence MIT ; voir [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).
Intégration conforme aux guides officiels [Laravel](https://laravel.com/docs/13.x) et [Inertia](https://inertiajs.com/docs/v3/installation/server-side-setup).
