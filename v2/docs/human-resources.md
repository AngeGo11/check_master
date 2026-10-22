# Ressources humaines — enseignants et personnel administratif

Ce lot prolonge les réunions et la messagerie sur la branche `refactor/v2-human-resources`. Les onglets Enseignants / Personnel administratif, les cartes, les filtres et les tableaux reprennent `app/Views/ressources_humaines.php`, dans la même interface bleue, avec Poppins et les logos historiques.

## Fonctions disponibles

- Créer et modifier les fiches : identité, coordonnées, naissance, date d’entrée, genre et poste du personnel administratif.
- Rechercher et filtrer par type de fiche, archivage, rattachement de compte, grade, fonction ou spécialité ; pagination de 20 fiches.
- Gérer les grades, fonctions et spécialités : ajout, renommage et désactivation réversible.
- Attribuer un grade et une fonction à un enseignant, avec date d’effet ; conserver les attributions précédentes, leur libellé d’origine et leur date de clôture. Les spécialités sont multiples.
- Rattacher explicitement un compte existant ; consulter sa propre fiche en lecture seule.
- Ajouter, remplacer ou retirer une photo privée ; archiver et restaurer une fiche avec motif.
- Consulter les 30 dernières modifications et jusqu’à 10 réunions à venir déjà accessibles au lecteur.

Les quatre indicateurs RH portent sur le type sélectionné avant les autres filtres. Le compteur Enseignants du tableau de bord compte les fiches enseignantes non archivées ; il est visible uniquement avec les droits de consultation RH.

## Correspondance avec la base fournie

La migration structure les références et les historiques dans des tables InnoDB avec clés étrangères. Elle reprend uniquement les 4 grades, 4 fonctions et 8 spécialités du dump fourni, avec leurs identifiants historiques par catégorie. Aucune personne, photo, attribution historique ou ancien mot de passe n’est importé automatiquement.

| Tables historiques | Organisation v2 |
| --- | --- |
| `enseignants`, `personnel_administratif` | `staff_profiles`, avec un type de fiche explicite et une référence ENS/ADM |
| `grade`, `fonction`, `specialite` | `staff_references`, avec catégorie, identifiant historique et statut actif |
| `avoir`, `occuper` | `staff_assignments`, avec dates, auteur et libellé enregistré |
| `enseignant_specialite` | `staff_specialties`, sans doublon de rattachement |
| Photos des fiches | `staff_photos` et fichiers privés |
| Actions RH | `staff_audits`, sans copie des coordonnées ni mots de passe |

Le transfert des données personnelles historiques demandera un import distinct, avec vérification des doublons et des correspondances de comptes. Le dump SQL n’est pas ajouté au code v2.

## Droits et rattachements

Le menu RH historique est attribué au groupe 8. La v2 conserve cette restriction et vérifie la compatibilité entre type et groupe.

| Permission | Attribution par défaut | Portée |
| --- | --- | --- |
| `hr.view` | Administrateur plateforme, groupe 8 compatible | Liste, fiches, photos et catalogues RH |
| `hr.manage` | Même groupe | Identité, historique des attributions, photos, archivage |
| `hr.catalogs.manage` | Même groupe | Grades, fonctions et spécialités |
| `hr.self` | Groupes 2 à 9 compatibles | Sa fiche explicitement rattachée, en lecture seule |
| `users.manage` + `hr.manage` | Administrateur habilité | Modifier le rattachement d’un compte |

Une fiche enseignante accepte un compte actif de type Enseignant (2) ou Responsable (3). Une fiche administrative accepte le type Personnel administratif (4). Un compte ne peut être rattaché qu’à une fiche RH, même si elle est archivée. Une adresse e-mail identique ne crée aucun rattachement automatique. Le retrait du lien, une modification de type incompatible ou la révocation de `hr.self` supprime l’accès personnel correspondant.

Le bouton « Gérer le compte, ses droits et la commission » ouvre la gestion existante des utilisateurs. Un grade, une fonction, un poste ou un archivage RH ne change pas les groupes du compte. **Archiver une fiche ne désactive pas le compte et ne retire aucun membre de commission.** Ces changements restent explicites dans la gestion des comptes, afin de préserver les règles d’approbation unanime des mémoires.

Les réunions affichées doivent à la fois concerner le compte rattaché et être déjà accessibles au lecteur selon les règles du module Réunions. La consultation RH ne donne aucun accès supplémentaire à un calendrier ou à un document de réunion.

## Historique et fichiers

