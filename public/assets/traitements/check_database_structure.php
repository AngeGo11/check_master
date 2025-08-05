<?php
// Démarrer la session

require_once __DIR__ . '/../../../config/config.php';

// Établir la connexion à la base de données
$pdo = DataBase::getConnection();

/**
 * Vérifie si une colonne existe dans une table
 */
function columnExists($table, $column) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM $table LIKE ?");
        $stmt->execute([$column]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Vérifie si une table existe
 */
function tableExists($table) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Récupère les informations utilisateur selon le type
 */
function getUserInfo($userId, $userType) {
    global $pdo;
    
    $table = getUserTable($userType);
    $idField = getIdField($userType);
    
    if (!tableExists($table)) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE $idField = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Erreur récupération utilisateur: ' . $e->getMessage());
        return null;
    }
}

/**
 * Met à jour les informations utilisateur selon le type
 */
function updateUserInfo($userId, $userType, $data) {
    global $pdo;
    
    $table = getUserTable($userType);
    $idField = getIdField($userType);
    
    if (!tableExists($table)) {
        throw new Exception("Table $table n'existe pas");
    }
    
    // Construire la requête dynamiquement selon les colonnes disponibles
    $updateFields = [];
    $values = [];
    
    foreach ($data as $field => $value) {
        if (columnExists($table, $field)) {
            $updateFields[] = "$field = ?";
            $values[] = $value;
        }

    }
    
    if (empty($updateFields)) {
        throw new Exception("Aucune colonne valide trouvée pour la mise à jour");
    }
    
    $values[] = $userId;
    $sql = "UPDATE $table SET " . implode(', ', $updateFields) . " WHERE $idField = ?";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        return true;
    } catch (Exception $e) {
        error_log('Erreur mise à jour utilisateur: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Récupère la photo de profil selon le type d'utilisateur
 */
function getProfilePhoto($userId, $userType) {
    global $pdo;
    
    $table = getUserTable($userType);
    $idField = getIdField($userType);
    
    $photoField = getPhotoField($userType);
    
    if (!$photoField || !tableExists($table) || !columnExists($table, $photoField)) {
        return '/GSCV+/public/assets/images/default-profile.png';
    }
    
    try {
        $stmt = $pdo->prepare("SELECT $photoField FROM $table WHERE $idField = ?");
        $stmt->execute([$userId]);
        $photo = $stmt->fetchColumn();
        
        if ($photo && file_exists(__DIR__ . '/../../../' . $photo)) {
            return $photo;
        }
    } catch (Exception $e) {
        error_log('Erreur récupération photo: ' . $e->getMessage());
    }
    
    return '/GSCV+/public/assets/images/default-profile.png';
}

// Fonctions utilitaires
function getUserTable($userType) {
    switch ($userType) {
        case 'enseignant': return 'enseignants';
        case 'etudiant': return 'etudiants';
        case 'personnel_adm': return 'personnel_administratif';
        default: return 'utilisateur';
    }
}

function getIdField($userType) {
    switch ($userType) {
        case 'enseignant': return 'id_ens';
        case 'etudiant': return 'num_etd';
        case 'personnel_adm': return 'id_personnel_adm';
        default: return 'id_utilisateur';
    }
}

function getPhotoField($userType) {
    switch ($userType) {
        case 'enseignant': return 'photo_ens';
        case 'etudiant': return 'photo_etd';
        case 'personnel_adm': return 'photo_personnel_adm';
        default: return null; // La table utilisateur n'a pas de colonne photo
    }
}

function getPasswordField($userType) {
    switch ($userType) {
        case 'enseignant': return 'mdp_ens';
        case 'etudiant': return 'mdp_etd';
        case 'personnel_adm': return 'mdp_personnel_adm';
        default: return 'mdp_utilisateur';
    }
}

/**
 * Vérifie la structure complète de la base de données
 */
function checkDatabaseStructure() {
    $tables = [
        'utilisateur' => ['id_utilisateur', 'login_utilisateur', 'mdp_utilisateur', 'statut_utilisateur'],
        'enseignants' => ['id_ens', 'nom_ens', 'prenoms_ens', 'email_ens', 'num_tel_ens', 'photo_ens', 'mdp_ens'],
        'etudiants' => ['num_etd', 'nom_etd', 'prenom_etd', 'email_etd', 'num_tel_etd', 'adresse_etd', 'ville_etd', 'pays_etd', 'photo_etd', 'mdp_etd'],
        'personnel_administratif' => ['id_personnel_adm', 'nom_personnel_adm', 'prenoms_personnel_adm', 'email_personnel_adm', 'tel_personnel_adm', 'photo_personnel_adm', 'mdp_personnel_adm']
    ];
    
    $results = [];
    
    foreach ($tables as $table => $columns) {
        $results[$table] = [
            'exists' => tableExists($table),
            'columns' => []
        ];
        
        if ($results[$table]['exists']) {
            foreach ($columns as $column) {
                $results[$table]['columns'][$column] = columnExists($table, $column);
            }
        }
    }
    
    return $results;
}

// Si le fichier est appelé directement, retourner la structure
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    header('Content-Type: application/json');
    echo json_encode(checkDatabaseStructure());
}
?> 