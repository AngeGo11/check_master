<?php

use App\Models\Message;
require_once __DIR__ . '/../Models/Message.php';

class MessageController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new Message($db);
    }

    /**
     * Afficher la page des messages
     */
    public function index($userId) {
        // Récupération des paramètres de pagination et filtres
        $page_messages = isset($_GET['page_messages']) ? (int)$_GET['page_messages'] : 1;
        $page_contacts = isset($_GET['page_contacts']) ? (int)$_GET['page_contacts'] : 1;
        $page_non_lus = isset($_GET['page_non_lus']) ? (int)$_GET['page_non_lus'] : 1;
        $page_archives = isset($_GET['page_archives']) ? (int)$_GET['page_archives'] : 1;
        $limit_messages = 5; // Limite pour les messages (5 par page)
        $limit_contacts = 9; // Limite pour les contacts (comme demandé)
        
        // Filtres pour les messages
        $filters_messages = [
            'search' => $_GET['search'] ?? '',
            'filter_statut' => $_GET['filter_statut'] ?? '',
            'filter_priorite' => $_GET['filter_priorite'] ?? '',
            'filter_date' => $_GET['filter_date'] ?? ''
        ];
        
        // Filtres pour les contacts
        $filters_contacts = [
            'search_contact' => $_GET['search_contact'] ?? ''
        ];
        
        // Filtres pour les messages non lus et archivés
        $filters_non_lus = [
            'search' => $_GET['search_non_lus'] ?? '',
            'filter_priorite' => $_GET['filter_priorite_non_lus'] ?? '',
            'filter_date' => $_GET['filter_date_non_lus'] ?? ''
        ];
        
        $filters_archives = [
            'search' => $_GET['search_archives'] ?? '',
            'filter_priorite' => $_GET['filter_priorite_archives'] ?? '',
            'filter_date' => $_GET['filter_date_archives'] ?? ''
        ];
        
        // Récupération des données avec pagination
        $all_messages = $this->model->getMessagesRecus($userId);
        $total_messages = count($all_messages);
        $offset_messages = ($page_messages - 1) * $limit_messages;
        $messages_data = [
            'data' => array_slice($all_messages, $offset_messages, $limit_messages),
            'total_items' => $total_messages,
            'total_pages' => ceil($total_messages / $limit_messages),
            'current_page' => $page_messages,
            'items_per_page' => $limit_messages
        ];
        
        // Utiliser l'ancienne méthode pour les contacts avec pagination manuelle
        $all_contacts = $this->model->getContacts($userId);
        $total_contacts = count($all_contacts);
        $offset_contacts = ($page_contacts - 1) * $limit_contacts;
        $contacts_data = [
            'data' => array_slice($all_contacts, $offset_contacts, $limit_contacts),
            'total_items' => $total_contacts,
            'total_pages' => ceil($total_contacts / $limit_contacts),
            'current_page' => $page_contacts,
            'items_per_page' => $limit_contacts
        ];
        
        // Récupération des messages non lus avec pagination
        $messages_non_lus_data = $this->model->getMessagesNonLusWithPagination($userId, $page_non_lus, $limit_messages, $filters_non_lus);
        
        // Récupération des messages archivés avec pagination
        $messages_archives_data = $this->model->getMessagesArchivesWithPagination($userId, $page_archives, $limit_messages, $filters_archives);
        
        $messages_envoyes = $this->model->getMessagesEnvoyes($userId);
        $rappels = $this->model->getRappels($userId);
        $statistics = $this->model->getStatistics($userId);
        
        return [
            'messages' => $messages_data['data'],
            'messages_envoyes' => $messages_envoyes,
            'messages_non_lus' => $messages_non_lus_data['data'],
            'messages_archives' => $messages_archives_data['data'],
            'contacts' => $contacts_data['data'],
            'rappels' => $rappels,
            'statistics' => $statistics,
            'pagination_messages' => [
                'current_page' => $page_messages,
                'total_pages' => $messages_data['total_pages'],
                'total_items' => $messages_data['total_items'],
                'items_per_page' => $limit_messages
            ],
            'pagination_contacts' => [
                'current_page' => $page_contacts,
                'total_pages' => $contacts_data['total_pages'],
                'total_items' => $contacts_data['total_items'],
                'items_per_page' => $limit_contacts
            ],
            'pagination_non_lus' => [
                'current_page' => $page_non_lus,
                'total_pages' => $messages_non_lus_data['total_pages'],
                'total_items' => $messages_non_lus_data['total_items'],
                'items_per_page' => $limit_messages
            ],
            'pagination_archives' => [
                'current_page' => $page_archives,
                'total_pages' => $messages_archives_data['total_pages'],
                'total_items' => $messages_archives_data['total_items'],
                'items_per_page' => $limit_messages
            ],
            'filters_messages' => $filters_messages,
            'filters_contacts' => $filters_contacts,
            'filters_non_lus' => $filters_non_lus,
            'filters_archives' => $filters_archives
        ];
    }

    /**
     * Envoyer un message
     */
    public function sendMessage($data) {
        return $this->model->sendMessage($data);
    }

    /**
     * Récupérer un message par ID
     */
    public function getMessageById($messageId) {
        return $this->model->getMessageById($messageId);
    }

    /**
     * Marquer un message comme lu
     */
    public function markAsRead($messageId) {
        return $this->model->markAsRead($messageId);
    }

    /**
     * Archiver des messages
     */
    public function archiveMessages($messageIds) {
        return $this->model->archiveMessages($messageIds);
    }

    /**
     * Supprimer des messages
     */
    public function deleteMessages($messageIds) {
        return $this->model->deleteMessages($messageIds);
    }

    /**
     * Restaurer des messages
     */
    public function restoreMessages($messageIds) {
        return $this->model->restoreMessages($messageIds);
    }

    /**
     * Récupérer les contacts
     */
    public function getContacts($userId) {
        return $this->model->getContacts($userId);
    }

    /**
     * Compter les messages non lus
     */
    public function compterMessagesNonLus($userId) {
        return $this->model->compterMessagesNonLus($userId);
    }

    /**
     * Méthode de débogage pour vérifier les messages non lus
     */
    public function debugMessagesNonLus($userId) {
        return $this->model->debugMessagesNonLus($userId);
    }
} 
