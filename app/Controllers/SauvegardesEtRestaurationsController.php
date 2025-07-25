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

    public function createBackup($data) {
        return $this->model->createBackup($data);
    }

    public function restoreBackup($backupId, $options = []) {
        return $this->model->restoreBackup($backupId, $options);
    }

    public function deleteBackup($backupId) {
        return $this->model->deleteBackup($backupId);
    }

    public function downloadBackup($backupId) {
        return $this->model->downloadBackup($backupId);
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
