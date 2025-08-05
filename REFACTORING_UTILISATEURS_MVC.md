# Refactoring de la Gestion des Utilisateurs vers MVC

## Vue d'ensemble

Ce document décrit la refactorisation du code de gestion des utilisateurs depuis une approche procédurale vers une architecture MVC (Model-View-Controller).

## Fichiers concernés

### Avant la refactorisation
- `app/Views/listes/liste_utilisateurs.php` - Contenait la logique métier et l'affichage
- `public/assets/traitements/traitements_liste_utilisateurs.php` - Logique de traitement des actions

### Après la refactorisation
- `app/Controllers/UtilisateurController.php` - Contrôleur pour la logique métier
- `app/Models/Utilisateur.php` - Modèle pour les interactions avec la base de données
- `public/assets/traitements/traitements_liste_utilisateurs_mvc.php` - Nouveau fichier de traitement utilisant le contrôleur
- `app/Views/listes/liste_utilisateurs_mvc_example.php` - Exemple de vue utilisant le nouveau système

## Fonctionnalités refactorisées

### 1. Ajout d'utilisateur
- **Avant** : Logique dans `traitements_liste_utilisateurs.php` (lignes 25-200)
- **Après** : Méthode `addUser()` dans `UtilisateurController` et `addUserWithType()` dans `Utilisateur`

### 2. Génération de mots de passe
- **Avant** : Logique dans `traitements_liste_utilisateurs.php` (lignes 202-400)
- **Après** : Méthode `generatePasswords()` dans `UtilisateurController` et `generatePasswordsForUsers()` dans `Utilisateur`

### 3. Édition d'utilisateur
- **Avant** : Logique dans `traitements_liste_utilisateurs.php` (lignes 402-600)
- **Après** : Méthode `editUser()` dans `UtilisateurController` et `updateUserInfo()` dans `Utilisateur`

### 4. Activation/Désactivation d'utilisateur
- **Avant** : Logique dans `traitements_liste_utilisateurs.php` (lignes 602-650)
- **Après** : Méthodes `activateUser()` et `deactivateUser()` dans `UtilisateurController`

### 5. Affectation en masse
- **Avant** : Logique dans `traitements_liste_utilisateurs.php` (lignes 652-900)
- **Après** : Méthode `assignMultipleUsers()` dans `UtilisateurController` et `Utilisateur`

### 6. Récupération de données
- **Avant** : Requêtes SQL directes dans les fichiers de traitement
- **Après** : Méthodes dédiées dans le modèle `Utilisateur`

## Structure des nouvelles classes

### UtilisateurController

```php
class UtilisateurController {
    // Méthodes principales
    public function addUser($login, $type_utilisateur)
    public function generatePasswords($user_ids)
    public function editUser($id_utilisateur, $type_utilisateur, ...)
    public function activateUser($id_utilisateur)
    public function deactivateUser($id_utilisateur)
    public function assignMultipleUsers($selected_users, ...)
    
    // Méthodes de récupération de données
    public function getUtilisateursWithFilters($page, $per_page, $filters)
    public function getInactiveUsers()
    public function getUtilisateurDetails($id)
    public function getTypesUtilisateurs()
    public function getGroupesUtilisateurs()
    // ... autres méthodes getters
    
    // Méthodes d'envoi d'emails
    private function sendWelcomeEmail($login, $password, $nom_complet)
    private function sendPasswordUpdateEmail($login, $password, $nom_complet)
    private function sendActivationEmail($login, $password, $nom_complet)
}
```

### Utilisateur (Model)

```php
class Utilisateur {
    // Méthodes utilitaires
    public function generateRandomPassword($length = 12)
    public function getUserFullName($login)
    
    // Méthodes de gestion des utilisateurs
    public function addUserWithType($login, $type_utilisateur)
    public function generatePasswordsForUsers($user_ids)
    public function updateUserInfo($id_utilisateur, ...)
    public function assignMultipleUsers($selected_users, ...)
    
    // Méthodes de récupération de données
    public function getUtilisateursWithFilters($page, $per_page, $filters)
    public function getInactiveUsers()
    public function getUtilisateurDetails($id)
    public function getTypesUtilisateurs()
    // ... autres méthodes getters
    
    // Méthodes existantes conservées
    public function ajouterUtilisateur(...)
    public function updateUtilisateur(...)
    public function desactiverUtilisateur($id)
    public function reactiverUtilisateur($id)
    // ... autres méthodes existantes
}
```

## Avantages de la refactorisation

### 1. Séparation des responsabilités
- **Controller** : Gère la logique métier et la coordination
- **Model** : Gère les interactions avec la base de données
- **View** : Se concentre uniquement sur l'affichage

### 2. Réutilisabilité
- Les méthodes du modèle peuvent être utilisées par d'autres contrôleurs
- La logique d'envoi d'emails est centralisée dans le contrôleur

### 3. Testabilité
- Chaque classe peut être testée indépendamment
- Les méthodes sont plus petites et plus faciles à tester

### 4. Maintenance
- Le code est plus organisé et plus facile à maintenir
- Les modifications sont localisées dans les bonnes classes

### 5. Extensibilité
- Facile d'ajouter de nouvelles fonctionnalités
- Structure claire pour l'évolution du code

## Migration

### Étape 1 : Utiliser le nouveau système
1. Remplacer l'inclusion de `traitements_liste_utilisateurs.php` par `traitements_liste_utilisateurs_mvc.php`
2. Les variables restent les mêmes, donc le HTML n'a pas besoin d'être modifié

### Étape 2 : Mise à jour progressive
1. Commencer par utiliser le nouveau contrôleur pour les nouvelles fonctionnalités
2. Migrer progressivement les fonctionnalités existantes
3. Supprimer l'ancien code une fois la migration terminée

### Étape 3 : Tests
1. Tester toutes les fonctionnalités avec le nouveau système
2. Vérifier que les emails sont envoyés correctement
3. S'assurer que les transactions de base de données fonctionnent

## Exemple d'utilisation

```php
// Dans un fichier de traitement
require_once '../../app/Controllers/UtilisateurController.php';

$utilisateurController = new UtilisateurController($pdo);

// Ajouter un utilisateur
$result = $utilisateurController->addUser('user@example.com', 1);
if ($result['success']) {
    $_SESSION['success'] = $result['message'];
} else {
    $_SESSION['error'] = $result['message'];
}

// Récupérer les utilisateurs avec filtres
$filters = ['type' => 1, 'statut' => 'Actif'];
$utilisateurs_data = $utilisateurController->getUtilisateursWithFilters(1, 75, $filters);
$utilisateurs = $utilisateurs_data['utilisateurs'];
```

## Notes importantes

1. **Gestion des erreurs** : Toutes les méthodes retournent des tableaux avec des indicateurs de succès
2. **Transactions** : Les opérations critiques utilisent des transactions de base de données
3. **Emails** : L'envoi d'emails est intégré dans les méthodes du contrôleur
4. **Sécurité** : Les mots de passe sont hashés avec SHA-256
5. **Compatibilité** : Les anciennes méthodes sont conservées pour la compatibilité

## Prochaines étapes

1. Tester le nouveau système en parallèle de l'ancien
2. Migrer progressivement les autres fonctionnalités
3. Ajouter des tests unitaires pour les nouvelles classes
4. Documenter les nouvelles méthodes
5. Former l'équipe sur la nouvelle architecture 