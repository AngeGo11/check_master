<?php

require_once __DIR__ . '/../Models/CompteRendu.php';
use App\Models\CompteRendu;

class CompteRenduController {
    private $model;

    public function __construct($db) {
        $this->model = new CompteRendu($db);
    }

    public function index() {
        return $this->model->getAllCompteRendus();
    }

    public function indexWithAuthor() {
        return $this->model->getCompteRendusWithStudents();
    }

    public function show($id) {
        return $this->model->getCompteRenduById($id);
    }

    public function store($data) {
        // Validation des données
        if (empty($data['titre'])) {
            return false;
        }

        // Utiliser la méthode createCompteRendu qui gère mieux les erreurs
        return $this->createCompteRendu(
            $data['titre'], 
            $data['date'], 
            $data['auteur'] ?? null, 
            $data['fichier_path'] ?? '',
            $data['rapport_ids'] ?? []
        );
    }

    public function update($id, $data) {
        // Validation des données
        if (empty($data['titre']) || empty($data['contenu'])) {
            return false;
        }

        return $this->model->modifierCompteRendu(
            $id,
            $data['titre'], 
            $data['contenu'], 
            $data['date'], 
            $data['auteur'] ?? null, 
            $data['fichier_path'] ?? ''
        );
    }

    public function delete($id) {
        return $this->model->supprimerCompteRendu($id);
    }

    // Méthode pour récupérer les rapports validés ou rejetés
    public function getRapportsValidesOuRejetes() {
        $sql = "SELECT DISTINCT re.*, e.nom_etd, e.prenom_etd, v.date_validation, 
                d.date_depot, a.date_approbation, a.com_appr
                FROM rapport_etudiant re
                LEFT JOIN etudiants e ON e.num_etd = re.num_etd
                LEFT JOIN deposer d ON d.id_rapport_etd = re.id_rapport_etd
                LEFT JOIN valider v ON v.id_rapport_etd = re.id_rapport_etd
                LEFT JOIN approuver a ON a.id_rapport_etd = re.id_rapport_etd
                WHERE re.statut_rapport IN ('Validé', 'Rejeté')
                ORDER BY re.date_rapport DESC";
        
        $stmt = $this->model->getDb()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Méthode pour vérifier si l'utilisateur est responsable de compte rendu
    public function getResponsableCompteRendu($user_id) {
        // Vérifier si l'utilisateur a les droits pour créer des comptes rendus
        // Utiliser la table enseignants au lieu de users
        $sql = "SELECT COUNT(*) as count FROM enseignants WHERE id_ens = :user_id";
        $stmt = $this->model->getDb()->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }


    // Méthode pour récupérer le responsable d'un compte rendu
    public function getResponsableCr($idCr) {
        return $this->model->getResponsableCompteRenduByCompteRenduId($idCr);
    }


    // Méthode pour vérifier si un compte rendu est disponible pour un étudiant
    public function isCompteRenduDisponible($idEtudiant, $idCr) {
        return $this->model->isCompteRenduDisponiblePourEtudiant($idEtudiant, $idCr);
    }

    // Méthode pour récupérer un compte rendu par rapport
    public function getCompteRenduByRapport($rapport_id) {
        $sql = "SELECT cr.*, r.id_rapport_etd 
                FROM compte_rendu cr
                JOIN rendre rn ON rn.id_cr = cr.id_cr
                JOIN enseignants ens ON ens.id_ens = rn.id_ens
                JOIN valider v ON v.id_ens = ens.id_ens
                JOIN rapport_etudiant r ON r.id_rapport_etd = v.id_rapport_etd
                WHERE r.id_rapport_etd = :rapport_id
                ORDER BY cr.date_cr DESC
                LIMIT 1";
        
        $stmt = $this->model->getDb()->prepare($sql);
        $stmt->bindParam(':rapport_id', $rapport_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Méthode pour créer un compte rendu avec gestion des rapports associés
    public function createCompteRendu($nomCr,  $dateCr, $auteur_id, $fichier_path, $rapport_ids = []) {
        try {
            // Le modèle gère déjà les transactions
            $compte_rendu_id = $this->model->ajouterCompteRendu($nomCr, $dateCr, $auteur_id, $fichier_path, $rapport_ids);
            
            if ($compte_rendu_id) {
                return $compte_rendu_id;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Erreur création compte rendu: " . $e->getMessage());
            return false;
        }
    }

    // Méthode pour récupérer les rapports associés à un compte rendu
    public function getRapportsAssocies($compte_rendu_id) {
        return $this->model->getRapportsAssocies($compte_rendu_id);
    }

    // Méthode pour récupérer les IDs des rapports associés
    public function getRapportIdsAssocies($compte_rendu_id) {
        return $this->model->getRapportIdsAssocies($compte_rendu_id);
    }

    // Méthode pour mettre à jour les associations de rapports
    public function mettreAJourAssociationsRapports($compte_rendu_id, $nouveaux_rapport_ids) {
        return $this->model->mettreAJourAssociationsRapports($compte_rendu_id, $nouveaux_rapport_ids);
    }

    // Méthode pour récupérer les informations d'un enseignant
    public function getEnseignantInfo($enseignant_id) {
        $sql = "SELECT id_ens, nom_ens, prenoms_ens, email_ens FROM enseignants WHERE id_ens = :id";
        $stmt = $this->model->getDb()->prepare($sql);
        $stmt->bindParam(':id', $enseignant_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Méthode pour ajouter un enseignant à la table rendre (association compte rendu - enseignant)
    public function ajouterEnseignantAuCompteRendu($compte_rendu_id, $enseignant_id) {
        try {
            $sql = "INSERT INTO rendre (id_cr, id_ens, date_env) VALUES (:id_cr, :id_ens, NOW())";
            $stmt = $this->model->getDb()->prepare($sql);
            $stmt->bindParam(':id_cr', $compte_rendu_id);
            $stmt->bindParam(':id_ens', $enseignant_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur ajout enseignant au compte rendu: " . $e->getMessage());
            return false;
        }
    }

    // Méthode pour récupérer les enseignants associés à un compte rendu
    public function getEnseignantsAssocies($compte_rendu_id) {
        try {
            $sql = "SELECT r.*, e.nom_ens, e.prenoms_ens, e.email_ens 
                    FROM rendre r
                    LEFT JOIN enseignants e ON r.id_ens = e.id_ens
                    WHERE r.id_cr = :id_cr";
            $stmt = $this->model->getDb()->prepare($sql);
            $stmt->bindParam(':id_cr', $compte_rendu_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération enseignants associés: " . $e->getMessage());
            return [];
        }
    }
} 
