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
        return [
            'messages' => $this->model->getMessagesRecus($userId),
            'contacts' => $this->model->getContacts($userId),
            'messagesNonLus' => $this->model->compterMessagesNonLus($userId),
            'messagesArchives' => $this->model->getMessagesArchives($userId)
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
} 
