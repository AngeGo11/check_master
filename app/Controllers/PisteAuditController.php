<?php
require_once __DIR__ . '/../Models/Audit.php';

use App\Models\Audit;

class PisteAuditController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new Audit($db);
    }

    public function index() {
        return $this->model->getAllAuditRecords();
    }

    public function getAuditRecordsWithFilters($filters = []) {
        return $this->model->getAuditRecordsWithFilters($filters);
    }

    public function getAuditStatistics() {
        return $this->model->getAuditStatistics();
    }

    public function getAuditRecordsWithPagination($page = 1, $limit = 10, $filters = []) {
        return $this->model->getAuditRecordsWithPagination($page, $limit, $filters);
    }

    public function searchAuditRecords($search, $filters = []) {
        return $this->model->searchAuditRecords($search, $filters);
    }

    public function getAuditRecordById($id) {
        return $this->model->getAuditRecordById($id);
    }

    public function exportAuditData($format = 'csv', $filters = []) {
        return $this->model->exportAuditData($format, $filters);
    }

    public function getAuditLogsByUser($userId) {
        return $this->model->getAuditLogsByUser($userId);
    }

    public function getAuditLogsByModule($module) {
        return $this->model->getAuditLogsByModule($module);
    }

    public function getAuditLogsByDateRange($startDate, $endDate) {
        return $this->model->getAuditLogsByDateRange($startDate, $endDate);
    }

    public function getAvailableActions() {
        return $this->model->getAvailableActions();
    }

    public function getAvailableModules() {
        return $this->model->getAvailableModules();
    }

    public function getAvailableUserTypes() {
        return $this->model->getAvailableUserTypes();
    }

    public function getAuditSummary($period = 'week') {
        return $this->model->getAuditSummary($period);
    }

    public function getUserActivity($user_id, $period = 'week') {
        return $this->model->getUserActivity($user_id, $period);
    }

    public function getModuleActivity($module, $period = 'week') {
        return $this->model->getModuleActivity($module, $period);
    }

    public function clearOldLogs($days = 90) {
        return $this->model->clearOldLogs($days);
    }
} 
