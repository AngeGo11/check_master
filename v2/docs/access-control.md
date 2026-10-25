# Lot 2 — comptes, groupes et permissions

La [matrice actuelle du sprint correctif](report-workflow-corrections.md#interfaces-par-groupe-dans-ce-sprint) précise désormais les droits de scolarité, de transmission et de commission pour les neuf groupes.

Ce document décrit le socle du lot 2. Le lot 3 ajoute les permissions étudiants et pédagogiques, et la lecture personnelle pour le groupe Étudiant : voir [dossiers étudiants](student-records.md). Le catalogue historique de types et groupes reste identique.

Ce lot reprend la gestion des comptes depuis les paramètres généraux. Il conserve les types et groupes de la base historique, la palette, les logos et les intitulés des listes.

## Ce qui fonctionne

- Liste des utilisateurs : totaux réels, recherche par nom/e-mail, filtres par type, groupe et statut, pagination de 20 comptes.
- Création et modification des comptes, affectation explicite de plusieurs types et groupes compatibles, activation/désactivation.
- Consultation des neuf groupes historiques et modification de leurs permissions pour les services disponibles dans ce lot.
- Profil personnel en lecture et changement du mot de passe avec vérification du mot de passe actuel.
- Personnalisation obligatoire du mot de passe temporaire fourni lors d’une création depuis l’interface.
- Menus et actions visibles selon les droits, avec contrôle serveur sur toutes les routes correspondantes.

## Correspondance historique

Références : `storage/data/check_master_db.sql`, `app/Controllers/AuthController.php`, `app/Controllers/MenuController.php`, `app/Views/listes/liste_utilisateurs.php`, `app/Views/listes/liste_groupes_utilisateurs.php` et `app/Views/parametres_generaux.php`, au commit `c143941`.

Les identifiants de référence et la compatibilité `type_a_groupe` sont conservés. Les tables v2 utilisent des clés étrangères et InnoDB sur MySQL.

| Groupe | Types compatibles |
| --- | --- |
| Étudiant | Étudiant |
| Chargé de communication | Personnel administratif |
| Responsable scolarité | Personnel administratif |
| Secrétaire | Personnel administratif |
| Enseignant sans responsabilité administrative | Enseignant simple |
| Responsable niveau | Enseignant administratif |
| Responsable filière | Enseignant administratif |
| Administrateur plateforme | Enseignant administratif |
| Commission de validation | Enseignant simple, Enseignant administratif |

Un type ne confère aucun droit à lui seul. Les droits exigent une appartenance explicite au groupe et un type compatible. Cela corrige les anciennes requêtes qui pouvaient déduire les groupes depuis le type sans restreindre correctement l’appartenance à l’utilisateur connecté.

| Permission disponible | Portée |
| --- | --- |
| `users.view` | Lire la liste de tous les comptes, leurs types, groupes et statuts |
| `users.manage` | Créer/modifier les comptes, attribuer leurs groupes, activer/désactiver ; exige `users.view` lors de la configuration |
| `groups.view` | Lire le catalogue des groupes et des permissions |
| `groups.manage` | Déléguer/retirer ces permissions aux groupes ; exige `groups.view` lors de la configuration |

La gestion des comptes et la configuration des groupes sont donc des droits d’administration étendus. Initialement, seul le groupe Administrateur plateforme possède ces quatre permissions. Aucun compte ne lui est affecté automatiquement. Les autres groupes disposent de leur espace personnel ; leurs traitements métier seront repris dans les lots suivants.

## Mise à jour et premier administrateur

Depuis le dépôt local, après avoir enregistré tout travail en cours :

```bash
git fetch origin
git switch --track origin/refactor/v2-access-control
cd v2
bash scripts/setup.sh
bash scripts/check.sh
```

Si cette branche existe déjà localement, utiliser `git switch refactor/v2-access-control`, puis `git pull --ff-only`.
La migration ajoute le catalogue et les relations ; elle conserve les comptes et mots de passe v2 existants. Les comptes du lot 1 restent actifs, sans groupe attribué. Les sessions précédentes doivent se reconnecter.

Créer d’abord un compte nominatif si nécessaire :

```bash
docker compose exec app php artisan checkmaster:create-user
```

Puis, en remplaçant l’adresse d’exemple par celle du compte destiné à administrer la plateforme :

```bash
docker compose exec app php artisan checkmaster:grant-admin responsable@example.com
```

La commande affiche le nom et l’adresse du compte, demande confirmation, attribue explicitement le type compatible et le groupe, journalise l’action et révoque les sessions de ce compte. Elle ne crée pas de compte, ne réactive pas un compte inactif et ne distribue aucun mot de passe par défaut. Se reconnecter pour voir « Paramètres généraux ».

## Garanties mises en œuvre

- Droits relus depuis la base à chaque requête ; aucune autorisation conservée uniquement dans le navigateur ou une ancienne session.
- Compte inactif refusé à la connexion et dans les sessions déjà ouvertes, y compris lors d’une reconnexion persistante.
- Modification de l’e-mail, des types, des groupes ou du statut : version d’authentification incrémentée, jeton de connexion persistante remplacé et sessions en base supprimées. La session courante de l’auteur est renouvelée s’il modifie son propre compte actif.
- Changement du mot de passe : vérification du mot de passe actuel, hachage Laravel, renouvellement de session et CSRF, invalidation des autres sessions et des anciens jetons persistants.
- Désactivation de son propre compte interdite ; au moins un administrateur plateforme actif conservé. Un verrou transactionnel commun sérialise les modifications d’accès pour protéger cette règle contre les écritures simultanées.
- Permissions du groupe Administrateur plateforme protégées contre une modification depuis l’interface.
- Attributs sensibles exclus des réponses et des mises à jour générales ; seul le parcours de changement du mot de passe modifie un mot de passe existant.
- Journal `access_audits` dans la même transaction que l’action : auteur, cible, action et changements de droits ; aucun mot de passe, jeton ou nouvelle valeur d’e-mail n’y est écrit.
- Aucun envoi d’e-mail depuis ce lot. Le formulaire d’oubli du mot de passe reste une aide invitant à contacter l’administration.

## Validation

`bash scripts/check.sh` exécute les tests de connexion et de contrôle d’accès. Les scénarios couvrent les accès directs sans permission, les droits de lecture seuls, les appartenances explicites, la compatibilité type/groupe, les filtres, la confidentialité des réponses, les comptes inactifs, les sessions révoquées, les mots de passe temporaires, CSRF et la protection de l’administration.

Les tests fonctionnels utilisent SQLite en mémoire. Le démarrage Docker et les migrations sont aussi exécutés sur MySQL 8.4. Les tests ne simulent pas deux connexions MySQL concurrentes ; la sérialisation repose sur le verrou transactionnel implémenté. Le contrôle visuel dans un navigateur sur le Mac reste nécessaire.

## Périmètre restant

Les droits sur les mémoires, soutenances, validations, réunions et autres traitements ne sont pas activés par ce lot. Le catalogue des traitements et leur ordre historique seront repris avec chaque module. Les six niveaux d’accès aux données seront traduits en règles de portée avec ces modules ; ils ne sont pas présentés comme des restrictions opérationnelles ici.

Pas d’import des anciens utilisateurs ou de leurs empreintes SHA-256. La migration des comptes historiques devra produire un état de rapprochement des identités et un parcours de réinitialisation ; les mots de passe historiques ne doivent pas être réutilisés tels quels.

La création/suppression de groupes, les affectations en masse, les fonctions/grades/spécialités des enseignants, l’envoi de liens de réinitialisation et l’écran de consultation de la piste d’audit restent à reprendre. Le tableau de bord métier et les écrans propres à chaque rôle seront adaptés avec leurs services et données.


La consultation centralisée des actions est désormais disponible dans la [piste d’audit](audit-trail.md), réservée au groupe Administrateur plateforme. Les limites ci-dessus décrivent le périmètre du lot initial.
