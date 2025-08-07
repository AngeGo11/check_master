<?php
// Script de test pour vérifier les frais d'inscription
require_once __DIR__ . '/../../../config/config.php';

echo "<h2>Test des frais d'inscription</h2>";

try {
    $pdo = DataBase::getConnection();
    
    // 1. Vérifier l'année académique en cours
    echo "<h3>1. Année académique en cours</h3>";
    $sql = "SELECT id_ac, annee_ac, statut_annee FROM annee_academique WHERE statut_annee = 'En cours'";
    $stmt = $pdo->query($sql);
    $annee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($annee) {
        echo "✅ Année trouvée: ID={$annee['id_ac']}, Année={$annee['annee_ac']}, Statut={$annee['statut_annee']}<br>";
    } else {
        echo "❌ Aucune année académique en cours trouvée<br>";
        exit;
    }
    
    // 2. Vérifier les niveaux disponibles
    echo "<h3>2. Niveaux disponibles</h3>";
    $sql = "SELECT id_niv_etd, lib_niv_etd FROM niveau_etude ORDER BY id_niv_etd";
    $stmt = $pdo->query($sql);
    $niveaux = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Niveaux trouvés:<br>";
    foreach ($niveaux as $niveau) {
        echo "- ID: {$niveau['id_niv_etd']}, Libellé: {$niveau['lib_niv_etd']}<br>";
    }
    
    // 3. Vérifier les frais d'inscription
    echo "<h3>3. Frais d'inscription</h3>";
    $sql = "SELECT fi.id_frais, fi.montant, ne.lib_niv_etd, aa.annee_ac
            FROM frais_inscription fi
            JOIN niveau_etude ne ON fi.id_niv_etd = ne.id_niv_etd
            JOIN annee_academique aa ON fi.id_ac = aa.id_ac
            WHERE fi.id_ac = ?
            ORDER BY ne.id_niv_etd";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$annee['id_ac']]);
    $frais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($frais) {
        echo "✅ Frais d'inscription trouvés:<br>";
        foreach ($frais as $f) {
            echo "- Niveau: {$f['lib_niv_etd']}, Montant: {$f['montant']} FCFA<br>";
        }
    } else {
        echo "❌ Aucun frais d'inscription trouvé pour l'année {$annee['annee_ac']}<br>";
    }
    
    // 4. Test avec un niveau spécifique
    if (!empty($niveaux)) {
        $niveau_test = $niveaux[0];
        echo "<h3>4. Test avec le niveau {$niveau_test['lib_niv_etd']}</h3>";
        
        $sql = "SELECT fi.montant, ne.lib_niv_etd, aa.annee_ac
                FROM frais_inscription fi
                JOIN niveau_etude ne ON fi.id_niv_etd = ne.id_niv_etd
                JOIN annee_academique aa ON fi.id_ac = aa.id_ac
                WHERE fi.id_niv_etd = ? AND fi.id_ac = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$niveau_test['id_niv_etd'], $annee['id_ac']]);
        $frais_test = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($frais_test) {
            echo "✅ Test réussi: Niveau {$frais_test['lib_niv_etd']}, Montant: {$frais_test['montant']} FCFA<br>";
        } else {
            echo "❌ Test échoué: Aucun frais trouvé pour le niveau {$niveau_test['lib_niv_etd']}<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage();
}
?>
