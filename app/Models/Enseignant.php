<?php

namespace App\Models;

use PDO;
use PDOException;

class Enseignant
{
    protected $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Récupérer tous les enseignants avec leurs grades, fonctions, spécialités
    public function all()
    {
        $sql = "SELECT e.*, f.nom_fonction, f.id_fonction, g.nom_grd, g.id_grd, s.lib_spe,
                       a.date_grd, o.date_occup
                FROM enseignants e
                LEFT JOIN avoir a ON e.id_ens = a.id_ens
                LEFT JOIN grade g ON a.id_grd = g.id_grd
                LEFT JOIN occuper o ON e.id_ens = o.id_ens
                LEFT JOIN fonction f ON o.id_fonction = f.id_fonction
                LEFT JOIN enseignant_specialite es ON e.id_ens = es.id_ens
                LEFT JOIN specialite s ON es.id_spe = s.id_spe
                ORDER BY e.nom_ens, e.prenoms_ens";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer un enseignant par ID
    public function find($id)
    {
        $sql = "SELECT e.*, f.nom_fonction, f.id_fonction, g.nom_grd, g.id_grd, s.lib_spe,
                       a.date_grd, o.date_occup
                FROM enseignants e
                LEFT JOIN avoir a ON e.id_ens = a.id_ens
                LEFT JOIN grade g ON a.id_grd = g.id_grd
                LEFT JOIN occuper o ON e.id_ens = o.id_ens
                LEFT JOIN fonction f ON o.id_fonction = f.id_fonction
                LEFT JOIN enseignant_specialite es ON e.id_ens = es.id_ens
                LEFT JOIN specialite s ON es.id_spe = s.id_spe
                WHERE e.id_ens = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Ajouter un enseignant (et lier grade/fonction)
    public function create($data)
    {
        try {
            $this->pdo->beginTransaction();

            // 1. Insérer dans enseignants
            $sql = "INSERT INTO enseignants (nom_ens, prenoms_ens, email_ens, date_entree_fonction, num_tel_ens, date_naissance_ens, sexe_ens, photo_ens, mdp_ens)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['nom_ens'],
                $data['prenoms_ens'],
                $data['email_ens'],
                $data['date_entree_fonction'],
                $data['num_tel_ens'],
                $data['date_naissance_ens'],
                $data['sexe_ens'],
                $data['photo_ens'],
                $data['mdp_ens']
            ]);
            $id_enseignant = $this->pdo->lastInsertId();

            // 2. Lier au grade
            $sql = "INSERT INTO avoir (id_grd, id_ens, date_grd) VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['id_grd'],
                $id_enseignant,
                $data['date_grd']
            ]);

