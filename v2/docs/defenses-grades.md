# Soutenances et notes UE / ECUE

Ce lot prolonge l’interface Check Master existante : navigation latérale, bleu historique, Poppins, cartes, filtres et tableaux. Branche `refactor/v2-defenses-grades`, basée sur `refactor/v2-human-resources`. Aucune fusion ni reprise de données personnelles historiques.

## Parcours et droits

| Fonction | Droits par défaut |
| --- | --- |
| Vérifier et publier les programmes UE/ECUE | Administrateur de plateforme |
| Consulter les évaluations et les soutenances globales | Scolarité, administrateur |
| Saisir, publier et corriger les notes ; planifier les soutenances | Scolarité |
| Consulter ses bulletins publiés, demander et suivre sa soutenance | Étudiant explicitement rattaché au dossier |
| Consulter les soutenances auxquelles on est affecté | Enseignant, responsable de niveau/filière, membre de commission |
| Consigner le résultat du jury | Président actuellement affecté, avec le droit `defenses.assess` |

Les permissions de lecture, gestion, catalogues, consultation personnelle et jury sont distinctes. Une attribution de jury ne confère aucun droit global sur les autres mémoires. Les changements de rôles sont réévalués au moment des écritures ; un membre retiré du jury perd immédiatement l’accès au dossier et au PDF. Un candidat disposant de plusieurs rôles ne peut saisir ses notes, planifier sa propre soutenance ni siéger dans son jury. Un grade ou une fonction RH n’accorde pas de permission.

## Programme pédagogique issu du SQL

`database/reference/academic-catalog.json` reprend exclusivement les catalogues du fichier SQL fourni : **9 semestres, 75 UE et 44 ECUE**. Les identifiants source sont conservés comme codes, les noms sont nettoyés des espaces superflus ; les rattachements et crédits restent ceux du fichier. Aucun compte, mot de passe, enseignant, étudiant, note historique ou année historique n’est importé.

Empreinte SHA-256 du fichier source : `921bf93160eea570c2109d0852d1c5063883c4dbf70c0a8cb3b0ae4276c66454`.

Le fichier couvre L1 à M2, mais contient seulement le semestre 9 pour M2. Aucun semestre 10 ni ECUE mémoire n’est inventé. Le résultat de soutenance n’est donc pas injecté automatiquement dans les notes.

Points du fichier à vérifier avec le responsable pédagogique :

| Élément source | Constat |
| --- | --- |
| S1 / S5 / S6 / S9 | Respectivement 27 / 33 / 56 / 25 crédits UE |
| UE 2402 | 8 crédits UE pour 7 crédits ECUE |
| UE 1701 | 5 crédits UE pour un ECUE ISI de 2 crédits |
| UE 3608 « Projet » | 30 crédits, 4 heures |
| ECUE 1146 « UML » | Rattaché à l’UE 3609 « Environnement juridique » |

L’administrateur prépare un programme par année et semestre à partir de ces données, corrige les éléments après vérification et publie avec un compte rendu. La publication est bloquée si les crédits d’une UE composée diffèrent de la somme de ses ECUE. Les autres avertissements exigent une confirmation explicite. Les deux incohérences de somme doivent être résolues par une décision pédagogique : le logiciel ne choisit pas une correction à la place de l’établissement.

Chaque publication est immuable. La version suivante reprend le dernier programme du même semestre et de la même année. Elle devient la référence des nouveaux bulletins. Les bulletins déjà créés conservent leur programme initial, même s’il est retiré de la sélection active. Les programmes d’une autre année restent indépendants.

## Notes et publications

Une grille est liée à une inscription et à un semestre de son niveau. Il faut une année en cours, une inscription non annulée, un étudiant non archivé et un programme publié. La scolarité saisit les notes sur 20, avec deux décimales maximum ; la virgule est acceptée. Une case vide reste non saisie : elle ne vaut jamais zéro.

- UE sans ECUE : saisie directe de la note UE.
- UE composée : moyenne pondérée par les crédits ECUE, sans deuxième saisie sur l’UE.
- Semestre : moyenne des UE pondérée par leurs crédits, comptés une seule fois.
- Majeures / mineures : répartition historique selon le seuil de 4 crédits UE ; moyennes pondérées séparées.
- Une moyenne reste indisponible si une matière nécessaire n’est pas renseignée. Les calculs intermédiaires ne sont pas arrondis ; l’affichage final utilise deux décimales. Le nombre de crédits associés à des UE de moyenne au moins 10 utilise la moyenne non arrondie.

Exemple : une UE de 4 crédits, avec deux ECUE de 1 et 3 crédits notés 10 et 18, vaut 16/20. Avec une seconde UE de 2 crédits notée 8, la moyenne du semestre est `(16 × 4 + 8 × 2) / 6 = 13,33`.

Le dénominateur est le total réel des crédits du programme vérifié. Les anciens dénominateurs fixes 30/60 auraient produit des résultats incohérents avec certains catalogues source. **Aucune validation annuelle, compensation entre semestres, admission « À cheval » ou délivrance de diplôme n’est automatisée dans ce lot.** La décision pédagogique du bulletin semestriel est choisie explicitement par la scolarité avec un commentaire : en attente, validé, autorisé ou ajourné.

