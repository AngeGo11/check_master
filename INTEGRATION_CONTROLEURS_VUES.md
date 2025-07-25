# Intégration des Contrôleurs dans les Vues

## Vue d'ensemble

Ce document décrit l'intégration des contrôleurs et modèles dans les vues correspondantes du projet GSCV+. L'objectif est de remplacer les requêtes SQL directes par des appels aux contrôleurs, améliorant ainsi la maintenabilité et la structure du code.

## Modifications apportées

### 1. Vue `ressources_humaines.php`

**Avant :**
```php
// Requêtes SQL directes
$query_total_enseignants = "SELECT COUNT(*) as total FROM enseignants";
$total_enseignants = $pdo->query($query_total_enseignants)->fetch()['total'] ?? 0;
```

**Après :**
```php
// Utilisation des contrôleurs
$enseignantController = new EnseignantController($pdo);
$stats_enseignants = $enseignantController->getStatistics();
$total_enseignants = $stats_enseignants['total'];
```

**Fonctionnalités intégrées :**
- Statistiques des enseignants et personnel administratif
- Pagination des listes
- Recherche et filtrage
- Gestion des grades, fonctions et spécialités

### 2. Vue `sauvegardes_et_restaurations.php`

**Avant :**
```php
// Requête directe
$stmt = $pdo->query("SELECT * FROM sauvegardes ORDER BY date_creation DESC");
$backups = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

**Après :**
```php
// Utilisation du contrôleur
$sauvegardeController = new SauvegardesEtRestaurationsController($pdo);
$backups = $sauvegardeController->index();
```

**Fonctionnalités intégrées :**
- Liste des sauvegardes
- Création de sauvegardes
- Restauration de sauvegardes
- Gestion des paramètres

### 3. Vue `piste_audit.php`

**Avant :**
```php
// Requêtes SQL complexes avec filtres
$sql = "SELECT p.*, t.lib_traitement, a.lib_action...";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$audit_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

**Après :**
```php
// Utilisation du contrôleur
$auditController = new PisteAuditController($pdo);
$audit_records = $auditController->getAuditRecordsWithPagination($page, $limit, $filters);
```

**Fonctionnalités intégrées :**
- Consultation des logs d'audit
- Filtrage et recherche
- Pagination
- Export des données
- Statistiques d'activité

## Fichiers de traitement créés

### 1. `traitements_ressources_humaines.php`

Gère toutes les actions CRUD pour les enseignants et le personnel administratif :

```php
// Exemple d'utilisation
$action = $_POST['action'] ?? '';
switch ($action) {
    case 'create_enseignant':
        $result = $enseignantController->store($data);
        break;
    case 'update_enseignant':
        $result = $enseignantController->update($id, $data);
        break;
    // ...
}
```

**Actions disponibles :**
- `create_enseignant` / `create_personnel`
- `update_enseignant` / `update_personnel`
- `delete_enseignant` / `delete_personnel`
- `search_enseignants` / `search_personnel`
- `get_enseignant` / `get_personnel`
- `get_statistics`

### 2. `traitements_piste_audit.php`

Gère les actions de consultation et d'export de la piste d'audit :

```php
// Exemple d'utilisation
switch ($action) {
    case 'get_audit_records':
        $result = $auditController->getAuditRecordsWithPagination($page, $limit, $filters);
        break;
    case 'export_audit':
        $result = $auditController->exportAuditData($filters, $format);
        break;
    // ...
}
```

**Actions disponibles :**
- `get_audit_records` - Récupération des logs avec pagination
- `get_statistics` - Statistiques d'activité
- `export_audit` - Export des données
- `get_available_actions` - Liste des actions disponibles
- `get_available_modules` - Liste des modules
- `get_user_types` - Types d'utilisateurs
- `clear_old_logs` - Nettoyage des anciens logs

## Fichiers JavaScript créés

### 1. `ressources_humaines.js`

Gère les interactions AJAX pour les ressources humaines :

```javascript
// Exemple de fonction
function searchEnseignants(search) {
    $.ajax({
        url: 'assets/traitements/traitements_ressources_humaines.php',
        method: 'GET',
        data: {
            action: 'search_enseignants',
            search: search
        },
        success: function(response) {
            if (response.success) {
                displayEnseignants(response.data);
            }
        }
    });
}
```

**Fonctionnalités :**
- Recherche en temps réel
- Filtrage dynamique
- Gestion des formulaires
- Suppression et modification
- Affichage des messages

### 2. `piste_audit.js`

Gère les interactions AJAX pour la piste d'audit :

```javascript
// Exemple de fonction
function loadAuditRecords(filters = {}) {
    $.ajax({
        url: 'assets/traitements/traitements_piste_audit.php',
        method: 'GET',
        data: {
            action: 'get_audit_records',
            page: getCurrentPage(),
            limit: $('#limit-select').val(),
            ...filters
        },
        success: function(response) {
            if (response.success) {
                displayAuditRecords(response.data);
            }
        }
    });
}
```

**Fonctionnalités :**
- Chargement des données avec filtres
- Recherche en temps réel
- Export des données
- Nettoyage des logs
- Mise à jour des statistiques
- Pagination dynamique

## Avantages de cette approche

### 1. Séparation des responsabilités
- **Vues** : Affichage uniquement
- **Contrôleurs** : Logique métier
- **Modèles** : Accès aux données

### 2. Réutilisabilité
- Les contrôleurs peuvent être utilisés par plusieurs vues
- Les modèles peuvent être utilisés par plusieurs contrôleurs

### 3. Maintenabilité
- Code plus organisé et structuré
- Modifications centralisées
- Tests plus faciles à écrire

### 4. Sécurité
- Validation centralisée dans les contrôleurs
- Protection contre les injections SQL
- Gestion des permissions

### 5. Performance
- Requêtes optimisées dans les modèles
- Cache possible au niveau des contrôleurs
- Pagination efficace

## Utilisation

### Dans une vue
```php
// Initialisation
require_once __DIR__ . '/../Controllers/EnseignantController.php';
$enseignantController = new EnseignantController($pdo);

// Récupération des données
$enseignants = $enseignantController->getEnseignantsWithPagination($page, $limit);
$stats = $enseignantController->getStatistics();
```

### Via AJAX
```javascript
// Appel AJAX
$.ajax({
    url: 'assets/traitements/traitements_ressources_humaines.php',
    method: 'POST',
    data: {
        action: 'create_enseignant',
        nom: 'Dupont',
        prenoms: 'Jean',
        email: 'jean.dupont@example.com'
    },
    success: function(response) {
        if (response.success) {
            showSuccess('Enseignant ajouté avec succès');
        }
    }
});
```

## Structure des réponses

### Succès
```json
{
    "success": true,
    "data": [...],
    "message": "Opération réussie"
}
```

### Erreur
```json
{
    "success": false,
    "message": "Message d'erreur"
}
```

## Prochaines étapes

1. **Tests** : Tester toutes les fonctionnalités intégrées
2. **Optimisation** : Optimiser les requêtes si nécessaire
3. **Documentation** : Compléter la documentation des API
4. **Sécurité** : Ajouter des validations supplémentaires
5. **Cache** : Implémenter un système de cache si nécessaire

## Notes importantes

- Tous les contrôleurs héritent de la même structure de base
- Les modèles utilisent PDO pour la sécurité
- Les erreurs sont gérées de manière centralisée
- Les messages de session sont utilisés pour le feedback utilisateur
- Les permissions sont vérifiées dans les contrôleurs 