            // 3. Lier à la fonction
            $sql = "INSERT INTO occuper (id_fonction, id_ens, date_occup) VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['id_fonction'],
                $id_enseignant,
                $data['date_occup']
            ]);

            // 4. Lier à la spécialité si fournie
            if (!empty($data['id_spe'])) {
                $sql = "INSERT INTO enseignant_specialite (id_ens, id_spe) VALUES (?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$id_enseignant, $data['id_spe']]);
            }

            $this->pdo->commit();
            return $id_enseignant;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Erreur lors de l'ajout de l'enseignant: " . $e->getMessage());
            return false;
        }
    }

    // Modifier un enseignant (et ses liaisons)
    public function update($id, $data)
    {
        try {
            $this->pdo->beginTransaction();

            // 1. Mettre à jour enseignants
            $sql = "UPDATE enseignants SET nom_ens = ?, prenoms_ens = ?, email_ens = ?, date_entree_fonction = ?, num_tel_ens = ?, date_naissance_ens = ?, sexe_ens = ?, photo_ens = ?, mdp_ens = ? WHERE id_ens = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['nom_ens'],
                $data['prenoms_ens'],
                $data['email_ens'],
                $data['date_entree_fonction'],
                $data['num_tel_ens'],
                $data['date_naissance_ens'],
                $data['sexe_ens'],
                $data['photo_ens'],
                $data['mdp_ens'],
                $id
            ]);

            // 2. Mettre à jour avoir
            $sql = "UPDATE avoir SET id_grd = ?, date_grd = ? WHERE id_ens = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['id_grd'],
                $data['date_grd'],
                $id
            ]);

            // 3. Mettre à jour occuper
            $sql = "UPDATE occuper SET id_fonction = ?, date_occup = ? WHERE id_ens = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['id_fonction'],
                $data['date_occup'],
                $id
            ]);

            // 4. Mettre à jour spécialité si fournie
            if (!empty($data['id_spe'])) {
                // Supprimer l'ancienne spécialité
                $this->pdo->prepare("DELETE FROM enseignant_specialite WHERE id_ens = ?")->execute([$id]);
                // Ajouter la nouvelle
                $sql = "INSERT INTO enseignant_specialite (id_ens, id_spe) VALUES (?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$id, $data['id_spe']]);
            }

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Erreur lors de la modification de l'enseignant: " . $e->getMessage());
            return false;
        }
    }

    // Supprimer un enseignant (et ses liaisons)
    public function delete($id)
    {
        try {
            $this->pdo->beginTransaction();

            $this->pdo->prepare("DELETE FROM avoir WHERE id_ens = ?")->execute([$id]);
            $this->pdo->prepare("DELETE FROM occuper WHERE id_ens = ?")->execute([$id]);
            $this->pdo->prepare("DELETE FROM enseignant_specialite WHERE id_ens = ?")->execute([$id]);
            $this->pdo->prepare("DELETE FROM enseignants WHERE id_ens = ?")->execute([$id]);

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Erreur lors de la suppression de l'enseignant: " . $e->getMessage());
            return false;
        }
    }

    public function getAllEnseignants()
    {
        $stmt = $this->pdo->query("SELECT * FROM enseignants");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Nouvelles méthodes ajoutées
    public function getStatistics()
    {
        $stats = [];

        // Total enseignants
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM enseignants");
        $stats['total'] = $stmt->fetchColumn();

        // Enseignants vacataires
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as total
            FROM enseignants e
            JOIN occuper o ON e.id_ens = o.id_ens
            JOIN fonction f ON o.id_fonction = f.id_fonction
            WHERE f.nom_fonction = 'Enseignant vacataire'
        ");
        $stats['vacataires'] = $stmt->fetchColumn();

        // Professeurs d'université
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as total
            FROM enseignants e
            JOIN avoir a ON e.id_ens = a.id_ens
            JOIN grade g ON a.id_grd = g.id_grd
            WHERE g.nom_grd = 'Professeur d\\'université'
        ");
        $stats['professeurs'] = $stmt->fetchColumn();

        return $stats;
    }

    public function getEnseignantsWithPagination($page = 1, $limit = 10)
    {
        $offset = ($page - 1) * $limit;

        $sql = "SELECT e.*, g.nom_grd, f.nom_fonction, s.lib_spe, es.*
                      FROM enseignants e 
                      LEFT JOIN avoir a ON e.id_ens = a.id_ens 
                      LEFT JOIN grade g ON a.id_grd = g.id_grd 
                      LEFT JOIN occuper o ON e.id_ens = o.id_ens 
                      LEFT JOIN fonction f ON o.id_fonction = f.id_fonction 
                      LEFT JOIN enseignant_specialite es ON e.id_ens = es.id_ens 
                      LEFT JOIN specialite s ON es.id_spe = s.id_spe 
                      ORDER BY e.id_ens
                LIMIT $limit OFFSET $offset";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchEnseignants($search, $filters = [])
    {
        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "(e.nom_ens LIKE ? OR e.prenoms_ens LIKE ? OR e.email_ens LIKE ?)";
            $searchParam = "%$search%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
        }

        if (!empty($filters['grade'])) {
            $where[] = "g.id_grd = ?";
            $params[] = $filters['grade'];
        }

        if (!empty($filters['fonction'])) {
            $where[] = "f.id_fonction = ?";
            $params[] = $filters['fonction'];
        }

        if (!empty($filters['specialite'])) {
            $where[] = "s.id_spe = ?";
            $params[] = $filters['specialite'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT e.*, g.nom_grd, f.nom_fonction, s.lib_spe
                FROM enseignants e 
                LEFT JOIN avoir a ON e.id_ens = a.id_ens 
                LEFT JOIN grade g ON a.id_grd = g.id_grd 
                LEFT JOIN occuper o ON e.id_ens = o.id_ens 
                LEFT JOIN fonction f ON o.id_fonction = f.id_fonction 
                LEFT JOIN enseignant_specialite es ON e.id_ens = es.id_ens 
                LEFT JOIN specialite s ON es.id_spe = s.id_spe 
                $whereClause
                ORDER BY e.nom_ens, e.prenoms_ens";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGrades()
    {
        $stmt = $this->pdo->query("SELECT * FROM grade ORDER BY nom_grd");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFonctions()
    {
        $stmt = $this->pdo->query("SELECT * FROM fonction ORDER BY nom_fonction");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSpecialites()
    {
        $stmt = $this->pdo->query("SELECT * FROM specialite ORDER BY lib_spe");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getCommissionMembers()
    {
        $sql = "SELECT e.id_ens, e.nom_ens, e.prenoms_ens, u.id_utilisateur, u.login_utilisateur
                FROM enseignants e
                JOIN utilisateur u ON u.login_utilisateur = e.email_ens
                JOIN posseder p ON p.id_util = u.id_utilisateur
                WHERE p.id_gu = 9 OR p.id_gu = 8";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }




    public function getIdEnseignantsByUserLogin($login)
    {
        $sql = "SELECT e.id_ens FROM enseignants e
                JOIN utilisateur u ON u.login_utilisateur = e.email_ens
                WHERE u.login_utilisateur = :login";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['login' => $login]);
        $enseignant = $stmt->fetch(PDO::FETCH_ASSOC);
        $id_ens = $enseignant['id_ens'];
        return $id_ens;
    }
}
