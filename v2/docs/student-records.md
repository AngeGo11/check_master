# Lot 3 — dossiers étudiants et références pédagogiques

Ce lot prépare les données nécessaires au dépôt des mémoires. Il reprend la liste des étudiants, les fiches individuelles, les promotions, les niveaux et les statuts de l’application historique. Il ne déclare pas un étudiant éligible au dépôt : le lot 4 reprend les trois critères de l’écran historique des demandes : scolarité soldée, Master 2 et stage déclaré. Voir [le guide des inscriptions](enrollments.md).

## Références et correspondances

Sources lues dans le commit historique `c143941cbf446a851dc7ae1f6d09466cc3b1fee2` : `app/Views/etudiants.php`, les modales étudiants, `app/Models/Etudiant.php`, `app/Controllers/EtudiantsController.php`, `app/Models/Rapport.php`, `app/Controllers/MenuController.php` et les seuls catalogues de référence du schéma SQL.

| Historique | v2 | Règle |
| --- | --- | --- |
| `etudiants.num_etd` | `students.id` | Nouvel identifiant interne ; aucun étudiant importé |
| `num_carte_etd` | `card_number` | Obligatoire, unique, espaces extérieurs retirés et lettres en majuscules |
| `nom_etd`, `prenom_etd` | `last_name`, `first_name` | Obligatoires, présentés dans le même ordre |
| `email_etd` | `email` | Contact unique, normalisé ; indépendant de l’adresse de connexion |
| `date_naissance_etd`, `sexe_etd` | `date_of_birth`, `gender` | Facultatifs ; date future refusée |
| `niveau_etude` | `study_levels` | IDs 1–5 conservés : Licence 1 à Master 2 |
| `promotion` | `promotions` | IDs 1–22 conservés : 2003-2004 à 2024-2025 ; création et modification disponibles |
| `statut_etudiant` | `student_statuses` | IDs 1–3 conservés : Normal, À cheval, Redoublant |
| Jointure par adresse e-mail vers `utilisateur` | `students.user_id` | Rattachement explicite à un seul compte, sans jointure implicite |
| Suppression d’étudiants | `is_archived` | Archivage réversible, conservation du dossier et de ses références |

Les promotions récentes doivent être ajoutées par une personne habilitée ; aucune année courante n’est déduite du dump historique. Les niveaux et statuts sont consultables, sans modification de leur sens dans ce lot.

## Parcours disponibles

- **Étudiants** : recherche par nom, prénoms, carte, e-mail ou promotion ; filtres combinables de promotion, niveau, statut et archivage ; pagination de 20 dossiers.
- **Fiche** : consultation, création et modification, archivage et réactivation. Les fiches archivées sont masquées dans la liste par défaut, puis retrouvables avec le filtre.
- **Compte de connexion** : depuis une fiche, rattachement, remplacement ou retrait d’un compte existant. Le compte doit être actif, de type Étudiant, et ne pas être rattaché ailleurs. Un dossier archivé ne peut recevoir de nouveau rattachement ; un rattachement existant peut être retiré.
- **Mon dossier étudiant** : consultation du seul dossier explicitement rattaché au compte connecté. La page affiche un état vide si aucun dossier n’est rattaché, sans recherche de dossier par e-mail.
- **Paramètres généraux → Promotions et niveaux** : création et renommage des promotions, effectifs des dossiers par promotion, consultation des niveaux et statuts.
- **Tableau de bord** : effectif réel des dossiers non archivés et répartition actuelle par niveau, réservés aux personnes autorisées à consulter l’annuaire étudiant. Aucun historique annuel ou indicateur de validation n’est inventé.

La création d’une fiche ne crée pas de compte ni de mot de passe. Utiliser la gestion des comptes du lot 2, puis rattacher le compte après vérification de l’identité. Modifier l’adresse de contact ou l’adresse de connexion ne change pas le rattachement. L’archivage d’une fiche ne désactive pas le compte de connexion et conserve la lecture du dossier personnel.

## Permissions

| Permission | Portée | Attribution initiale |
| --- | --- | --- |
| `students.view` | Tous les dossiers, détails et effectifs | Administrateur plateforme |
| `students.manage` | Création, modification, archivage/réactivation | Administrateur plateforme |
| `students.self` | Son seul dossier rattaché, avec type Étudiant requis | Groupe Étudiant |
| `academic.view` | Promotions, niveaux et statuts | Administrateur plateforme |
| `academic.manage` | Création et renommage des promotions | Administrateur plateforme |

