<?php
namespace App\Models;

use PDO;
use PDOException;
use Exception;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class Sauvegarde {
    private $db;
    private $backupDir;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->backupDir = __DIR__ . '/../../../storage/sauvegardes/';
        
        // Créer le répertoire s'il n'existe pas
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    public function getAllSauvegardes() {
        $sql = "SELECT * FROM sauvegardes ORDER BY date_creation DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createBackup($data) {
        try {
            $this->db->beginTransaction();
            
            $nom = $data['name'] ?? 'Sauvegarde_' . date('Y-m-d_H-i-s');
            $description = $data['description'] ?? '';
            $includeFiles = $data['include_files'] ?? false;
            $includeAudit = $data['include_audit'] ?? false;
            
            // Créer le fichier de sauvegarde
            $filename = $nom . '_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $this->backupDir . $filename;
            
            // Générer le dump SQL
            $this->generateDatabaseDump($filepath, $includeAudit);
            
            // Ajouter les fichiers si demandé
            if ($includeFiles) {
                $this->addFilesToBackup($filepath);
            }
            
            // Compresser le fichier
            $zipPath = $filepath . '.zip';
            $this->compressBackup($filepath, $zipPath);
            
            // Supprimer le fichier SQL non compressé
            unlink($filepath);
            
            // Enregistrer dans la base de données
            $sql = "INSERT INTO sauvegardes (nom_sauvegarde, description_sauvegarde, chemin_fichier, taille_fichier, date_creation, id_utilisateur_creation) 
                    VALUES (?, ?, ?, ?, NOW(), ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $nom,
                $description,
                $zipPath,
                filesize($zipPath),
                $_SESSION['user_id'] ?? 1
            ]);
            
            $this->db->commit();
            return $this->db->lastInsertId();
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors de la création de la sauvegarde: " . $e->getMessage());
            return false;
        }
    }

    public function restoreBackup($backupId, $options = []) {
        try {
            $this->db->beginTransaction();
            
            // Récupérer les informations de la sauvegarde
            $sql = "SELECT * FROM sauvegardes WHERE id_sauvegarde = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$backupId]);
            $backup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$backup) {
                throw new Exception("Sauvegarde non trouvée");
            }
            
            $overwrite = $options['overwrite'] ?? false;
            $includeFiles = $options['include_files'] ?? false;
            
            // Décompresser la sauvegarde
            $tempDir = $this->backupDir . 'temp_' . uniqid();
            mkdir($tempDir);
            
            $zip = new ZipArchive();
            if ($zip->open($backup['chemin_fichier']) === TRUE) {
                $zip->extractTo($tempDir);
                $zip->close();
            }
            
            // Restaurer la base de données
            $sqlFile = glob($tempDir . '/*.sql')[0] ?? null;
            if ($sqlFile) {
                $this->restoreDatabaseFromFile($sqlFile, $overwrite);
            }
            
            // Restaurer les fichiers si demandé
            if ($includeFiles && is_dir($tempDir . '/files')) {
                $this->restoreFilesFromBackup($tempDir . '/files');
            }
            
            // Nettoyer
            $this->removeDirectory($tempDir);
            
            // Enregistrer la restauration
            $sql = "INSERT INTO restaurations (id_sauvegarde, date_restauration, id_utilisateur_restauration, options_restauration) 
                    VALUES (?, NOW(), ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $backupId,
                $_SESSION['user_id'] ?? 1,
                json_encode($options)
            ]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors de la restauration: " . $e->getMessage());
            return false;
        }
    }

    public function deleteBackup($backupId) {
        try {
            $this->db->beginTransaction();
            
            // Récupérer le chemin du fichier
            $sql = "SELECT chemin_fichier FROM sauvegardes WHERE id_sauvegarde = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$backupId]);
            $backup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($backup && file_exists($backup['chemin_fichier'])) {
                unlink($backup['chemin_fichier']);
            }
            
            // Supprimer de la base de données
            $sql = "DELETE FROM sauvegardes WHERE id_sauvegarde = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$backupId]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors de la suppression de la sauvegarde: " . $e->getMessage());
            return false;
        }
    }

    public function downloadBackup($backupId) {
        $sql = "SELECT * FROM sauvegardes WHERE id_sauvegarde = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$backupId]);
        $backup = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($backup && file_exists($backup['chemin_fichier'])) {
            return $backup['chemin_fichier'];
        }
        
        return false;
    }

    public function getBackupStatistics() {
        $stats = [];
        
        // Total sauvegardes
        $stmt = $this->db->query("SELECT COUNT(*) FROM sauvegardes");
        $stats['total'] = $stmt->fetchColumn();
        
        // Taille totale
        $stmt = $this->db->query("SELECT SUM(taille_fichier) FROM sauvegardes");
        $stats['taille_totale'] = $stmt->fetchColumn() ?? 0;
        
        // Plus récente
        $stmt = $this->db->query("SELECT MAX(date_creation) FROM sauvegardes");
        $stats['plus_recente'] = $stmt->fetchColumn();
        
        // Plus ancienne
        $stmt = $this->db->query("SELECT MIN(date_creation) FROM sauvegardes");
        $stats['plus_ancienne'] = $stmt->fetchColumn();
        
        return $stats;
    }

    public function getBackupSettings() {
        // Récupérer les paramètres de sauvegarde automatique
        $sql = "SELECT * FROM parametres_sauvegarde WHERE id = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateBackupSettings($settings) {
        try {
            $sql = "INSERT INTO parametres_sauvegarde (id, frequence, jour_semaine, heure_sauvegarde, retention, emplacement_stockage, ftp_host, ftp_user, ftp_pass, date_modification) 
                    VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE 
                    frequence = VALUES(frequence),
                    jour_semaine = VALUES(jour_semaine),
                    heure_sauvegarde = VALUES(heure_sauvegarde),
                    retention = VALUES(retention),
                    emplacement_stockage = VALUES(emplacement_stockage),
                    ftp_host = VALUES(ftp_host),
                    ftp_user = VALUES(ftp_user),
                    ftp_pass = VALUES(ftp_pass),
                    date_modification = NOW()";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $settings['frequency'] ?? 'weekly',
                $settings['day_of_week'] ?? 3,
                $settings['backup_time'] ?? '03:00',
                $settings['retention'] ?? 2,
                $settings['storage_location'] ?? 'local',
                $settings['ftp_host'] ?? '',
                $settings['ftp_user'] ?? '',
                $settings['ftp_pass'] ?? ''
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Erreur lors de la mise à jour des paramètres: " . $e->getMessage());
            return false;
        }
    }

    public function testBackupConnection() {
        // Tester la connexion FTP si configurée
        $settings = $this->getBackupSettings();
        
        if ($settings['emplacement_stockage'] === 'ftp' && !empty($settings['ftp_host'])) {
            try {
                $ftp = ftp_connect($settings['ftp_host']);
                if ($ftp && ftp_login($ftp, $settings['ftp_user'], $settings['ftp_pass'])) {
                    ftp_close($ftp);
                    return ['success' => true, 'message' => 'Connexion FTP réussie'];
                }
                return ['success' => false, 'message' => 'Échec de la connexion FTP'];
            } catch (Exception $e) {
                return ['success' => false, 'message' => 'Erreur FTP: ' . $e->getMessage()];
            }
        }
        
        return ['success' => true, 'message' => 'Stockage local - pas de test nécessaire'];
    }

    public function getBackupLogs() {
        $sql = "SELECT r.*, s.nom_sauvegarde 
                FROM restaurations r 
                JOIN sauvegardes s ON r.id_sauvegarde = s.id_sauvegarde 
                ORDER BY r.date_restauration DESC 
                LIMIT 50";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Méthodes privées utilitaires
    private function generateDatabaseDump($filepath, $includeAudit = false) {
        $tables = $this->db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        $dump = "-- Sauvegarde de la base de données\n";
        $dump .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            // Exclure les tables d'audit si non demandé
            if (!$includeAudit && in_array($table, ['pister', 'audit_logs'])) {
                continue;
            }
            
            $dump .= $this->getTableDump($table);
        }
        
        file_put_contents($filepath, $dump);
    }

    private function getTableDump($table) {
        $dump = "DROP TABLE IF EXISTS `$table`;\n";
        
        // Structure
        $createTable = $this->db->query("SHOW CREATE TABLE `$table`")->fetch();
        $dump .= $createTable[1] . ";\n\n";
        
        // Données
        $rows = $this->db->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $columns = array_keys($rows[0]);
            $dump .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES\n";
            
            $values = [];
            foreach ($rows as $row) {
                $rowValues = array_map(function($value) {
                    return $value === null ? 'NULL' : $this->db->quote($value);
                }, $row);
                $values[] = "(" . implode(', ', $rowValues) . ")";
            }
            
            $dump .= implode(",\n", $values) . ";\n\n";
        }
        
        return $dump;
    }

    private function addFilesToBackup($filepath) {
        $filesDir = __DIR__ . '/../../../storage/uploads/';
        if (is_dir($filesDir)) {
            // Ajouter les fichiers au zip
            $zip = new ZipArchive();
            $zipPath = $filepath . '.zip';
            $zip->open($zipPath, ZipArchive::CREATE);
            $zip->addFile($filepath, basename($filepath));
            
            $this->addDirectoryToZip($zip, $filesDir, 'files/');
            $zip->close();
        }
    }

    private function addDirectoryToZip($zip, $dir, $zipPath) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $zipPath . substr($filePath, strlen($dir));
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    private function compressBackup($source, $destination) {
        $zip = new ZipArchive();
        if ($zip->open($destination, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($source, basename($source));
            $zip->close();
            return true;
        }
        return false;
    }

    private function restoreDatabaseFromFile($sqlFile, $overwrite = false) {
        $sql = file_get_contents($sqlFile);
        $statements = explode(';', $sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $this->db->exec($statement);
                } catch (PDOException $e) {
                    if (!$overwrite) {
                        throw $e;
                    }
                }
            }
        }
    }

    private function restoreFilesFromBackup($filesDir) {
        $targetDir = __DIR__ . '/../../../storage/uploads/';
        if (is_dir($filesDir)) {
            $this->copyDirectory($filesDir, $targetDir);
        }
    }

    private function copyDirectory($source, $destination) {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $dir = opendir($source);
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $sourcePath = $source . '/' . $file;
                $destPath = $destination . '/' . $file;
                
                if (is_dir($sourcePath)) {
                    $this->copyDirectory($sourcePath, $destPath);
                } else {
                    copy($sourcePath, $destPath);
                }
            }
        }
        closedir($dir);
    }

    private function removeDirectory($dir) {
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), array('.', '..'));
            foreach ($files as $file) {
                $path = $dir . '/' . $file;
                if (is_dir($path)) {
                    $this->removeDirectory($path);
                } else {
                    unlink($path);
                }
            }
            rmdir($dir);
        }
    }
}
