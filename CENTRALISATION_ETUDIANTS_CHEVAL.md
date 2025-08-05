# Centralisation du Système Étudiant à Cheval

## Vue d'ensemble

Le système de gestion des étudiants à cheval a été entièrement centralisé dans les fichiers existants de l'application GSCV+. Cette centralisation permet une meilleure organisation du code et évite la duplication de fonctionnalités.

## Fichiers modifiés

### 1. `app/Controllers/EtudiantsController.php`
- **Ajouts** : Toutes les méthodes de gestion des étudiants à cheval ont été intégrées
- **Méthodes ajoutées** :
  - `inscrireEtudiantCheval()` : Inscription d'un étudiant à cheval
  - `ajouterMatiereForm()` : Formulaire d'ajout de matière
  - `ajouterMatiereRattrapage()` : Ajout d'une matière à rattraper
  - `updateStatutMatiere()` : Mise à jour du statut d'une matière
  - `supprimerMatiereRattrapage()` : Suppression d'une matière
  - `calculerFrais()` : Calcul des frais d'inscription
  - `updateInscription()` : Mise à jour de l'inscription
  - `exportEtudiantsCheval()` : Export des données
  - `detailEtudiantCheval()` : Détails d'un étudiant à cheval
  - `inscriptionForm()` : Formulaire d'inscription
  - `getAnneeCourante()` : Récupération de l'année courante
  - `getStatistiquesCheval()` : Statistiques des étudiants à cheval
  - `getAllEtudiantsCheval()` : Liste des étudiants à cheval

### 2. `app/Models/Etudiant.php`
- **Ajouts** : Toutes les méthodes de gestion des données des étudiants à cheval
- **Méthodes ajoutées** :
  - `isEtudiantCheval()` : Vérification du statut étudiant à cheval
  - `getInscriptionCheval()` : Récupération des informations d'inscription
  - `getMatieresRattrapage()` : Liste des matières à rattraper
  - `ajouterMatiereRattrapage()` : Ajout d'une matière
  - `updateStatutMatiereRattrapage()` : Mise à jour du statut
  - `supprimerMatiereRattrapage()` : Suppression d'une matière
  - `inscrireEtudiantCheval()` : Inscription d'un étudiant
  - `updateInscriptionCheval()` : Mise à jour de l'inscription
  - `calculerFraisCheval()` : Calcul des frais
  - `getAllEtudiantsCheval()` : Liste complète des étudiants
  - `getStatistiquesCheval()` : Statistiques
  - `getHistoriqueInscriptionsCheval()` : Historique des inscriptions
  - `peutPasserNiveauSuperieur()` : Vérification du passage

### 3. `app/Views/etudiants.php`
- **Ajouts** : Section complète pour la gestion des étudiants à cheval
- **Fonctionnalités ajoutées** :
  - KPI des étudiants à cheval (total, paiements, montants)
  - Filtres de recherche (nom, niveau, statut paiement)
  - Tableau des étudiants à cheval avec actions
  - Boutons d'export et d'inscription
  - Modales pour les différentes actions
  - JavaScript pour les interactions AJAX

### 4. Modales créées
- `app/Views/modals/inscription_etudiant_cheval_modal.php` : Inscription d'un étudiant
- `app/Views/modals/ajouter_matiere_modal.php` : Ajout de matière à rattraper
- `app/Views/modals/detail_etudiant_cheval_modal.php` : Détails complets d'un étudiant

### 5. `routes/web.php`
- **Suppressions** : Routes spécifiques aux étudiants à cheval supprimées
- **Intégration** : Toutes les actions sont maintenant gérées via `page=etudiants`

## Fichiers supprimés

Les fichiers suivants ont été supprimés car leurs fonctionnalités ont été intégrées :

- `app/Controllers/EtudiantChevalController.php`
- `app/Models/EtudiantCheval.php`
- `app/Views/etudiant_cheval.php`

## Fonctionnalités disponibles

### 1. Gestion des inscriptions
- Inscription d'un étudiant à cheval
- Modification des informations d'inscription
- Calcul automatique des frais
- Gestion des statuts de paiement

### 2. Gestion des matières
- Ajout de matières à rattraper
- Mise à jour des statuts (En cours, Validée, Échouée)
- Suppression de matières
- Suivi du progrès

### 3. Statistiques et rapports
- KPI en temps réel
- Export des données en CSV
- Historique des inscriptions
- Vérification de l'éligibilité au passage

### 4. Interface utilisateur
- Interface moderne avec Tailwind CSS
- Modales interactives
- Notifications en temps réel
- Actions AJAX pour une meilleure expérience

## Utilisation

### Accès à la section étudiants à cheval
1. Aller sur la page `etudiants`
2. Faire défiler jusqu'à la section "Étudiants à Cheval"
3. Utiliser les filtres pour rechercher des étudiants spécifiques

### Inscription d'un étudiant à cheval
1. Cliquer sur "Inscrire étudiant à cheval"
2. Remplir le formulaire avec les informations requises
3. Valider l'inscription

### Gestion des matières
1. Cliquer sur "Voir détails" pour un étudiant
2. Utiliser les boutons d'action pour gérer les matières
3. Mettre à jour les statuts selon les résultats

### Export des données
1. Cliquer sur "Exporter" dans la section étudiants à cheval
2. Le fichier CSV sera téléchargé automatiquement

## Avantages de la centralisation

1. **Cohérence** : Toutes les fonctionnalités liées aux étudiants sont au même endroit
2. **Maintenance** : Un seul endroit pour maintenir le code
3. **Performance** : Moins de fichiers à charger
4. **UX** : Interface unifiée pour toutes les fonctionnalités étudiant
5. **Développement** : Plus facile d'ajouter de nouvelles fonctionnalités

## Base de données

Le système utilise les tables suivantes :
- `inscription_etudiant_cheval` : Inscriptions des étudiants à cheval
- `matieres_rattrapage` : Matières à rattraper par étudiant
- `frais_etudiant_cheval` : Configuration des frais
- `etudiants` : Informations des étudiants
- `promotion` : Promotions disponibles
- `niveau_etude` : Niveaux d'étude
- `ecue` : Matières/ECUE
- `annee_academique` : Années académiques

## Configuration

Les frais sont configurés dans la table `frais_etudiant_cheval` avec :
- `montant_base` : Frais de base par niveau
- `montant_supplementaire` : Frais supplémentaires par matière

Le calcul se fait automatiquement : `montant_total = montant_base + (montant_supplementaire × nombre_matieres)` 