Le rattachement exige **simultanément** `students.manage` et `users.manage`. Un gestionnaire de scolarité ne peut donc pas modifier les associations avec les identités de connexion s’il n’a pas aussi ce droit.

Les droits peuvent être délégués depuis les groupes, avec obligation d’accorder la consultation avant la gestion du même module. Les autres groupes n’obtiennent pas automatiquement de nouveaux droits globaux. Les contrôles sont appliqués aux URL directes et dans les transactions d’écriture. La disparition d’un rattachement, du type Étudiant ou de la permission personnelle ferme cet accès dès la requête suivante.

## Intégrité et confidentialité

- Contraintes uniques sur carte, e-mail de contact et compte rattaché ; références protégées par clés étrangères. L’archivage ne libère pas ces identifiants.
- Révision sur chaque fiche et promotion : une saisie ancienne est refusée avec invitation à recharger, y compris pour le rattachement d’identité.
- Transactions avec le même ordre de verrouillage que les opérations d’accès du lot 2. Les droits sont relus après acquisition du verrou ; doublons et compatibilité du compte sont revérifiés.
- Journal `student_audits` : acteur, dossier/promotion, action, date et champs modifiés. Les valeurs d’identité, dates de naissance, mots de passe et adresses e-mail ne sont pas copiées dans ce journal. Les rattachements enregistrent seulement les identifiants des comptes ; les promotions enregistrent leur libellé.
- Réponses Inertia limitées aux champs nécessaires. Aucune date de naissance dans la liste, aucun secret de connexion dans les réponses. Les coordonnées du compte rattaché sont visibles uniquement aux personnes habilitées à gérer le rattachement.
- Conservation des contrôles de session, compte actif, mot de passe initial et CSRF du socle.

## Mise à jour Docker

Depuis la racine d’un dépôt existant, après avoir enregistré les modifications locales :

```bash
git fetch origin
git switch --track origin/refactor/v2-students
cd v2
bash scripts/setup.sh
bash scripts/check.sh
```

Si la branche locale existe déjà : `git switch refactor/v2-students` puis `git pull --ff-only` avant les scripts. Conserver le même dossier de travail et le même projet Docker Compose que pour les lots précédents afin de réutiliser le volume de données. Ne pas lancer `docker compose down -v`.

La migration ajoute les catalogues, les fiches et cinq permissions ; elle conserve les comptes et données des lots précédents. Les administrateurs existants reçoivent les droits du nouveau module via leur groupe. Les membres compatibles du groupe Étudiant reçoivent la consultation personnelle, qui ne révèle rien sans rattachement. Les anciennes tables et données du projet historique ne sont pas modifiées.

Pour une première installation, suivre le README et les commandes nominatives `checkmaster:create-user` puis `checkmaster:grant-admin`. Aucun compte réel n’a été créé ou promu lors de la préparation de ce lot.

## Vérifications et périmètre restant

`StudentRecordsTest` couvre les catalogues, les accès directs, la création et les validations, l’archivage, les doublons, les filtres et la pagination, les réponses privées, les rattachements, l’isolation entre étudiants, la révocation immédiate, les révisions concurrentes, les promotions, les indicateurs réels et CSRF. Les tests des lots précédents sont conservés.

Les tests applicatifs utilisent SQLite en mémoire. Le workflow démarre également Docker, applique réellement les migrations sur MySQL et vérifie HTTP ainsi que la compilation TypeScript/Vite. Le contrôle visuel sur Mac et un test à deux connexions MySQL simultanées restent à réaliser ; les tests de révision simulent deux formulaires ouverts successivement.

À la fin du lot 3, les années, inscriptions, frais, règlements et critères d’éligibilité restaient à reprendre ; ils sont maintenant décrits dans le [lot 4](enrollments.md). Restent à reprendre : import des étudiants historiques, photo, export ou opérations en masse, matières à rattraper, notes et décisions de commission. Le dépôt de mémoire est désormais décrit dans le [lot 5](reports.md). Le statut « À cheval » est une information de dossier ; il ne déclenche aucun calcul de frais ni inscription à une matière. Le journal est stocké mais son écran de consultation reste à reprendre avec la piste d’audit.

Le lot 4 complète cette dépendance avec les inscriptions et leurs règles avant la reprise du dépôt de mémoire.


La consultation centralisée des actions est désormais disponible dans la [piste d’audit](audit-trail.md), réservée au groupe Administrateur plateforme. Les limites ci-dessus décrivent le périmètre du lot initial.
