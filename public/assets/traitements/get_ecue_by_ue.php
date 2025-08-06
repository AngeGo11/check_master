<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../config/config.php';

$id_ue = $_GET['id_ue'] ?? null;
$all = isset($_GET['all']);

try {
    // Créer la connexion PDO
    $pdo = DataBase::getConnection();
    if ($all) {
        // Retourner toutes les UE
        $stmt = $pdo->query("SELECT id_ue, lib_ue, credit_ue, 'ue' as type FROM ue");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
    } elseif ($id_ue) {
        // Retourner les ECUEs pour une UE donnée, ou l'UE elle-même si pas d'ECUE
        $stmt = $pdo->prepare("SELECT id_ecue, lib_ecue, credit_ecue, 'ecue' as type FROM ecue WHERE id_ue = ?");
        $stmt->execute([$id_ue]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($data)) {
            // Pas d'ECUE, on retourne l'UE elle-même
            $stmt = $pdo->prepare("SELECT id_ue as id_ecue, lib_ue as lib_ecue, credit_ue as credit_ecue, 'ue' as type FROM ue WHERE id_ue = ?");
            $stmt->execute([$id_ue]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        throw new Exception('Paramètre manquant : id_ue ou all');
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données : ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>