<?php
require_once __DIR__ . '/config/config.php';

echo "<h2>Test de la base de données - Rapports en attente</h2>";

// Test 1: Vérifier la structure de la table rapport_etudiant
echo "<h3>1. Structure de la table rapport_etudiant</h3>";
$stmt = $pdo->query("DESCRIBE rapport_etudiant");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($columns);
echo "</pre>";

// Test 2: Compter tous les rapports
echo "<h3>2. Nombre total de rapports</h3>";
$stmt = $pdo->query("SELECT COUNT(*) as total FROM rapport_etudiant");
$total = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Total des rapports: " . $total['total'] . "<br>";

// Test 3: Voir tous les statuts
echo "<h3>3. Répartition par statut</h3>";
$stmt = $pdo->query("SELECT statut_rapport, COUNT(*) as count FROM rapport_etudiant GROUP BY statut_rapport");
$statuts = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($statuts);
echo "</pre>";

// Test 4: Voir tous les rapports avec leurs détails
echo "<h3>4. Tous les rapports</h3>";
$stmt = $pdo->query("SELECT * FROM rapport_etudiant ORDER BY date_rapport DESC");
$rapports = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($rapports);
echo "</pre>";

// Test 5: Vérifier les rapports en attente d'approbation
echo "<h3>5. Rapports en attente d'approbation</h3>";
$stmt = $pdo->prepare("SELECT * FROM rapport_etudiant WHERE statut_rapport = ?");
$stmt->execute(["En attente d'approbation"]);
$rapports_attente = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Nombre de rapports en attente: " . count($rapports_attente) . "<br>";
echo "<pre>";
print_r($rapports_attente);
echo "</pre>";

// Test 6: Tester la requête exacte du modèle
echo "<h3>6. Test de la requête du modèle</h3>";
$sql = "SELECT e.nom_etd, e.prenom_etd, e.email_etd, e.num_etd, 
               r.id_rapport_etd, r.nom_rapport, r.date_rapport, 
               COALESCE(d.date_depot, r.date_rapport) as date_depot, r.statut_rapport
        FROM etudiants e
        JOIN rapport_etudiant r ON r.num_etd = e.num_etd 
        LEFT JOIN deposer d ON d.id_rapport_etd = r.id_rapport_etd
        WHERE r.statut_rapport = 'En attente d\'approbation'
        ORDER BY r.date_rapport DESC";

$stmt = $pdo->query($sql);
$resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Résultats de la requête du modèle: " . count($resultats) . " rapports trouvés<br>";
echo "<pre>";
print_r($resultats);
echo "</pre>";

// Test 7: Vérifier les données de la table deposer
echo "<h3>7. Données de la table deposer</h3>";
$stmt = $pdo->query("SELECT * FROM deposer");
$depots = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($depots);
echo "</pre>";

// Test 8: Vérifier les étudiants
echo "<h3>8. Étudiants avec rapports</h3>";
$stmt = $pdo->query("SELECT e.num_etd, e.nom_etd, e.prenom_etd, e.email_etd, COUNT(r.id_rapport_etd) as nb_rapports
                      FROM etudiants e
                      LEFT JOIN rapport_etudiant r ON e.num_etd = r.num_etd
                      GROUP BY e.num_etd
                      HAVING nb_rapports > 0
                      ORDER BY e.nom_etd");
$etudiants_rapports = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($etudiants_rapports);
echo "</pre>";
?> 