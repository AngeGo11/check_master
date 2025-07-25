# Contrôleurs et Modèles Créés

## Vue d'ensemble

Ce document résume les contrôleurs et modèles créés ou mis à jour pour les modules suivants :
- **Ressources Humaines** (Enseignants et Personnel Administratif)
- **Sauvegardes et Restaurations**
- **Piste d'Audit**

## 1. Contrôleurs

### 1.1 EnseignantController.php (Mis à jour)
**Fichier :** `app/Controllers/EnseignantController.php`

**Méthodes disponibles :**
- `index()` - Récupère tous les enseignants
- `show($id)` - Récupère un enseignant par ID
- `store($data)` - Crée un nouvel enseignant
- `update($id, $data)` - Met à jour un enseignant
- `delete($id)` - Supprime un enseignant
- `getStatistics()` - Récupère les statistiques des enseignants
- `getEnseignantsWithPagination($page, $limit)` - Pagination des enseignants
- `searchEnseignants($search, $filters)` - Recherche d'enseignants
- `getGrades()` - Récupère tous les grades
- `getFonctions()` - Récupère toutes les fonctions
- `getSpecialites()` - Récupère toutes les spécialités

### 1.2 PersonnelAdministratifController.php (Mis à jour)
**Fichier :** `app/Controllers/PersonnelAdministratifController.php`

**Méthodes disponibles :**
- `index()` - Récupère tout le personnel administratif
- `show($id)` - Récupère un membre du personnel par ID
- `store($data)` - Crée un nouveau membre du personnel
- `update($id, $data)` - Met à jour un membre du personnel
- `delete($id)` - Supprime un membre du personnel
- `getStatistics()` - Récupère les statistiques du personnel
- `getPersonnelWithPagination($page, $limit)` - Pagination du personnel
- `searchPersonnel($search, $filters)` - Recherche de personnel
- `getGroupes()` - Récupère tous les groupes utilisateur

### 1.3 SauvegardesEtRestaurationsController.php (Créé)
**Fichier :** `app/Controllers/SauvegardesEtRestaurationsController.php`

**Méthodes disponibles :**
- `index()` - Récupère toutes les sauvegardes
- `createBackup($data)` - Crée une nouvelle sauvegarde
- `restoreBackup($backupId, $options)` - Restaure une sauvegarde
- `deleteBackup($backupId)` - Supprime une sauvegarde
- `downloadBackup($backupId)` - Télécharge une sauvegarde
- `getBackupStatistics()` - Récupère les statistiques des sauvegardes
- `getBackupSettings()` - Récupère les paramètres de sauvegarde
- `updateBackupSettings($settings)` - Met à jour les paramètres
- `testBackupConnection()` - Teste la connexion de sauvegarde
- `getBackupLogs()` - Récupère les logs de sauvegarde

### 1.4 PisteAuditController.php (Créé)
**Fichier :** `app/Controllers/PisteAuditController.php`

**Méthodes disponibles :**
- `index()` - Récupère tous les enregistrements d'audit
- `getAuditRecordsWithFilters($filters)` - Récupère avec filtres
- `getAuditStatistics()` - Récupère les statistiques d'audit
- `getAuditRecordsWithPagination($page, $limit, $filters)` - Pagination
- `searchAuditRecords($search, $filters)` - Recherche d'enregistrements
- `getAuditRecordById($id)` - Récupère un enregistrement par ID
- `exportAuditData($format, $filters)` - Export des données d'audit
- `getAuditLogsByUser($userId)` - Logs par utilisateur
- `getAuditLogsByModule($module)` - Logs par module
- `getAuditLogsByDateRange($startDate, $endDate)` - Logs par période
- `getAvailableActions()` - Actions disponibles
- `getAvailableModules()` - Modules disponibles
- `getAvailableUserTypes()` - Types d'utilisateurs disponibles

## 2. Modèles

### 2.1 Enseignant.php (Mis à jour)
**Fichier :** `app/Models/Enseignant.php`

**Nouvelles méthodes ajoutées :**
- `getStatistics()` - Statistiques des enseignants
- `getEnseignantsWithPagination($page, $limit)` - Pagination
- `searchEnseignants($search, $filters)` - Recherche avec filtres
- `getGrades()` - Liste des grades
- `getFonctions()` - Liste des fonctions
- `getSpecialites()` - Liste des spécialités

### 2.2 PersonnelAdministratif.php (Mis à jour)
**Fichier :** `app/Models/PersonnelAdministratif.php`

**Nouvelles méthodes ajoutées :**
- `getStatistics()` - Statistiques du personnel
- `getPersonnelWithPagination($page, $limit)` - Pagination
- `searchPersonnel($search, $filters)` - Recherche avec filtres
- `getGroupes()` - Liste des groupes utilisateur

### 2.3 Sauvegarde.php (Créé)
**Fichier :** `app/Models/Sauvegarde.php`

**Méthodes principales :**
- `getAllSauvegardes()` - Récupère toutes les sauvegardes
- `createBackup($data)` - Crée une sauvegarde complète
- `restoreBackup($backupId, $options)` - Restaure une sauvegarde
- `deleteBackup($backupId)` - Supprime une sauvegarde
- `downloadBackup($backupId)` - Télécharge une sauvegarde
- `getBackupStatistics()` - Statistiques des sauvegardes
- `getBackupSettings()` - Paramètres de sauvegarde
- `updateBackupSettings($settings)` - Met à jour les paramètres
- `testBackupConnection()` - Test de connexion
- `getBackupLogs()` - Logs de sauvegarde