La publication exige toutes les notes. L’étudiant ne voit que ses publications ; les brouillons restent réservés aux services autorisés. Une correction motivée ouvre un nouveau brouillon, pendant que la dernière publication reste visible. La nouvelle publication conserve toutes les versions précédentes. Le bulletin peut être imprimé ou enregistré en PDF via le navigateur.

## Soutenances

Le parcours existant est conservé : **l’étudiant déclare son stage ; dès que les critères sont réunis, il dépose son mémoire ; la communication le transmet ; chaque membre de la commission doit l’approuver**. Les notes n’ajoutent aucun critère d’éligibilité au dépôt.

Après l’acceptation unanime, l’étudiant ouvre « Soutenances » depuis son mémoire et dépose sa demande. Le serveur vérifie les avis effectifs de chaque membre, la version acceptée, l’intégrité du PDF et l’éligibilité actuelle. Une seule demande est conservée par mémoire ; les doubles envois sont réexécutables sans doublon.

La scolarité fixe un créneau futur (15 à 240 minutes), le lieu, les consignes et un jury de 2 à 8 membres actifs et distincts, dont exactement un président. Le candidat ne peut pas appartenir au jury. La date est saisie en **heure d’Abidjan**, puis stockée en UTC.

Le planning refuse les chevauchements de lieu, de candidat ou de juré entre soutenances. Les invitations de réunion non refusées occupent également les jurés ; les réunions vérifient réciproquement les soutenances. Deux créneaux consécutifs sans chevauchement sont possibles. Le lieu est comparé après normalisation de la casse et des espaces ; il faut employer le même intitulé pour une même salle. Il n’y a pas encore de catalogue de salles partagé avec les réunions.

La convocation apparaît dans les espaces du candidat et de son jury et peut être imprimée. Aucun envoi externe ni message automatique supplémentaire n’est effectué par ce module. Le PDF accepté est accessible via une route privée liée à la version du dossier.

Après la fin du créneau, seul le président affecté peut consigner la décision du jury : validée ou ajournée avec une note sur 20, ou absence sans note. La décision reste explicite. Une correction exige un motif et conserve l’ancien résultat dans l’audit. Le résultat de soutenance reste distinct des notes UE/ECUE tant que le rattachement pédagogique manque dans le fichier source.

L’étudiant peut retirer sa demande avant planification. La scolarité peut annuler une séance ; le dossier et l’historique restent consultables. Elle peut reprogrammer un dossier annulé. Une séance commencée ne peut plus être reprogrammée ; une séance terminée avec résultat ne peut être ni annulée ni reprogrammée.

## Tester sur Mac

Depuis la racine du dépôt existant, avec les modifications locales enregistrées et Docker Desktop ouvert :

```bash
git fetch origin
git switch --track origin/refactor/v2-defenses-grades
cd v2
bash scripts/setup.sh
bash scripts/check.sh
```

Si la branche locale existe déjà : `git switch refactor/v2-defenses-grades`, puis `git pull --ff-only`. Conserver le même dossier/projet Docker pour garder la base et les fichiers privés. Ne pas importer le dump source ; les migrations ajoutent les nouvelles tables et permissions. L’application reste sur **http://localhost:8085**.

Recette avec des comptes fictifs, actifs et explicitement rattachés :

1. **Administrateur** : ouvrir « Évaluations » puis « Programmes UE / ECUE », sélectionner l’année courante et un semestre, vérifier les matières, corriger les crédits incohérents et publier. Vérifier qu’une nouvelle version ne modifie pas la précédente.
2. **Scolarité** : ouvrir l’inscription d’un étudiant dans « Évaluations », préparer son bulletin pour ce semestre, saisir une partie des notes et enregistrer. Vérifier que la publication est bloquée tant qu’il reste une note vide, puis compléter et publier avec la décision pédagogique.
3. **Étudiant** : consulter « Mes notes », imprimer le bulletin et vérifier qu’un autre compte étudiant n’y accède pas. Pendant une correction ouverte par la scolarité, vérifier que les anciennes notes publiées restent affichées jusqu’à la nouvelle publication.
4. **Étudiant M2 / communication / commission** : réaliser le parcours existant jusqu’à l’approbation de chaque membre. Vérifier qu’une demande de soutenance est impossible avec un avis manquant, puis déposer la demande après unanimité.
5. **Scolarité** : planifier la soutenance, sélectionner le président et au moins un autre membre. Vérifier les refus de doublon de jury, de membre inactif et de conflit horaire ; contrôler la convocation dans l’espace étudiant et le jury.
6. **Jury** : vérifier que seul le président obtient la saisie du résultat, après la fin du créneau. Pour une recette rapide, préparer une séance de 15 minutes et attendre sa fin. Consigner un résultat puis une correction motivée ; vérifier le suivi étudiant et l’historique.
7. **Régression** : vérifier qu’un étudiant éligible sans bulletin peut toujours déposer son mémoire immédiatement ; consulter réunions, messagerie et fiches RH.

Les tests automatisés utilisent des données fictives et des fichiers privés simulés. La CI Docker vérifie compilation TypeScript/Vite, suite PHP, migrations MySQL et page HTTP. La recette visuelle réelle dans Docker Desktop sur Mac reste à effectuer par l’utilisateur ; aucune connexion au Mac n’est disponible depuis cette session.
