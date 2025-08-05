<?php
require_once __DIR__ . '/../Models/Sauvegarde.php';

class SauvegardesEtRestaurationsController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new \App\Models\Sauvegarde($db);
    }

    public function index() {
        return $this->model->getAllSauvegardes();
    }

    public function createBackup($name, $type = 'full', $description = '') {
        try {
            $data = [
                'name' => $name,
                'description' => $description,
                'include_files' => ($type === 'full'),
                'include_audit' => ($type === 'full')
            ];
            
            $backupId = $this->model->createBackup($data);
            
            if ($backupId) {
                return [
                    'success' => true,
                    'message' => "Sauvegarde '$name' créée avec succès.",
                    'backup_id' => $backupId
                ];
            } else {
                return [
                    'success' => false,
                    'error' => "Erreur lors de la création de la sauvegarde."
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => "Erreur: " . $e->getMessage()
            ];
        }
    }

    public function restoreBackup($backupId, $options = []) {
        try {
            $result = $this->model->restoreBackup($backupId, $options);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => "Restauration effectuée avec succès."
                ];
            } else {
                return [
                    'success' => false,
                    'error' => "Erreur lors de la restauration."
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => "Erreur: " . $e->getMessage()
            ];
        }
    }

    public function restoreFromFile($file) {
        try {
            // Vérifier le type de fichier
            $allowedTypes = ['application/sql', 'text/sql', 'application/octet-stream'];
            if (!in_array($file['type'], $allowedTypes) && !str_ends_with($file['name'], '.sql')) {
                return [
                    'success' => false,
                    'error' => "Format de fichier non supporté. Utilisez un fichier .sql"
                ];
            }

            // Vérifier la taille (max 50MB)
            if ($file['size'] > 50 * 1024 * 1024) {
                return [
                    'success' => false,
                    'error' => "Le fichier est trop volumineux. Taille maximum: 50MB"
                ];
            }

            // Créer un dossier temporaire
            $tempDir = __DIR__ . '/../../../storage/sauvegardes/temp_' . uniqid();
            mkdir($tempDir, 0755, true);

            // Déplacer le fichier uploadé
            $tempFile = $tempDir . '/' . basename($file['name']);
            if (!move_uploaded_file($file['tmp_name'], $tempFile)) {
                return [
                    'success' => false,
                    'error' => "Erreur lors du téléchargement du fichier."
                ];
            }

            // Restaurer la base de données
            $this->model->restoreDatabaseFromFile($tempFile, true);

            // Nettoyer
            unlink($tempFile);
            rmdir($tempDir);

            return [
                'success' => true,
                'message' => "Restauration depuis le fichier effectuée avec succès."
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => "Erreur lors de la restauration: " . $e->getMessage()
            ];
        }
    }

    public function deleteBackup($filename) {
        try {
            // Trouver la sauvegarde par nom de fichier
            $sql = "SELECT id_sauvegarde FROM sauvegardes WHERE nom_sauvegarde = ?";
            $stmt = $this->model->getDb()->prepare($sql);
            $stmt->execute([$filename]);
            $backup = $stmt->fetch();

            if (!$backup) {
                return [
                    'success' => false,
                    'error' => "Sauvegarde non trouvée."
                ];
            }

            $result = $this->model->deleteBackup($backup['id_sauvegarde']);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => "Sauvegarde supprimée avec succès."
                ];
            } else {
                return [
                    'success' => false,
                    'error' => "Erreur lors de la suppression."
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => "Erreur: " . $e->getMessage()
            ];
        }
    }

    public function downloadBackup($filename) {
        try {
            // Trouver la sauvegarde par nom de fichier
            $sql = "SELECT * FROM sauvegardes WHERE nom_sauvegarde = ?";
            $stmt = $this->model->getDb()->prepare($sql);
            $stmt->execute([$filename]);
            $backup = $stmt->fetch();

            if (!$backup) {
                return [
                    'success' => false,
                    'error' => "Sauvegarde non trouvée."
                ];
            }

            $filepath = $this->model->downloadBackup($backup['nom_fichier']);
            
            if ($filepath && file_exists($filepath)) {
                // Envoyer le fichier pour téléchargement
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
                header('Content-Length: ' . filesize($filepath));
                header('Cache-Control: no-cache, must-revalidate');
                header('Expires: 0');
                
                readfile($filepath);
                exit();
            } else {
                return [
                    'success' => false,
                    'error' => "Fichier de sauvegarde non trouvé."
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => "Erreur: " . $e->getMessage()
            ];
        }
    }

    public function getBackupStatistics() {
        return $this->model->getBackupStatistics();
    }

    public function getBackupSettings() {
        return $this->model->getBackupSettings();
    }

    public function updateBackupSettings($settings) {
        return $this->model->updateBackupSettings($settings);
    }

    public function testBackupConnection() {
        return $this->model->testBackupConnection();
    }

    public function getBackupLogs() {
        return $this->model->getBackupLogs();
    }
} 
