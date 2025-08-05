<?php
$pdo = new PDO("mysql:host=localhost;dbname=check_master_db", "root", "");

// Récupérer toutes les tables
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

// Définir les descriptions pour chaque table
$descriptions = [
    'action' => 'Liste des actions possibles dans le système',
    'enseignants' => 'Tous les enseignants de l\'établissement',
    'grade' => 'Liste des grades du personnel enseignant',
    'annee_academique' => 'Gestion des années académiques',
    'ecue' => 'Éléments constitutifs d\'une unité d\'enseignement',
    'etudiants' => 'Tous les étudiants inscrits',
    'filieres' => 'Liste des filières de formation',
    'matieres' => 'Catalogue des matières enseignées',
    'niveaux' => 'Niveaux d\'études (L1, L2, L3, M1, M2, etc.)',
    'notes' => 'Système de notation des étudiants',
    'reclamations' => 'Gestion des réclamations des étudiants',
    'reunions' => 'Planning et suivi des réunions',
    'stages' => 'Gestion des stages et rapports',
    'utilisateurs' => 'Comptes utilisateurs du système',
    'consultations' => 'Suivi des consultations pédagogiques',
    'archives' => 'Archivage des documents',
    'documents' => 'Gestion documentaire',
    'evaluations' => 'Système d\'évaluation',
    'presences' => 'Suivi des présences',
    'emplois_temps' => 'Planning des emplois du temps'
];

echo "<table border='1'>
<tr><th>Code</th><th>Libellé</th><th>Description</th></tr>";

// Ajouter l'en-tête de section
echo "<tr><td colspan='3'><strong>GESTION ACADÉMIQUE</strong></td></tr>";

$counter = 1;
foreach ($tables as $table) {
    // Générer le code au format CM_TB001, CM_TB002, etc.
    $code = 'CM_TB' . str_pad($counter, 3, '0', STR_PAD_LEFT);
    
    // Pour le libellé, on peut transformer le nom en quelque chose de plus lisible
    $label = str_replace('_', ' ', strtoupper($table));
    
    // Récupérer la description depuis notre tableau ou utiliser une description par défaut
    $description = isset($descriptions[strtolower($table)]) ? $descriptions[strtolower($table)] : 'Table de données pour ' . $label;

    echo "<tr>
        <td>$code</td>
        <td>$label</td>
        <td>$description</td>
    </tr>";
    
    $counter++;
}
echo "</table>";
?>