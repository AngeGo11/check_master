<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';
require_once "config_year.php";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //Récupération de l'id de l'année en cours
    $query = "SELECT id_ac, date_debut, date_fin FROM annee_academique WHERE statut_annee = 'En cours'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        throw new Exception("Aucune année académique en cours trouvée");
    }

    
    $num_carte = $_POST['card'] ?? '';
    $id_ac = $result['id_ac'];
    $id_niv_etd = $_POST['niveau'] ?? null;
    $mode_paiement =  $_POST['mode_paiement'];
    $motif_paiement =  $_POST['motif_paiement'];
    $numero_cheque = $_POST['numero_cheque'] ?? 'Néant';

    $montant_paye = intval($_POST['montant_paye'] ?? 0);
    $date_reglement = date('Y-m-d');

    if (!$num_carte || !$id_ac || !$id_niv_etd) {
        $_SESSION['error_message'] = "Tous les champs obligatoires ne sont pas remplis.";
        header("Location: ../../index_personnel_administratif.php?page=inscriptions_etudiants");
        exit;
    }

    // 1. Récupérer l'étudiant et son niveau actuel
    $stmt = $pdo->prepare("SELECT num_etd, id_niv_etd FROM etudiants WHERE num_carte_etd = ?");
    $stmt->execute([$num_carte]);
    $etudiant = $stmt->fetch();

    if (!$etudiant) {
        $_SESSION['error_message'] = "Étudiant introuvable.";
        header("Location: ../../index_personnel_administratif.php?page=inscriptions_etudiants");
        exit;
    }

    $num_etd = $etudiant['num_etd'];

    // S'assurer que id_niv_etd est défini
    if (!$id_niv_etd) {
        $id_niv_etd = $etudiant['id_niv_etd'];
        if (!$id_niv_etd) {
            $_SESSION['error_message'] = "Le niveau de l'étudiant n'est pas défini.";
            header("Location: ../../index_personnel_administratif.php?page=inscriptions_etudiants");
            exit;
        }
    }

    // 2. Mettre à jour le niveau si besoin
    if ($etudiant['id_niv_etd'] != $id_niv_etd) {
        $stmt = $pdo->prepare("UPDATE etudiants SET id_niv_etd = ? WHERE num_etd = ?");
        $stmt->execute([$id_niv_etd, $num_etd]);
    }

    // 3. Récupérer le montant à payer depuis la table frais_inscription
    $stmt = $pdo->prepare("SELECT montant FROM frais_inscription WHERE id_niv_etd = ? AND id_ac = ?");
    $stmt->execute([$id_niv_etd, $id_ac]);
    $frais = $stmt->fetch();

    if (!$frais) {
        $_SESSION['error_message'] = "Aucun tarif défini pour ce niveau et cette année académique.";
        header("Location: ../../index_personnel_administratif.php?page=inscriptions_etudiants");
        exit;
    }

    $montant_total = intval($frais['montant']);

    // 4. Vérifier si un règlement existe déjà pour cette année et ce niveau
    $stmt = $pdo->prepare("SELECT * FROM reglement WHERE num_etd = ? AND id_ac = ? AND id_niv_etd = ? AND statut != 'Soldé' LIMIT 1");
    $stmt->execute([$num_etd, $id_ac, $id_niv_etd]);
    $reglement = $stmt->fetch();

    // Générer le numéro de reçu
    $numero_recu = 'REC-' . date('Y') . strtoupper(bin2hex(random_bytes(3)));

    if ($reglement) {
        // Règlement existant
        $id_reglement = $reglement['id_reglement'];
        $numero_reglement = $reglement['numero_reglement'];
        // Utiliser le montant actuel depuis frais_inscription pour être cohérent
        // $montant_total est déjà défini plus haut depuis frais_inscription

        // Calculer le total payé actuel pour ce règlement
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(montant_paye), 0) as total_paye_actuel FROM paiement_reglement WHERE id_reglement = ?");
        $stmt->execute([$id_reglement]);
        $total_paye_actuel = $stmt->fetchColumn();

        // Calculer le nouveau total payé
        $nouveau_total = $total_paye_actuel + $montant_paye;

        // Vérifier si le nouveau total ne dépasse pas le montant total
        if ($nouveau_total > $montant_total) {
            $_SESSION['error_message'] = "Le montant total payé (" . number_format($nouveau_total, 0, ',', ' ') . " FCFA) dépasse le montant à payer (" . number_format($montant_total, 0, ',', ' ') . " FCFA).";
            header("Location: ../../index_personnel_administratif.php?page=inscriptions_etudiants");
            exit;
        }

        // Calculer le nouveau reste à payer
        $reste_a_payer = max(0, $montant_total - $nouveau_total);

        // Déterminer le statut
        if ($nouveau_total >= $montant_total) {
            $statut = 'Soldé';
        } else {
            $statut = 'Partiel';
        }

        // Mettre à jour le règlement
        $stmt = $pdo->prepare("UPDATE reglement SET 
            total_paye = ?, 
            reste_a_payer = ?,
            statut = ?,
            date_reglement = NOW()
            WHERE id_reglement = ?");
        $stmt->execute([$nouveau_total, $reste_a_payer, $statut, $id_reglement]);

        // Ajouter le paiement
        $stmt = $pdo->prepare("INSERT INTO paiement_reglement (id_reglement, numero_recu, mode_de_paiement, motif_paiement, numero_cheque, montant_paye, date_paiement) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$id_reglement, $numero_recu, $mode_paiement, $motif_paiement, $numero_cheque, $montant_paye]);
        $_SESSION['success_message'] = "Paiement enregistré avec succès (Règlement : $numero_reglement).";
    } else {
        // Nouveau règlement
        $prefix = 'REG-' . date('Y');
        $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(numero_reglement, 10) AS UNSIGNED)) FROM reglement WHERE numero_reglement LIKE '$prefix%'");
        $max = $stmt->fetchColumn();
        $next = str_pad(((int)$max ?: 0) + 1, 4, '0', STR_PAD_LEFT);
        $numero_reglement = $prefix . $next;

        // Calculer les paiements précédents pour la même année académique
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(p.montant_paye), 0) as total_deja_paye
            FROM paiement_reglement p
            JOIN reglement r ON r.id_reglement = p.id_reglement
            WHERE r.num_etd = ? AND r.id_ac = ? AND r.id_niv_etd = ?
        ");
        $stmt->execute([$num_etd, $id_ac, $id_niv_etd]);
        $total_deja_paye = intval($stmt->fetchColumn());

        // Nouveau total payé (paiement actuel + paiements précédents)
        $nouveau_total_paye = $total_deja_paye + $montant_paye;

        // Calculer le nouveau reste à payer
        $reste_a_payer = max(0, $montant_total - $nouveau_total_paye);

        // Déterminer le statut pour le nouveau règlement
        if ($nouveau_total_paye >= $montant_total) {
            $statut = 'Soldé';
        } elseif ($montant_paye > 0) {
            $statut = 'Partiel';
        } else {
            $statut = 'Non payé';
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO reglement (num_etd, id_ac, numero_reglement, montant_a_payer, total_paye, reste_a_payer, id_niv_etd, date_reglement, statut, mode_de_paiement, numero_cheque, motif_paiement)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$num_etd, $id_ac, $numero_reglement, $montant_total, $nouveau_total_paye, $reste_a_payer, $id_niv_etd, $date_reglement, $statut, $mode_paiement, $numero_cheque, $motif_paiement]);
            $id_reglement = $pdo->lastInsertId();

            //Ajouter le paiement
            $stmt = $pdo->prepare("INSERT INTO paiement_reglement (id_reglement, numero_recu, mode_de_paiement, motif_paiement, numero_cheque, montant_paye, date_paiement) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$id_reglement, $numero_recu, $mode_paiement, $motif_paiement, $numero_cheque, $montant_paye]);
            $_SESSION['success_message'] = "Paiement enregistré avec succès (Règlement : $numero_reglement).";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Erreur lors de l'enregistrement du règlement : " . $e->getMessage();
            header("Location: ../../index_personnel_administratif.php?page=inscriptions_etudiants");
            exit;
        }
    }

    header("Location: ../../index_personnel_administratif.php?page=inscriptions_etudiants");
    exit;
}