Les attributions sont datées jusqu’à aujourd’hui, après la naissance et l’entrée en fonction lorsqu’elles sont connues. Un changement ne peut précéder la dernière attribution ou sa clôture. Un remplacement le même jour est possible et conserve les deux événements. Clôturer sans remplacement laisse l’enseignant sans attribution courante de cette catégorie. Désactiver une référence conserve les associations existantes, mais empêche de nouvelles attributions.

Les formulaires vérifient la version de la fiche pour refuser l’écrasement d’un changement concurrent. Les écritures métier et les audits sont transactionnels ; les tentatives répétées de création ou d’envoi d’une même photo ne créent pas de doublon.

Les photos acceptées sont des JPEG/PNG de 5 Mo et 2048 × 2048 pixels maximum. Elles sont décodées puis réencodées pour retirer les métadonnées et les données ajoutées au fichier. Leur taille et leur empreinte sont contrôlées à la lecture. Les anciennes photos restent conservées, mais seule la photo courante est servie par une route autorisée, sans cache public.

Sauvegarder ensemble MySQL et `v2/storage/app/private`, notamment `staff`, `reports` et `meetings`. Les photos retirées ne sont pas purgées automatiquement. Aucun lien symbolique public n’est nécessaire pour les photos. Le retrait de cette migration exige une restauration cohérente de sauvegarde, comme les autres modules avec historique.

## Installation et test sur Mac

Ouvrir Docker Desktop. Depuis la racine du dépôt existant, après avoir enregistré les éventuelles modifications locales :

```bash
git fetch origin
git switch --track origin/refactor/v2-human-resources
cd v2
bash scripts/setup.sh
bash scripts/check.sh
```

Si la branche locale existe déjà, remplacer la commande `git switch --track` par `git switch refactor/v2-human-resources`, puis exécuter `git pull --ff-only`. Conserver le même dossier/projet Docker pour réutiliser la base et les fichiers privés. `setup.sh` reconstruit PHP avec GD pour les photos et applique la nouvelle migration sans réimporter les données.

Ouvrir **http://localhost:8085** avec le compte administrateur déjà utilisé. Pour une première installation, suivre la création du compte et l’attribution explicite du rôle administrateur dans le [README](../README.md).

1. Dans **Ressources humaines**, créer un enseignant fictif avec deux spécialités, un grade et une fonction datés. Vérifier les filtres, la fiche et le compteur du tableau de bord.
2. Ajouter une photo PNG/JPEG. Vérifier son affichage, son remplacement et son retrait. Tester le refus d’un autre format ou d’une image dépassant les limites.
3. Changer le grade à une date plus récente. Vérifier la clôture et le libellé de l’ancienne attribution. Désactiver une spécialité dans les catalogues : elle reste sur la fiche, mais ne peut plus être ajoutée ailleurs.
4. Créer un compte enseignant de test via **Paramètres → Utilisateurs**, puis le rattacher à la fiche par son adresse exacte. Dans une autre session, personnaliser son mot de passe temporaire et ouvrir **Ma fiche professionnelle** : seule cette fiche est visible, sans action de gestion.
5. Créer une fiche de personnel administratif avec un poste et la rattacher à un compte de type 4. Vérifier que les attributions académiques ne sont pas proposées et qu’un compte étudiant ou incompatible est refusé.
6. Avec un compte de commission rattaché, vérifier les réunions auxquelles il participe. Les séances et documents hors de son périmètre doivent rester inaccessibles.
7. Ouvrir la même fiche dans deux onglets. Modifier le premier, puis essayer d’enregistrer le second : le formulaire obsolète doit être refusé avec une invitation à recharger.
8. Archiver puis restaurer la fiche avec un motif. Vérifier sa conservation, l’historique et les filtres. Le compte et ses groupes restent inchangés ; retirer le rattachement supprime la consultation personnelle.
9. Vérifier ces écrans dans Safari/Chrome, en largeur mobile et au clavier, avec les logos, couleurs et repères existants.

Les tests automatiques couvrent les permissions, rattachements, dates, doublons, concurrence, transactions, confidentialité, photos, filtres, pagination, réunions accessibles et CSRF. La CI compile TypeScript/React, démarre Docker/MySQL, applique les migrations, exécute les tests sur SQLite en mémoire et vérifie la page Nginx. La comparaison visuelle sur Mac reste une recette manuelle.

Ce lot ne comprend pas la paie, les emplois du temps d’enseignement, les soutenances, les notes ou les comptes rendus formels. Les règles déjà validées de déclaration étudiante, d’éligibilité immédiate et d’approbation unanime du mémoire sont conservées.
