<?php
require_once __DIR__ . '/../config/database.php';

class DashboardSecretaireController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        // Vérification des permissions
        if (!isset($_SESSION['user_id'])) {
            header('Location: ../../public/pageConnexion.php');
            exit();
        }

        // Récupération des statistiques
        $stats = $this->getStats();
        
        // Récupération des activités récentes
        $activites = $this->getActivitesRecentes();
        
        // Récupération de l'évolution des effectifs
        $evolutionEffectifs = $this->getEvolutionEffectifs();

        return [
            'stats' => $stats,
            'activites' => $activites,
            'evolutionEffectifs' => $evolutionEffectifs
        ];
    }

    private function getStats() {
        $stats = [];
        
        // Nombre total d'étudiants
        $sql = "SELECT COUNT(*) as total FROM etudiant";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['total_etudiants'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Nombre de demandes de soutenance en attente
        $sql = "SELECT COUNT(*) as total FROM demande_soutenance WHERE statut = 'en_attente'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['demandes_en_attente'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Nombre de réclamations non traitées
        $sql = "SELECT COUNT(*) as total FROM reclamation WHERE statut = 'non_traitee'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['reclamations_non_traitees'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Nombre de messages non lus
        $sql = "SELECT COUNT(*) as total FROM message WHERE lu = 0 AND destinataire_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $stats['messages_non_lus'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return $stats;
    }

    private function getActivitesRecentes() {
        $sql = "SELECT 
                    'demande_soutenance' as type,
                    ds.date_demande as date,
                    CONCAT(e.nom, ' ', e.prenom) as etudiant,
                    ds.sujet,
                    ds.statut
                FROM demande_soutenance ds
                JOIN etudiant e ON ds.id_etudiant = e.id_etudiant
                WHERE ds.date_demande >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ORDER BY ds.date_demande DESC
                LIMIT 10";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getEvolutionEffectifs() {
        $sql = "SELECT 
                    YEAR(date_inscription) as annee,
                    COUNT(*) as effectif
                FROM etudiant
                WHERE date_inscription >= DATE_SUB(NOW(), INTERVAL 5 YEAR)
                GROUP BY YEAR(date_inscription)
                ORDER BY annee";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 