**Méthodes utilitaires privées :**
- `generateDatabaseDump()` - Génère le dump SQL
- `getTableDump()` - Dump d'une table spécifique
- `addFilesToBackup()` - Ajoute les fichiers à la sauvegarde
- `compressBackup()` - Compresse la sauvegarde
- `restoreDatabaseFromFile()` - Restaure depuis un fichier
- `restoreFilesFromBackup()` - Restaure les fichiers

### 2.4 Audit.php (Mis à jour)
**Fichier :** `app/Models/Audit.php`

**Nouvelles méthodes ajoutées :**
- `getAllAuditRecords()` - Tous les enregistrements d'audit
- `getAuditRecordsWithFilters($filters)` - Avec filtres
- `getAuditStatistics()` - Statistiques d'audit
- `getAuditRecordsWithPagination($page, $limit, $filters)` - Pagination
- `searchAuditRecords($search, $filters)` - Recherche
- `getAuditRecordById($id)` - Par ID
- `exportAuditData($format, $filters)` - Export
- `getAuditLogsByUser($userId)` - Par utilisateur
- `getAuditLogsByModule($module)` - Par module
- `getAuditLogsByDateRange($startDate, $endDate)` - Par période
- `getAvailableActions()` - Actions disponibles
- `getAvailableModules()` - Modules disponibles
- `getAvailableUserTypes()` - Types d'utilisateurs

**Méthodes d'export privées :**
- `exportToCSV($records)` - Export CSV
- `exportToJSON($records)` - Export JSON
- `exportToPDF($records)` - Export PDF (à implémenter)

## 3. Utilisation

### 3.1 Exemple d'utilisation des contrôleurs

```php
// Initialisation
$enseignantController = new EnseignantController($pdo);
$personnelController = new PersonnelAdministratifController($pdo);
$sauvegardeController = new SauvegardesEtRestaurationsController($pdo);
$auditController = new PisteAuditController($pdo);

// Récupération des données
$enseignants = $enseignantController->index();
$statistiques = $enseignantController->getStatistics();
$sauvegardes = $sauvegardeController->index();
$auditRecords = $auditController->getAuditRecordsWithFilters([
    'date_debut' => '2025-01-01',
    'date_fin' => '2025-12-31'
]);
```

### 3.2 Exemple de création de sauvegarde

```php
$sauvegardeController = new SauvegardesEtRestaurationsController($pdo);

$result = $sauvegardeController->createBackup([
    'name' => 'Sauvegarde-Mensuelle',
    'description' => 'Sauvegarde automatique mensuelle',
    'include_files' => true,
    'include_audit' => false
]);
```

### 3.3 Exemple de recherche d'audit

```php
$auditController = new PisteAuditController($pdo);

$records = $auditController->searchAuditRecords('connexion', [
    'date_debut' => '2025-01-01',
    'type_utilisateur' => 'Enseignant'
]);
```

## 4. Notes importantes

1. **Sécurité** : Tous les contrôleurs incluent des validations de base et une gestion d'erreurs
2. **Performance** : Les requêtes utilisent des requêtes préparées pour éviter les injections SQL
3. **Flexibilité** : Les méthodes de filtrage et de pagination permettent une utilisation flexible
4. **Extensibilité** : La structure permet d'ajouter facilement de nouvelles fonctionnalités

## 5. Tables de base de données requises

### 5.1 Pour les sauvegardes
```sql
CREATE TABLE sauvegardes (
    id_sauvegarde INT PRIMARY KEY AUTO_INCREMENT,
    nom_sauvegarde VARCHAR(255) NOT NULL,
    description_sauvegarde TEXT,
    chemin_fichier VARCHAR(500) NOT NULL,
    taille_fichier BIGINT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_utilisateur_creation INT
);

CREATE TABLE restaurations (
    id_restauration INT PRIMARY KEY AUTO_INCREMENT,
    id_sauvegarde INT,
    date_restauration DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_utilisateur_restauration INT,
    options_restauration JSON,
    FOREIGN KEY (id_sauvegarde) REFERENCES sauvegardes(id_sauvegarde)
);

CREATE TABLE parametres_sauvegarde (
    id INT PRIMARY KEY DEFAULT 1,
    frequence ENUM('daily', 'weekly', 'biweekly', 'monthly') DEFAULT 'weekly',
    jour_semaine INT DEFAULT 3,
    heure_sauvegarde TIME DEFAULT '03:00:00',
    retention INT DEFAULT 2,
    emplacement_stockage ENUM('local', 'cloud', 'ftp') DEFAULT 'local',
    ftp_host VARCHAR(255),
    ftp_user VARCHAR(255),
    ftp_pass VARCHAR(255),
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 5.2 Tables existantes utilisées
- `enseignants`
- `personnel_administratif`
- `pister` (pour l'audit)
- `traitement`
- `action`
- `utilisateur`
- `grade`
- `fonction`
- `specialite`
- `groupe_utilisateur` 